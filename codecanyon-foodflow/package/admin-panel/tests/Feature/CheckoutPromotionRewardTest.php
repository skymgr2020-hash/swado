<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPromotionRewardTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_checkout_creates_a_separate_order_item_for_a_not_in_cart_free_reward(): void
    {
        $user = User::factory()->create();
        $restaurant = $this->restaurant();
        $paidItem = $this->menuItem($restaurant, 'Paid Thali', 200);
        $rewardItem = $this->menuItem($restaurant, 'Free Drink', 50);
        $address = $this->address($user);

        Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Free Drink Reward',
            'promotion_type' => 'free_item',
            'application_mode' => 'automatic',
            'status' => 'active',
            'conditions' => [
                'audience_type' => 'all',
                'buy_rule' => ['buy_quantity' => 1, 'item_ids' => [$paidItem->id]],
            ],
            'rewards' => [
                'type' => 'free_item',
                'free_item_id' => $rewardItem->id,
                'reward_rule' => [
                    'reward_type' => 'configured_item',
                    'item_ids' => [$rewardItem->id],
                    'reward_quantity' => 1,
                ],
            ],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $response = $this->actingAs($user)->postJson(route('checkout.process'), [
            'restaurant_id' => $restaurant->id,
            'order_type' => 'delivery',
            'delivery_address_id' => $address->id,
            'payment_method' => 'cod',
            'items' => [
                ['id' => $paidItem->id, 'quantity' => 1],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $order = Order::where('id', $response->json('order_id'))->firstOrFail();

        $rewardOrderItem = OrderItem::where('order_id', $order->id)
            ->where('menu_item_id', $rewardItem->id)
            ->first();

        $this->assertNotNull($rewardOrderItem, 'Expected a separate order item to be created for the free reward item.');
        $this->assertSame(0, (int) $rewardOrderItem->unit_price);
        $this->assertSame(0, (int) $rewardOrderItem->total_price);
        $this->assertSame('promotion_reward', data_get($rewardOrderItem->selected_variant, 'custom_fields.line_type'));

        $snapshot = json_decode($order->items, true);
        $this->assertTrue(
            collect($snapshot)->contains(fn (array $line) => (int) ($line['menu_item_id'] ?? 0) === $rewardItem->id),
            'Expected the orders.items JSON snapshot to include the reward item.'
        );
    }

    public function test_web_checkout_bumps_and_annotates_quantity_for_an_included_in_cart_reward(): void
    {
        $user = User::factory()->create();
        $restaurant = $this->restaurant();
        $menuItem = $this->menuItem($restaurant, 'Burger', 120);
        $address = $this->address($user);

        Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Buy One Get One',
            'promotion_type' => 'bogo',
            'application_mode' => 'automatic',
            'status' => 'active',
            'conditions' => ['audience_type' => 'all'],
            'rewards' => [
                'type' => 'bogo',
                'buy_quantity' => 1,
                'free_quantity' => 1,
                'reward_rule' => ['included_in_cart' => true],
            ],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $response = $this->actingAs($user)->postJson(route('checkout.process'), [
            'restaurant_id' => $restaurant->id,
            'order_type' => 'delivery',
            'delivery_address_id' => $address->id,
            'payment_method' => 'cod',
            'items' => [
                ['id' => $menuItem->id, 'quantity' => 2],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $order = Order::where('id', $response->json('order_id'))->firstOrFail();

        $orderItems = OrderItem::where('order_id', $order->id)->where('menu_item_id', $menuItem->id)->get();
        $this->assertCount(1, $orderItems, 'Expected exactly one order item row for the bumped BOGO item.');

        $orderItem = $orderItems->first();
        $this->assertSame(2, $orderItem->quantity);
        $this->assertSame('buy_get_free', data_get($orderItem->selected_variant, 'custom_fields.promotion_line_type'));
        $this->assertSame('1', data_get($orderItem->selected_variant, 'custom_fields.promotion_free_quantity'));
        $this->assertStringContainsString('Promotion:', (string) $orderItem->special_instructions);
    }

    private function menuItem(Restaurant $restaurant, string $name, float $price): MenuItem
    {
        return MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => $name,
            'price' => $price,
            'is_available' => true,
            'is_veg' => true,
            'approval_status' => 'approved',
        ]);
    }

    private function address(User $user): Address
    {
        return Address::create([
            'user_id' => $user->id,
            'name' => 'Home',
            'address' => '1 Test Lane',
            'city' => 'Kolkata',
            'state' => 'WB',
            'pincode' => '700001',
            'phone' => '9999999999',
            'is_default' => true,
            'latitude' => 22.5726,
            'longitude' => 88.3639,
        ]);
    }

    private function restaurant(): Restaurant
    {
        return Restaurant::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Reward Kitchen',
            'slug' => 'reward-kitchen-' . uniqid(),
            'address' => 'Main Street',
            'city' => 'Kolkata',
            'state' => 'WB',
            'pincode' => '700001',
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'phone' => '9999999999',
            'email' => uniqid('reward') . '@example.com',
            'cuisine' => ['Indian'],
            'is_open' => true,
            'is_pure_veg' => false,
            'min_order_amount' => 0,
            'delivery_fee' => 20,
            'delivery_time' => 30,
            'rating' => 4.7,
            'total_ratings' => 10,
            'is_featured' => true,
            'is_verified' => true,
        ]);
    }
}
