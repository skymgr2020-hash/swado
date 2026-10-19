<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\PromotionCouponCode;
use App\Models\PromotionUsage;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPromotionUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_checkout_records_promotion_usage_and_liability_for_a_coupon_order(): void
    {
        $user = User::factory()->create();
        $restaurant = $this->restaurant();
        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Thali',
            'price' => 300,
            'is_available' => true,
            'is_veg' => true,
            'approval_status' => 'approved',
        ]);
        $address = Address::create([
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

        $promotion = Promotion::create([
            'owner_type' => 'admin',
            'title' => 'Shared Web Coupon',
            'promotion_type' => 'flat_discount',
            'application_mode' => 'coupon',
            'status' => 'active',
            'funding_type' => 'shared',
            'platform_share_percent' => 40,
            'restaurant_share_percent' => 60,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'conditions' => ['audience_type' => 'all'],
            'rewards' => ['type' => 'flat', 'value' => 50],
            'targets' => ['restaurant_ids' => [$restaurant->id]],
        ]);
        PromotionCouponCode::create([
            'promotion_id' => $promotion->id,
            'code' => 'WEB50',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('checkout.process'), [
            'restaurant_id' => $restaurant->id,
            'order_type' => 'delivery',
            'delivery_address_id' => $address->id,
            'payment_method' => 'cod',
            'coupon_code' => 'WEB50',
            'items' => [
                ['id' => $menuItem->id, 'quantity' => 1],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $order = Order::where('id', $response->json('order_id'))->firstOrFail();

        $this->assertSame(50.0, (float) $order->discount);
        $this->assertSame(round(300 - 50 + $order->delivery_fee + $order->platform_fee + $order->tax, 2), (float) $order->total);

        $this->assertDatabaseHas('promotion_usage', [
            'order_id' => $order->id,
            'promotion_id' => $promotion->id,
            'coupon_code' => 'WEB50',
        ]);

        $this->assertDatabaseHas('promotion_settlement_ledgers', [
            'order_id' => $order->id,
            'promotion_id' => $promotion->id,
        ]);

        $order->refresh();
        $this->assertSame(30.0, (float) $order->promotion_restaurant_liability);
        $this->assertSame(20.0, (float) $order->promotion_platform_liability);

        $this->assertSame(1, PromotionUsage::where('order_id', $order->id)->count());
        $this->assertSame(1, $promotion->fresh()->used_count);
        $this->assertSame(50.0, (float) $promotion->fresh()->budget_used);
    }

    private function restaurant(): Restaurant
    {
        return Restaurant::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Checkout Kitchen',
            'slug' => 'checkout-kitchen-' . uniqid(),
            'address' => 'Main Street',
            'city' => 'Kolkata',
            'state' => 'WB',
            'pincode' => '700001',
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'phone' => '9999999999',
            'email' => uniqid('checkout') . '@example.com',
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
