<?php

namespace Tests\Unit;

use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\PromotionRewardOrderItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerPromotionRewardTest extends TestCase
{
    use RefreshDatabase;

    public function test_reward_lines_can_be_laravel_collections(): void
    {
        $service = new PromotionRewardOrderItemService();

        $result = $service->applyRewardLines([
            [
                'id' => 10,
                'menu_item_id' => 10,
                'name' => 'Masala Dosa',
                'price' => 120,
                'quantity' => 1,
                'selected_variant' => [],
                'tags' => [],
            ],
        ], collect([
            [
                'menu_item_id' => 10,
                'quantity' => 1,
                'title' => 'Buy One Get One',
                'promotion_id' => 25,
            ],
        ]));

        $this->assertSame(2, $result[0]['quantity']);
        $this->assertSame('buy_get_free', $result[0]['promotion_line_type']);
        $this->assertSame(1, $result[0]['promotion_free_quantity']);
        $this->assertContains('promotion_free_units', $result[0]['tags']);
    }

    public function test_applying_reward_lines_rejects_an_unavailable_reward_item(): void
    {
        $restaurant = Restaurant::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Availability Kitchen',
            'slug' => 'availability-kitchen-' . uniqid(),
            'address' => 'Main Street',
            'city' => 'Kolkata',
            'state' => 'WB',
            'pincode' => '700001',
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'phone' => '9999999999',
            'email' => uniqid('availability') . '@example.com',
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

        $rewardItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Free Drink',
            'price' => 50,
            'is_available' => false,
            'is_veg' => true,
            'approval_status' => 'approved',
        ]);

        $service = new PromotionRewardOrderItemService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer available');

        $service->applyRewardLines(
            [
                ['id' => 1, 'menu_item_id' => 1, 'name' => 'Paid item', 'price' => 200, 'quantity' => 1, 'selected_variant' => [], 'tags' => []],
            ],
            collect([
                ['menu_item_id' => $rewardItem->id, 'quantity' => 1, 'title' => 'Free Drink Reward', 'promotion_id' => 99, 'included_in_cart' => false],
            ]),
            $restaurant->id
        );
    }
}
