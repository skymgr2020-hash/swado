<?php

namespace Tests\Unit;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\PromotionCouponCode;
use App\Models\PromotionUsage;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\PayoutCalculationService;
use App\Services\PromotionEngineService;
use App\Services\PromotionRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_coupon_discount_with_engine_response_shape(): void
    {
        $restaurant = $this->restaurant();

        $promotion = Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Welcome Offer',
            'promotion_type' => 'percentage_discount',
            'application_mode' => 'coupon',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'conditions' => ['min_order_amount' => 100, 'audience_type' => 'all'],
            'rewards' => ['type' => 'percentage', 'value' => 20, 'max_discount' => 75],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        PromotionCouponCode::create([
            'promotion_id' => $promotion->id,
            'code' => 'SAVE20',
            'is_active' => true,
        ]);

        $result = app(PromotionEngineService::class)->validateCoupon([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 500,
            'delivery_fee' => 40,
            'platform_fee' => 10,
            'tax' => 25,
            'code' => 'SAVE20',
            'user_id' => User::factory()->create()->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(75.0, $result['coupon_discount']);
        $this->assertSame(75.0, $result['total_savings']);
        $this->assertSame(500.0, $result['final_total']);
        $this->assertSame('SAVE20', $result['coupon_code']);
        $this->assertCount(1, $result['discount_lines']);
    }

    public function test_it_returns_invalid_reason_for_minimum_order(): void
    {
        $restaurant = $this->restaurant();
        $promotion = Promotion::create([
            'owner_type' => 'admin',
            'title' => 'High Cart Offer',
            'promotion_type' => 'flat_discount',
            'application_mode' => 'coupon',
            'status' => 'active',
            'conditions' => ['min_order_amount' => 1000, 'audience_type' => 'all'],
            'rewards' => ['type' => 'flat', 'value' => 100],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        PromotionCouponCode::create([
            'promotion_id' => $promotion->id,
            'code' => 'BIG100',
            'is_active' => true,
        ]);

        $result = app(PromotionEngineService::class)->validateCoupon([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 250,
            'code' => 'BIG100',
            'user_id' => User::factory()->create()->id,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Minimum order amount', $result['message']);
    }

    public function test_empty_workflow_rules_do_not_turn_percentage_offer_into_free_item_workflow(): void
    {
        $restaurant = $this->restaurant();

        Promotion::create([
            'owner_type' => 'admin',
            'title' => '20% off on First Order',
            'promotion_type' => 'percentage_discount',
            'application_mode' => 'automatic',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'conditions' => [
                'audience_type' => 'all',
                'buy_rule' => [],
            ],
            'rewards' => [
                'type' => 'percentage',
                'value' => 20,
                'reward_rule' => [],
            ],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $result = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 149,
            'items' => [
                ['id' => 1, 'price' => 149, 'quantity' => 1],
            ],
        ]);

        $this->assertSame(29.8, $result['promotion_discount']);
        $this->assertSame(29.8, $result['total_savings']);
        $this->assertSame(119.2, $result['final_total']);
        $this->assertCount(1, $result['applied_promotions']);
        $this->assertFalse(
            collect($result['invalid_reasons'])->contains(
                fn (array $reason) => ($reason['reason_code'] ?? null) === 'reward_unavailable'
            )
        );
    }

    public function test_partial_workflow_metadata_does_not_change_percentage_promotion_type(): void
    {
        $restaurant = $this->restaurant();

        Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Simple Percentage',
            'promotion_type' => 'percentage_discount',
            'application_mode' => 'automatic',
            'status' => 'active',
            'conditions' => ['audience_type' => 'all', 'buy_rule' => []],
            'rewards' => [
                'type' => 'percentage',
                'value' => 10,
                'reward_rule' => ['auto_add' => true],
            ],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $result = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 200,
            'items' => [['id' => 10, 'price' => 200, 'quantity' => 1]],
        ]);

        $this->assertSame(20.0, $result['promotion_discount']);
        $this->assertSame(180.0, $result['final_total']);
        $this->assertEmpty($result['reward_lines']);
    }

    public function test_bogo_requires_enough_item_quantity_before_discounting(): void
    {
        $restaurant = $this->restaurant();

        Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Buy One Get One',
            'promotion_type' => 'bogo',
            'application_mode' => 'automatic',
            'status' => 'active',
            'conditions' => ['audience_type' => 'all'],
            'rewards' => ['type' => 'bogo', 'buy_quantity' => 1, 'free_quantity' => 1, 'reward_rule' => ['included_in_cart' => true]],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $singleItemResult = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 120,
            'items' => [
                ['id' => 10, 'price' => 120, 'quantity' => 1],
            ],
        ]);

        $qualifiedResult = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 240,
            'items' => [
                ['id' => 10, 'price' => 120, 'quantity' => 2],
            ],
        ]);

        $this->assertSame(0.0, $singleItemResult['item_discount']);
        $this->assertSame(120.0, $singleItemResult['final_total']);
        $this->assertFalse((bool) collect($singleItemResult['reward_lines'])->first()['included_in_cart']);
        $this->assertSame(1, collect($singleItemResult['reward_lines'])->first()['quantity']);
        $this->assertSame(120.0, $qualifiedResult['item_discount']);
        $this->assertSame(120.0, $qualifiedResult['final_total']);
        $this->assertTrue((bool) collect($qualifiedResult['reward_lines'])->first()['included_in_cart']);
        $this->assertSame(1, collect($qualifiedResult['reward_lines'])->first()['quantity']);
    }

    public function test_buy_three_get_one_uses_the_registered_quantities(): void
    {
        $restaurant = $this->restaurant();

        Promotion::create([
            'owner_type' => 'restaurant',
            'owner_id' => $restaurant->id,
            'restaurant_id' => $restaurant->id,
            'title' => 'Buy Three Get One',
            'promotion_type' => 'buy_3_get_1',
            'application_mode' => 'automatic',
            'status' => 'active',
            'conditions' => ['audience_type' => 'all'],
            'rewards' => [
                'type' => 'buy_3_get_1',
                'reward_rule' => ['included_in_cart' => true],
            ],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $result = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 400,
            'items' => [['id' => 10, 'name' => 'Meal', 'price' => 100, 'quantity' => 4]],
        ]);

        $this->assertSame(100.0, $result['item_discount']);
        $this->assertSame(300.0, $result['final_total']);
        $this->assertSame(1, collect($result['reward_lines'])->first()['quantity']);
    }

    public function test_free_item_reward_must_be_available_in_the_current_restaurant(): void
    {
        $restaurant = $this->restaurant();
        $otherRestaurant = $this->restaurant();
        $unavailableReward = $this->menuItem($restaurant, 'Unavailable reward', false);
        $otherRestaurantReward = $this->menuItem($otherRestaurant, 'Wrong restaurant reward');

        $promotions = collect([$unavailableReward, $otherRestaurantReward])
            ->map(fn (MenuItem $rewardItem) => Promotion::create([
                'owner_type' => 'admin',
                'title' => 'Scoped Free Item ' . $rewardItem->id,
                'promotion_type' => 'free_item',
                'application_mode' => 'automatic',
                'status' => 'active',
                'conditions' => [
                    'audience_type' => 'all',
                    'buy_rule' => ['buy_quantity' => 1, 'item_ids' => [900]],
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
            ]));

        foreach ($promotions as $promotion) {
            $result = app(PromotionEngineService::class)->calculate([
                'restaurant_id' => $restaurant->id,
                'subtotal' => 200,
                'items' => [['id' => 900, 'name' => 'Paid item', 'price' => 200, 'quantity' => 1]],
            ]);

            $this->assertEmpty($result['applied_promotions']);
            $this->assertTrue(collect($result['invalid_reasons'])->contains(
                fn (array $reason) => ($reason['promotion_id'] ?? null) === $promotion->id
                    && ($reason['reason_code'] ?? null) === 'reward_unavailable'
            ), json_encode($result['invalid_reasons'], JSON_UNESCAPED_SLASHES));
        }
    }

    public function test_packaging_discount_uses_the_real_packaging_charge_bucket(): void
    {
        $restaurant = $this->restaurant();

        Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Free Packaging',
            'promotion_type' => 'packaging_discount',
            'application_mode' => 'automatic',
            'status' => 'active',
            'conditions' => ['audience_type' => 'all'],
            'rewards' => ['type' => 'packaging_discount', 'value' => 100],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $result = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 200,
            'packaging_fee' => 20,
        ]);

        $this->assertSame(20.0, $result['packaging_discount']);
        $this->assertSame(20.0, $result['total_savings']);
        $this->assertSame(200.0, $result['final_total']);
    }

    public function test_charge_discounts_honor_explicit_percentage_and_fixed_value_types(): void
    {
        $service = app(PromotionRewardService::class);
        $deliveryPromotion = new Promotion([
            'title' => 'Fixed delivery saving',
            'promotion_type' => 'delivery_discount',
            'rewards' => [
                'type' => 'delivery_discount',
                'value' => 10,
                'value_type' => 'fixed',
            ],
        ]);
        $packagingPromotion = new Promotion([
            'title' => 'Percentage packaging saving',
            'promotion_type' => 'packaging_discount',
            'rewards' => [
                'type' => 'packaging_discount',
                'value' => 10,
                'value_type' => 'percentage',
            ],
        ]);

        $delivery = $service->calculate($deliveryPromotion, [
            'subtotal' => 200,
            'delivery_fee' => 40,
        ]);
        $packaging = $service->calculate($packagingPromotion, [
            'subtotal' => 200,
            'packaging_fee' => 40,
        ]);

        $this->assertSame(10.0, $delivery['discount_amount']);
        $this->assertSame('delivery_discount', $delivery['bucket']);
        $this->assertSame(4.0, $packaging['discount_amount']);
        $this->assertSame('packaging_discount', $packaging['bucket']);
    }

    public function test_category_discount_requires_matching_cart_category(): void
    {
        $restaurant = $this->restaurant();

        Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Dessert Deal',
            'promotion_type' => 'category_discount',
            'application_mode' => 'automatic',
            'status' => 'active',
            'conditions' => [
                'audience_type' => 'all',
                'contains_category_ids' => [5],
            ],
            'rewards' => [
                'type' => 'category_discount',
                'value' => 50,
                'category_ids' => [5],
            ],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $invalid = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 200,
            'items' => [
                ['id' => 10, 'category_id' => 8, 'price' => 200, 'quantity' => 1],
            ],
        ]);

        $valid = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 200,
            'items' => [
                ['id' => 11, 'category_id' => 5, 'price' => 200, 'quantity' => 1],
            ],
        ]);

        $this->assertSame(0.0, $invalid['item_discount']);
        $this->assertSame(100.0, $valid['item_discount']);
        $this->assertSame(100.0, $valid['final_total']);
    }

    public function test_enterprise_rewards_can_return_points_without_discounting_total(): void
    {
        $restaurant = $this->restaurant();

        Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Points Boost',
            'promotion_type' => 'reward_points',
            'application_mode' => 'automatic',
            'status' => 'active',
            'conditions' => ['audience_type' => 'all'],
            'rewards' => ['type' => 'reward_points', 'value' => 250],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $result = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 300,
        ]);

        $this->assertSame(250, $result['reward_points_earned']);
        $this->assertSame(0.0, $result['total_savings']);
        $this->assertSame(300.0, $result['final_total']);
        $this->assertCount(1, $result['applied_promotions']);
    }

    public function test_shared_funded_discount_returns_liability_breakdown(): void
    {
        $restaurant = $this->restaurant();

        Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Shared Burn',
            'promotion_type' => 'flat_discount',
            'application_mode' => 'automatic',
            'status' => 'active',
            'funding_type' => 'shared',
            'platform_share_percent' => 30,
            'restaurant_share_percent' => 70,
            'conditions' => ['audience_type' => 'all'],
            'rewards' => ['type' => 'flat', 'value' => 100],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $result = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 500,
        ]);

        $this->assertSame(100.0, $result['total_savings']);
        $this->assertSame(100.0, $result['funding_breakdown']['gross_liability_amount']);
        $this->assertSame(30.0, $result['funding_breakdown']['platform_liability_amount']);
        $this->assertSame(70.0, $result['funding_breakdown']['restaurant_liability_amount']);
        $this->assertSame(70.0, $result['promo_liability_lines'][0]['restaurant_liability_amount']);
    }

    public function test_exhausted_campaign_budget_blocks_promotion(): void
    {
        $restaurant = $this->restaurant();

        Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Small Budget',
            'promotion_type' => 'flat_discount',
            'application_mode' => 'automatic',
            'status' => 'active',
            'funding_type' => 'platform',
            'total_budget' => 50,
            'conditions' => ['audience_type' => 'all'],
            'rewards' => ['type' => 'flat', 'value' => 100],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $result = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 500,
        ]);

        $this->assertSame(0.0, $result['total_savings']);
        $this->assertSame('campaign_budget_exhausted', $result['invalid_reasons'][0]['reason_code']);
    }

    public function test_restaurant_promotion_liability_reduces_restaurant_earning(): void
    {
        $order = new Order([
            'subtotal' => 1000,
            'total' => 1000,
            'payment_method' => 'cod',
            'restaurant_delivery_subsidy' => 0,
            'promotion_restaurant_liability' => 75,
        ]);

        $withoutLiability = clone $order;
        $withoutLiability->promotion_restaurant_liability = 0;

        $service = app(PayoutCalculationService::class);
        $base = $service->calculateRestaurantEarning($withoutLiability);
        $funded = $service->calculateRestaurantEarning($order);

        $this->assertSame(75.0, $funded['promotion_restaurant_liability']);
        $this->assertSame(round($base['restaurant_earning'] - 75, 2), $funded['restaurant_earning']);
    }

    public function test_max_discount_caps_buy_x_get_y_reward_value(): void
    {
        $restaurant = $this->restaurant();

        Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Capped BOGO',
            'promotion_type' => 'bogo',
            'application_mode' => 'automatic',
            'status' => 'active',
            'conditions' => ['audience_type' => 'all'],
            'rewards' => [
                'type' => 'bogo',
                'buy_quantity' => 1,
                'free_quantity' => 1,
                'max_discount' => 40,
                'reward_rule' => ['included_in_cart' => true],
            ],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        $result = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 240,
            'items' => [
                ['id' => 10, 'price' => 120, 'quantity' => 2],
            ],
        ]);

        $this->assertSame(40.0, $result['item_discount']);
        $this->assertSame(200.0, $result['final_total']);
    }

    public function test_fraud_rule_blocks_are_logged_for_audit(): void
    {
        $restaurant = $this->restaurant();
        $user = User::factory()->create();
        $promotion = Promotion::create([
            'owner_type' => 'admin',
            'title' => 'One Per User',
            'promotion_type' => 'flat_discount',
            'application_mode' => 'automatic',
            'status' => 'active',
            'fraud_rules' => ['one_per_user' => true],
            'conditions' => ['audience_type' => 'all'],
            'rewards' => ['type' => 'flat', 'value' => 50],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);

        PromotionUsage::create([
            'promotion_id' => $promotion->id,
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'discount_amount' => 50,
        ]);

        $result = app(PromotionEngineService::class)->calculate([
            'restaurant_id' => $restaurant->id,
            'subtotal' => 500,
            'user_id' => $user->id,
        ]);

        $this->assertSame(0.0, $result['total_savings']);
        $this->assertDatabaseHas('promotion_fraud_attempts', [
            'promotion_id' => $promotion->id,
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'rule_key' => 'one_per_user',
            'reason_code' => 'one_per_user_exceeded',
        ]);
    }

    private function menuItem(Restaurant $restaurant, string $name, bool $available = true): MenuItem
    {
        return MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => $name,
            'price' => 50,
            'is_available' => $available,
            'is_veg' => true,
            'approval_status' => 'approved',
        ]);
    }

    private function restaurant(): Restaurant
    {
        return Restaurant::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Promo Kitchen',
            'slug' => 'promo-kitchen-' . uniqid(),
            'address' => 'Main Street',
            'city' => 'Kolkata',
            'state' => 'WB',
            'pincode' => '700001',
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'phone' => '9999999999',
            'email' => uniqid('promo') . '@example.com',
            'cuisine' => ['Indian'],
            'is_open' => true,
            'is_pure_veg' => false,
            'min_order_amount' => 199,
            'delivery_fee' => 20,
            'delivery_time' => 30,
            'rating' => 4.7,
            'total_ratings' => 10,
            'is_featured' => true,
            'is_verified' => true,
        ]);
    }
}
