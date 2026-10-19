<?php

namespace Tests\Unit;

use App\Console\Commands\ReleaseHeldTheatreOrders;
use App\Http\Controllers\Api\TheatreController;
use App\Http\Controllers\Theatre\SeatOrderController;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Theatre;
use App\Models\TheatreScreen;
use App\Models\TheatreShow;
use App\Services\AutoAssignDriverService;
use App\Services\OrderReleaseService;
use App\Support\TheatreSeatMap;
use App\Support\TheatreSeatToken;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Real Eloquent queries against a fresh SQLite database; never loads .env. */
class TheatreWorkflowTest extends TestCase
{
    private Container $container;
    private Capsule $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new \Illuminate\Foundation\Application(dirname(__DIR__, 2));
        Container::setInstance($this->container);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->container);
        $this->container->instance('config', new Repository([
            'app' => ['timezone' => 'UTC', 'key' => 'theatre-test-key'],
            'firebase' => ['credentials' => null],
        ]));
        $translator = new Translator(new ArrayLoader(), 'en');
        $this->container->instance('validator', new Factory($translator, $this->container));
        $this->db = new Capsule($this->container);
        $this->db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db->setAsGlobal();
        $this->db->bootEloquent();
        $this->container->instance('db', $this->db->getDatabaseManager());
        $this->container['validator']->setPresenceVerifier(new \Illuminate\Validation\DatabasePresenceVerifier($this->db->getDatabaseManager()));
        Request::macro('validate', function (array $rules) {
            return app('validator')->make($this->all(), $rules)->validate();
        });
        Model::unsetEventDispatcher();
        Model::clearBootedModels();
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'UTC'));

        Capsule::schema()->create('theatres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Cinema');
            $table->string('status')->default('active');
            $table->string('code')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('owner_phone')->nullable();
            $table->softDeletes();
        });
        Capsule::schema()->create('theatre_screens', function (Blueprint $table) {
            $table->id();
            $table->integer('theatre_id');
            $table->boolean('is_active')->default(true);
            $table->string('name')->default('Screen 1');
            $table->json('seat_map_config')->nullable();
            $table->timestamps();
        });
        Capsule::schema()->create('theatre_shows', function (Blueprint $table) {
            $table->id();
            $table->integer('theatre_id')->default(1);
            $table->integer('screen_id')->default(1);
            $table->string('movie_title')->default('Film');
            $table->timestamp('starts_at');
            $table->timestamp('interval_at')->nullable();
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Capsule::schema()->create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->integer('restaurant_id');
            $table->string('name')->default('Popcorn');
            $table->decimal('price')->default(100);
            $table->boolean('is_available')->default(true);
            $table->string('approval_status')->nullable();
            $table->json('variants')->nullable();
            $table->json('add_ons')->nullable();
        });
        Capsule::schema()->create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Counter');
            $table->boolean('is_open')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->softDeletes();
        });
        Capsule::schema()->create('theatre_restaurants', function (Blueprint $table) {
            $table->integer('theatre_id');
            $table->integer('restaurant_id');
            $table->boolean('is_active')->default(true);
            $table->string('approval_status')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        Capsule::schema()->create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('theatre_id')->nullable();
            $table->integer('show_id')->nullable();
            $table->string('order_type')->default('theatre_delivery');
            $table->string('delivery_timing')->default('interval');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('success');
            $table->string('payment_method')->default('wallet');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
        Capsule::table('theatres')->insert(['id' => 1]);
        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
        });
        Capsule::schema()->create('theatre_runners', function (Blueprint $table) {
            $table->id();
            $table->integer('theatre_id');
            $table->integer('runner_id');
            $table->string('approval_status');
        });
        Capsule::schema()->create('theatre_users', function (Blueprint $table) {
            $table->id();
            $table->integer('theatre_id');
            $table->integer('user_id');
            $table->boolean('is_active')->default(true);
            $table->json('permissions')->nullable();
        });
        Capsule::table('theatre_screens')->insert([
            'id' => 1, 'theatre_id' => 1,
            'seat_map_config' => json_encode(['rows' => [['label' => 'A', 'seat_count' => 3, 'gaps' => [1]]]]),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Model::unsetEventDispatcher();
        Model::clearBootedModels();
        $this->db->getConnection()->disconnect();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        parent::tearDown();
    }

    private function call(object $object, string $method, mixed ...$arguments): mixed
    {
        return (new \ReflectionMethod($object, $method))->invoke($object, ...$arguments);
    }

    private function panel(): array
    {
        $user = $this->getMockBuilder(\App\Models\User::class)->onlyMethods(['can'])->getMock();
        $user->forceFill(['id' => 10, 'is_active' => true]);
        $user->method('can')->willReturn(true);
        Capsule::table('theatre_users')->insert(['user_id' => 10, 'theatre_id' => 1]);
        $service = $this->createMock(\App\Services\TheatreManagementService::class);
        $service->method('theatreForUser')->willReturn(Theatre::find(1));
        $request = new Request();
        $request->setUserResolver(fn () => $user);

        return [new \App\Http\Controllers\Theatre\TheatreDashboardController($service), $request];
    }

    private function checkoutRequest(string $method = 'razorpay'): Request
    {
        Capsule::schema()->table('users', fn (Blueprint $table) => $table->string('name')->nullable());
        Capsule::schema()->table('orders', function (Blueprint $table) {
            foreach (['customer_id', 'restaurant_id', 'screen_id', 'order_group_id'] as $column) $table->integer($column)->nullable();
            foreach (['order_number', 'seat_label', 'customer_name', 'customer_phone', 'customer_address', 'delivery_address', 'payment_id'] as $column) $table->string($column)->nullable();
            foreach (['subtotal', 'delivery_fee', 'platform_fee', 'tax', 'total'] as $column) $table->decimal($column)->nullable();
            $table->json('tax_breakdown')->nullable();
            $table->json('items')->nullable();
        });
        Capsule::schema()->create('order_groups', function (Blueprint $table) {
            $table->id();
            foreach (['user_id', 'theatre_id', 'screen_id', 'show_id'] as $column) $table->integer($column)->nullable();
            foreach (['group_number', 'seat_label', 'delivery_timing', 'payment_status', 'payment_reference'] as $column) $table->string($column)->nullable();
            $table->decimal('total_amount');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamps();
        });
        foreach (['transactions', 'payment_attempts'] as $name) {
            Capsule::schema()->create($name, function (Blueprint $table) {
                $table->id();
                foreach (['order_id', 'user_id', 'created_by'] as $column) $table->integer($column)->nullable();
                foreach (['transaction_id', 'type', 'status', 'payment_method', 'source', 'gateway', 'currency'] as $column) $table->string($column)->nullable();
                $table->decimal('amount');
                $table->timestamps();
            });
        }
        Capsule::schema()->create('delivery_charge_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('platform_fee')->default(10);
        });
        Capsule::table('delivery_charge_settings')->insert(['id' => 1]);
        Capsule::schema()->create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Tax');
            $table->string('type')->default('gst');
            $table->string('calculation_type')->default('percentage');
            $table->decimal('rate')->default(5);
            $table->boolean('is_active')->default(true);
        });
        Capsule::table('tax_settings')->insert(['id' => 1]);
        $tax = $this->createMock(\App\Services\Tax\TaxCalculationService::class);
        $tax->method('override')->willReturn(null);
        $this->container->instance(\App\Services\Tax\TaxCalculationService::class, $tax);
        $response = $this->createMock(\Illuminate\Contracts\Routing\ResponseFactory::class);
        $response->method('json')->willReturnCallback(fn ($data, $status = 200) => new \Illuminate\Http\JsonResponse($data, $status));
        $this->container->instance(\Illuminate\Contracts\Routing\ResponseFactory::class, $response);
        $this->container->instance('log', new \Psr\Log\NullLogger());
        Capsule::table('users')->insert(['id' => 1, 'name' => 'Customer', 'phone' => '9999999999']);
        foreach ([1, 2] as $id) {
            Capsule::table('restaurants')->insert(['id' => $id, 'is_verified' => true]);
            Capsule::table('theatre_restaurants')->insert(['theatre_id' => 1, 'restaurant_id' => $id]);
            Capsule::table('menu_items')->insert(['id' => $id, 'restaurant_id' => $id]);
        }
        $request = new Request([
            'theatre_id' => 1, 'screen_id' => 1, 'seat_label' => 'A1', 'seat_token' => TheatreSeatToken::sign(1, 1, 'A1'),
            'payment_method' => $method,
            'stalls' => [
                ['restaurant_id' => 1, 'items' => [['id' => 1, 'quantity' => 1]]],
                ['restaurant_id' => 2, 'items' => [['id' => 2, 'quantity' => 1]]],
            ],
        ]);
        $request->setUserResolver(fn () => \App\Models\User::find(1));

        return $request;
    }

    public function test_quote_includes_each_stalls_tax_and_fee_without_creating_orders(): void
    {
        $request = $this->checkoutRequest();
        $response = (new TheatreController())->quote($request);
        $this->assertSame(200, $response->status());
        $this->assertEquals(['subtotal' => 200, 'tax' => 10, 'platform_fee' => 20, 'total' => 230], $response->getData(true)['data']);
        $this->assertSame(0, Order::count());
    }

    public function test_group_payment_reference_is_available_to_each_stall_for_refunds(): void
    {
        $request = $this->checkoutRequest();
        $payments = $this->createMock(\App\Http\Controllers\Api\PaymentController::class);
        $payments->expects($this->once())->method('verifyCheckoutPaymentForOrder')->with($request, 'razorpay', 230)->willReturn('pay_group');
        $this->container->instance(\App\Http\Controllers\Api\PaymentController::class, $payments);
        $release = $this->createMock(OrderReleaseService::class);
        $release->expects($this->exactly(2))->method('releaseToRestaurant')->willReturn(true);
        $this->container->instance(OrderReleaseService::class, $release);
        $response = (new TheatreController())->checkout($request);
        $this->assertSame(200, $response->status(), $response->getContent());
        $this->assertSame(['pay_group', 'pay_group'], Order::orderBy('id')->pluck('payment_id')->all());
        $this->assertSame(2, Order::whereNotNull('released_at')->count());
        $this->assertSame(1, Capsule::table('transactions')->count());
    }

    public function test_wallet_total_change_is_rejected_before_debit(): void
    {
        $request = $this->checkoutRequest('wallet');
        $request->merge(['expected_total' => 200]);
        $wallet = $this->createMock(\App\Services\WalletService::class);
        $wallet->expects($this->never())->method('debitInstant');
        $this->container->instance(\App\Services\WalletService::class, $wallet);
        $response = (new TheatreController())->checkout($request);
        $this->assertSame(422, $response->status());
        $this->assertSame(0, Order::count());
    }

    public function test_failed_wallet_payment_rolls_back_all_stall_orders(): void
    {
        $request = $this->checkoutRequest('wallet');
        $wallet = $this->createMock(\App\Services\WalletService::class);
        $wallet->expects($this->once())->method('debitInstant')->willReturn(false);
        $this->container->instance(\App\Services\WalletService::class, $wallet);
        $response = (new TheatreController())->checkout($request);
        $this->assertSame(422, $response->status(), $response->getContent());
        $this->assertSame(0, Order::count());
        $this->assertSame(0, Capsule::table('order_groups')->count());
    }

    public function test_inactive_membership_cannot_use_global_permission_to_enter_panel(): void
    {
        [$controller, $request] = $this->panel();
        Capsule::table('theatre_users')->update(['is_active' => false]);
        $this->assertFalse($this->call($controller, 'theatreCan', $request, 'theatre.screens.manage'));
    }

    public function test_screen_from_another_theatre_cannot_be_edited(): void
    {
        [$controller, $request] = $this->panel();
        $screen = new TheatreScreen(['theatre_id' => 2]);
        $this->expectException(HttpException::class);
        $controller->editSeatMap($request, $screen);
    }

    public function test_overlapping_shows_are_rejected(): void
    {
        [$controller, $request] = $this->panel();
        TheatreShow::create(['starts_at' => now(), 'ends_at' => now()->addHours(2)]);
        $request->merge(['screen_id' => 1, 'movie_title' => 'Overlap', 'starts_at' => now()->addHour()->toDateTimeString(), 'ends_at' => now()->addHours(3)->toDateTimeString()]);
        $this->expectException(ValidationException::class);
        $controller->storeShow($request);
    }

    public function test_invalid_day_filter_returns_validation_error(): void
    {
        [$controller, $request] = $this->panel();
        $request->merge(['date' => 'not-a-date']);
        $this->expectException(ValidationException::class);
        $controller->shows($request);
    }

    public function test_admin_custom_tags_are_saved_and_deduplicated(): void
    {
        $request = new Request([
            'name' => 'Cinema', 'code' => 'TEST', 'owner_name' => 'Owner',
            'owner_email' => 'owner@example.test', 'owner_phone' => '9999999999',
            'owner_password' => 'example-password', 'latitude' => 20, 'longitude' => 80,
            'geofence_radius_meters' => 120, 'status' => 'active',
            'tags' => ['IMAX'], 'custom_tags' => ' IMAX, Parking, ',
        ]);
        $controller = new \App\Http\Controllers\Admin\TheatreController(new \App\Services\TheatreManagementService());
        $data = $this->call($controller, 'validatedTheatre', $request);
        $this->assertSame(['IMAX', 'Parking'], $data['tags']);
        $this->assertArrayNotHasKey('custom_tags', $data);
    }

    #[DataProvider('runnerMemberships')]
    public function test_runner_requires_active_roster_and_venue(string $status, string $venueStatus, bool $allowed): void
    {
        Capsule::table('theatres')->update(['status' => $venueStatus]);
        Capsule::table('theatre_runners')->insert(['theatre_id' => 1, 'runner_id' => 7, 'approval_status' => $status]);
        $user = $this->getMockBuilder(\App\Models\User::class)->onlyMethods(['hasRole'])->getMock();
        $user->forceFill(['id' => 7, 'theatre_id' => 1, 'is_active' => true]);
        $user->method('hasRole')->willReturn(true);
        $guard = $this->createMock(\Illuminate\Contracts\Auth\Guard::class);
        $guard->method('user')->willReturn($user);
        $this->container->instance('auth', $guard);
        if (! $allowed) $this->expectException(HttpException::class);
        $id = $this->call(new \App\Http\Controllers\Api\TheatreRunnerController(), 'activeTheatreId');
        if ($allowed) $this->assertSame(1, $id);
    }

    public static function runnerMemberships(): array
    {
        return [['active', 'active', true], ['pending', 'active', false], ['active', 'inactive', false]];
    }

    public function test_panel_views_render_empty_and_populated_stalls_without_mapped_variable(): void
    {
        $this->container->instance('files', new \Illuminate\Filesystem\Filesystem());
        $this->container->instance('events', new \Illuminate\Events\Dispatcher($this->container));
        $this->container['config']->set('view.paths', [dirname(__DIR__) . '/Fixtures/theatre-views', dirname(__DIR__, 2) . '/resources/views']);
        $this->container['config']->set('view.compiled', sys_get_temp_dir() . '/swado-theatre-view-tests');
        if (! is_dir(config('view.compiled'))) mkdir(config('view.compiled'), 0777, true);
        $this->container->register(\Illuminate\View\ViewServiceProvider::class);
        $url = $this->createMock(\Illuminate\Routing\UrlGenerator::class);
        $url->method('route')->willReturn('/test-route');
        $this->container->instance('url', $url);
        $request = new Request();
        $session = new \Illuminate\Session\Store('test', new \Illuminate\Session\ArraySessionHandler(120));
        $session->start();
        $request->setLaravelSession($session);
        $this->container->instance('request', $request);
        $this->container->instance('session', $session);
        $views = $this->container['view'];
        $views->share('errors', new \Illuminate\Support\ViewErrorBag());
        $theatre = Theatre::find(1);
        foreach ([false, true] as $manage) {
            foreach ([collect(), collect([new \App\Models\Restaurant(['id' => 1, 'name' => "Owner's Counter", 'is_verified' => false, 'is_open' => false])])] as $stalls) {
                $html = $views->make('theatre.restaurants', ['theatre' => $theatre, 'stalls' => $stalls, 'capabilities' => ['restaurants_manage' => $manage]])->render();
                $this->assertStringContainsString($stalls->isEmpty() ? 'No stalls yet' : 'Pending admin approval', $html);
            }
        }
        foreach (['screens' => 'screens', 'shows' => 'shows', 'runners' => 'roster'] as $view => $variable) {
            $html = $views->make('theatre.' . $view, [
                'theatre' => $theatre, $variable => collect(), 'screens' => collect(),
                'date' => now(), 'capabilities' => ['screens_manage' => true, 'shows_manage' => true, 'runners_manage' => true],
            ])->render();
            $this->assertNotEmpty($html);
        }
    }

    public function test_checkout_rejects_items_from_another_stall(): void
    {
        Capsule::table('menu_items')->insert(['id' => 1, 'restaurant_id' => 2]);
        $this->assertNull($this->call(new TheatreController(), 'priceStallItems', [['id' => 1, 'quantity' => 1]], 1));
    }

    public function test_checkout_prices_options_from_the_catalog(): void
    {
        Capsule::table('menu_items')->insert([
            'id' => 1, 'restaurant_id' => 1,
            'variants' => json_encode([['name' => 'Large', 'price' => 50]]),
            'add_ons' => json_encode([['name' => 'Butter', 'price' => 10]]),
        ]);
        $lines = $this->call(new TheatreController(), 'priceStallItems', [[
            'id' => 1, 'quantity' => 2,
            'selected_variant' => ['name' => 'Large', 'price' => 0],
            'selected_add_ons' => [['name' => 'Butter', 'price' => 0]],
        ]], 1);
        $this->assertEquals(320, $lines[0]['total']);
    }

    #[DataProvider('unavailableItems')]
    public function test_checkout_rejects_unavailable_items(array $attributes): void
    {
        Capsule::table('menu_items')->insert(array_merge(['id' => 1, 'restaurant_id' => 1], $attributes));
        $this->assertNull($this->call(new TheatreController(), 'priceStallItems', [['id' => 1, 'quantity' => 1]], 1));
    }

    public static function unavailableItems(): array
    {
        return [[['is_available' => false]], [['approval_status' => 'pending']]];
    }

    #[DataProvider('invalidOptions')]
    public function test_unavailable_options_are_rejected(string $method, array $selected): void
    {
        $this->expectException(\RuntimeException::class);
        $this->call(new TheatreController(), $method, [['name' => 'Large', 'price' => 50, 'is_available' => false]], $selected);
    }

    public static function invalidOptions(): array
    {
        return [['resolveSelectedOption', ['name' => 'Large']], ['resolveSelectedAddOns', [['name' => 'Large']]]];
    }

    public function test_unverified_stall_cannot_be_ordered_or_browsed(): void
    {
        Capsule::table('restaurants')->insert(['id' => 1]);
        Capsule::table('theatre_restaurants')->insert(['theatre_id' => 1, 'restaurant_id' => 1]);
        try {
            $this->call(new TheatreController(), 'priceStalls', Theatre::find(1), [['restaurant_id' => 1, 'items' => []]]);
            $this->fail('Unverified stall accepted.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('not available', $exception->getMessage());
        }
        $this->expectException(HttpException::class);
        $this->call(new SeatOrderController(), 'assertStallServesTheatre', Theatre::find(1), \App\Models\Restaurant::find(1));
    }

    public function test_deleted_seat_is_rejected_even_with_a_valid_signature(): void
    {
        $this->expectException(HttpException::class);
        $this->call(new SeatOrderController(), 'resolveSeat', Theatre::find(1), TheatreScreen::find(1), 'A3', TheatreSeatToken::sign(1, 1, 'A3'));
    }

    public function test_valid_seat_signature_normalizes_checkout_label(): void
    {
        $request = new Request(['theatre_id' => 1, 'screen_id' => 1, 'seat_label' => ' a2 ', 'seat_token' => TheatreSeatToken::sign(1, 1, 'A2')]);
        $this->call(new TheatreController(), 'resolveTheatreAndScreen', $request);
        $this->assertSame('A2', $request->seat_label);
    }

    public function test_omitting_show_id_does_not_bypass_order_cutoff(): void
    {
        TheatreShow::create(['starts_at' => now()->subHour(), 'ends_at' => now()->addMinutes(5)]);
        $this->expectException(\RuntimeException::class);
        $this->call(new TheatreController(), 'resolveShowAndTiming', new Request(), TheatreScreen::find(1));
    }

    public function test_unscheduled_screen_keeps_asap_ordering(): void
    {
        $this->assertSame([null, 'asap', null], $this->call(new TheatreController(), 'resolveShowAndTiming', new Request(), TheatreScreen::find(1)));
    }

    public function test_current_show_is_inferred_for_asap_order(): void
    {
        $show = TheatreShow::create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        [$resolved] = $this->call(new TheatreController(), 'resolveShowAndTiming', new Request(), TheatreScreen::find(1));
        $this->assertSame($show->id, $resolved->id);
    }

    public function test_future_show_does_not_take_orders_before_trailers(): void
    {
        $show = new TheatreShow(['is_active' => true, 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(3)]);
        $this->assertFalse($show->isAcceptingOrders());
        $this->assertTrue($show->isAcceptingOrders(now()->addMinutes(40)));
    }

    public function test_interval_requires_preparation_time(): void
    {
        $show = TheatreShow::create(['starts_at' => now()->subHour(), 'interval_at' => now()->addMinutes(20), 'ends_at' => now()->addHour(), 'is_active' => true]);
        $this->expectException(\RuntimeException::class);
        $this->call(new TheatreController(), 'resolveShowAndTiming', new Request(['show_id' => $show->id, 'delivery_timing' => 'interval']), TheatreScreen::find(1));
    }

    public function test_seat_map_normalizes_labels_and_numeric_gaps(): void
    {
        $config = TheatreSeatMap::validate(json_encode(['rows' => [['label' => 'ab', 'seat_count' => '3', 'gaps' => ['1']]]]));
        $screen = new TheatreScreen(['seat_map_config' => $config]);
        $this->assertSame(['AB1', 'AB2'], $screen->seatLabels());
    }

    #[DataProvider('invalidMaps')]
    public function test_invalid_seat_layouts_are_rejected(array $config): void
    {
        $this->expectException(ValidationException::class);
        TheatreSeatMap::validate(json_encode($config));
    }

    public static function invalidMaps(): array
    {
        return [
            [['rows' => []]],
            [['rows' => ['invalid']]],
            [['rows' => [['label' => 'A', 'seat_count' => 2], ['label' => 'a', 'seat_count' => 2]]]],
            [['rows' => [['label' => 'A', 'seat_count' => 2, 'gaps' => [2]]]]],
            [['rows' => [['label' => 'A', 'seat_count' => 2, 'gaps' => [0, 1]]]]],
            [['rows' => [['label' => 'A', 'seat_count' => 2, 'column_blocks' => [1]]]]],
            [['rows' => [['label' => '<script>', 'seat_count' => 2]]]],
        ];
    }

    public function test_empty_layout_is_not_orderable(): void
    {
        $this->assertFalse((new TheatreScreen(['seat_map_config' => ['rows' => []]]))->hasSeatMap());
    }

    public function test_held_orders_are_hidden_by_both_visibility_checks(): void
    {
        $id = Capsule::table('orders')->insertGetId(['theatre_id' => 1, 'scheduled_for' => now()->addHour()]);
        $order = Order::find($id);
        $this->assertFalse($order->isVisibleToRestaurant());
        $this->assertFalse(Order::visibleToRestaurant()->whereKey($id)->exists());
        $order->update(['released_at' => now()]);
        $this->assertTrue($order->isVisibleToRestaurant());
        $this->assertTrue(Order::visibleToRestaurant()->whereKey($id)->exists());
    }

    public function test_theatre_orders_never_enter_road_assignment(): void
    {
        $id = Capsule::table('orders')->insertGetId(['theatre_id' => 1, 'status' => 'ready_for_pickup']);
        $this->assertNull((new AutoAssignDriverService())->autoAssignOrder(Order::find($id)));
        $this->assertNull((new AutoAssignDriverService())->reassignOnCancellation($id, 7));
    }

    public function test_ready_order_notifies_runners_after_commit_only(): void
    {
        $id = Capsule::table('orders')->insertGetId(['theatre_id' => 1, 'status' => 'preparing']);
        $notifications = 0;
        $service = $this->createMock(\App\Services\TheatreManagementService::class);
        $service->expects($this->once())->method('notifyRunnersOrderReady')->willReturnCallback(function () use (&$notifications) { $notifications++; });
        $this->container->instance(\App\Services\TheatreManagementService::class, $service);
        Model::setEventDispatcher(new \Illuminate\Events\Dispatcher($this->container));
        Model::clearBootedModels();
        $connection = $this->db->getConnection();
        $connection->setTransactionManager(new \Illuminate\Database\DatabaseTransactionsManager());
        $connection->beginTransaction();
        Order::find($id)->update(['status' => 'ready_for_pickup']);
        $this->assertSame(0, $notifications);
        $connection->commit();
        $this->assertSame(1, $notifications);
    }

    public function test_release_only_processes_paid_or_cod_due_theatre_orders_once(): void
    {
        $rows = [
            ['theatre_id' => 1, 'scheduled_for' => now()->addMinutes(10)],
            ['theatre_id' => 1, 'scheduled_for' => now()->addMinutes(10), 'payment_method' => 'cod', 'payment_status' => 'pending'],
            ['theatre_id' => 1, 'scheduled_for' => now()->addMinutes(10), 'payment_status' => 'pending'],
            ['theatre_id' => 1, 'scheduled_for' => now()->addMinutes(30)],
            ['theatre_id' => 1, 'scheduled_for' => now()->addMinutes(10), 'status' => 'cancelled'],
            ['scheduled_for' => now()->addMinutes(10)],
        ];
        foreach ($rows as $row) Capsule::table('orders')->insert($row);
        $release = $this->createMock(OrderReleaseService::class);
        $release->expects($this->exactly(2))->method('releaseToRestaurant')->willReturn(true);
        $command = new ReleaseHeldTheatreOrders();
        $command->setOutput(new \Illuminate\Console\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]), new NullOutput()));
        $command->handle($release);
        $command->handle($release);
        $this->assertSame([1, 2], Order::whereNotNull('released_at')->pluck('id')->all());
    }
}
