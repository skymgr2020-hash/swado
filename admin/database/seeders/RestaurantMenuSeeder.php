<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Cuisine;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local-dev-only seeder: this sqlite database has zero restaurants, so
 * every real /api/restaurants/* endpoint the customer website (web/)
 * calls returns an empty list. Seeds a realistic set of restaurants +
 * menus through the actual Eloquent models so the real controllers,
 * distance/ETA calculation and pricing logic all have data to serve —
 * nothing here is faked in the frontend, it's real rows behind the real API.
 * Coordinates cluster around Adityapur, Jamshedpur (22.7901, 86.1657),
 * matching the default location the website starts at.
 */
class RestaurantMenuSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::updateOrCreate(
            ['email' => 'demo.owner@swado.test'],
            [
                'name' => 'Demo Restaurant Owner',
                'phone' => '9999900002',
                'password' => Hash::make('DemoPass123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        if (! $owner->hasRole('restaurant_owner')) {
            $owner->assignRole('restaurant_owner');
        }

        $cuisineIds = Cuisine::pluck('id', 'name');

        foreach ($this->restaurants() as $spec) {
            $restaurant = Restaurant::updateOrCreate(
                ['slug' => $spec['slug']],
                [
                    'owner_id' => $owner->id,
                    'name' => $spec['name'],
                    'description' => $spec['description'],
                    'address' => $spec['address'],
                    'city' => 'Jamshedpur',
                    'state' => 'Jharkhand',
                    'pincode' => '831013',
                    'latitude' => $spec['lat'],
                    'longitude' => $spec['lng'],
                    'phone' => $spec['phone'],
                    'email' => strtolower(str_replace(' ', '.', $spec['name'])) . '@swado.test',
                    'cuisine' => $spec['cuisines'],
                    'is_open' => true,
                    'is_pure_veg' => $spec['pure_veg'] ?? false,
                    'min_order_amount' => 0,
                    'delivery_fee' => 30,
                    'delivery_time' => $spec['delivery_time'],
                    'rating' => $spec['rating'],
                    'total_ratings' => $spec['total_ratings'],
                    'banner_image' => $spec['banner'],
                    'logo_image' => $spec['logo'],
                    'is_verified' => true,
                    'delivery_radius' => 15,
                    'restaurant_type' => 'all',
                    'weekly_timings' => $this->alwaysOpenTimings(),
                ]
            );

            foreach ($spec['menu'] as $categoryName => $items) {
                $category = Category::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'name' => $categoryName],
                    ['is_active' => true]
                );

                foreach ($items as $item) {
                    MenuItem::updateOrCreate(
                        ['restaurant_id' => $restaurant->id, 'name' => $item['name']],
                        [
                            'category_id' => $category->id,
                            'cuisine_id' => $cuisineIds[$spec['primary_cuisine']] ?? null,
                            'description' => $item['description'],
                            'price' => $item['price'],
                            'discounted_price' => $item['discounted_price'] ?? null,
                            'images' => [$item['image']],
                            'is_veg' => $item['is_veg'],
                            'food_type' => $item['is_veg'] ? 'veg' : 'non_veg',
                            'is_available' => true,
                            'is_recommended' => $item['recommended'] ?? false,
                            'is_bestseller' => $item['bestseller'] ?? false,
                            'rating' => $item['rating'] ?? 4.0,
                            'total_orders' => $item['orders'] ?? random_int(20, 400),
                        ]
                    );
                }
            }
        }

        $this->command->info('Seeded ' . count($this->restaurants()) . ' restaurants with menus.');
    }

    private function img(string $seed, int $w = 800, int $h = 600): string
    {
        return "https://picsum.photos/seed/{$seed}/{$w}/{$h}";
    }

    private function alwaysOpenTimings(): array
    {
        $day = ['is_open' => true, 'open_time' => '00:00', 'close_time' => '23:59', 'break_start' => null, 'break_end' => null];

        return array_fill_keys(
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            $day
        );
    }

    private function restaurants(): array
    {
        return [
            [
                'slug' => 'spice-route-kitchen',
                'name' => 'Spice Route Kitchen',
                'description' => 'North Indian & Mughlai comfort food.',
                'address' => '12, Main Road, Adityapur, Jamshedpur',
                'phone' => '9204885001',
                'lat' => 22.7901, 'lng' => 86.1657,
                'cuisines' => ['North Indian', 'Mughlai'],
                'primary_cuisine' => 'North Indian',
                'pure_veg' => false,
                'delivery_time' => 35,
                'rating' => 4.3, 'total_ratings' => 214,
                'banner' => $this->img('spice-route-banner'),
                'logo' => $this->img('spice-route-logo', 200, 200),
                'menu' => [
                    'Starters' => [
                        ['name' => 'Paneer Tikka', 'description' => 'Char-grilled cottage cheese with spices.', 'price' => 220, 'discounted_price' => 189, 'is_veg' => true, 'image' => $this->img('paneer-tikka'), 'recommended' => true, 'rating' => 4.5],
                        ['name' => 'Chicken Seekh Kebab', 'description' => 'Minced chicken skewers, char-grilled.', 'price' => 260, 'is_veg' => false, 'image' => $this->img('seekh-kebab'), 'bestseller' => true, 'rating' => 4.6],
                        ['name' => 'Veg Spring Roll', 'description' => 'Crispy rolls with mixed vegetables.', 'price' => 160, 'is_veg' => true, 'image' => $this->img('spring-roll'), 'rating' => 4.1],
                    ],
                    'Main Course' => [
                        ['name' => 'Butter Chicken', 'description' => 'Creamy tomato gravy, tandoori chicken.', 'price' => 320, 'is_veg' => false, 'image' => $this->img('butter-chicken'), 'bestseller' => true, 'rating' => 4.7, 'orders' => 512],
                        ['name' => 'Dal Makhani', 'description' => 'Slow-cooked black lentils, butter & cream.', 'price' => 220, 'is_veg' => true, 'image' => $this->img('dal-makhani'), 'recommended' => true, 'rating' => 4.5],
                        ['name' => 'Paneer Butter Masala', 'description' => 'Cottage cheese in rich tomato gravy.', 'price' => 260, 'is_veg' => true, 'image' => $this->img('paneer-butter-masala'), 'rating' => 4.4],
                        ['name' => 'Mutton Rogan Josh', 'description' => 'Slow-braised mutton in Kashmiri spices.', 'price' => 380, 'is_veg' => false, 'image' => $this->img('rogan-josh'), 'rating' => 4.6],
                    ],
                    'Breads' => [
                        ['name' => 'Butter Naan', 'description' => 'Tandoor-baked leavened bread.', 'price' => 55, 'is_veg' => true, 'image' => $this->img('butter-naan'), 'rating' => 4.3],
                        ['name' => 'Garlic Naan', 'description' => 'Naan topped with garlic & coriander.', 'price' => 65, 'is_veg' => true, 'image' => $this->img('garlic-naan'), 'rating' => 4.4],
                    ],
                ],
            ],
            [
                'slug' => 'green-leaf-udupi',
                'name' => 'Green Leaf Udupi',
                'description' => 'Authentic South Indian, 100% vegetarian.',
                'address' => '45, Station Road, Adityapur, Jamshedpur',
                'phone' => '9204885002',
                'lat' => 22.7965, 'lng' => 86.1598,
                'cuisines' => ['South Indian'],
                'primary_cuisine' => 'South Indian',
                'pure_veg' => true,
                'delivery_time' => 25,
                'rating' => 4.5, 'total_ratings' => 341,
                'banner' => $this->img('green-leaf-banner'),
                'logo' => $this->img('green-leaf-logo', 200, 200),
                'menu' => [
                    'Tiffins' => [
                        ['name' => 'Masala Dosa', 'description' => 'Crisp rice crepe, spiced potato filling.', 'price' => 130, 'is_veg' => true, 'image' => $this->img('masala-dosa'), 'bestseller' => true, 'rating' => 4.7, 'orders' => 620],
                        ['name' => 'Idli Sambar', 'description' => 'Steamed rice cakes with lentil sambar.', 'price' => 90, 'is_veg' => true, 'image' => $this->img('idli-sambar'), 'recommended' => true, 'rating' => 4.5],
                        ['name' => 'Rava Upma', 'description' => 'Semolina cooked with vegetables & spices.', 'price' => 100, 'is_veg' => true, 'image' => $this->img('rava-upma'), 'rating' => 4.2],
                        ['name' => 'Medu Vada', 'description' => 'Crispy lentil doughnuts.', 'price' => 80, 'is_veg' => true, 'image' => $this->img('medu-vada'), 'rating' => 4.3],
                    ],
                    'Rice & Curries' => [
                        ['name' => 'Curd Rice', 'description' => 'Comforting rice tempered with curd.', 'price' => 120, 'is_veg' => true, 'image' => $this->img('curd-rice'), 'rating' => 4.4],
                        ['name' => 'Bisi Bele Bath', 'description' => 'Spiced rice & lentil one-pot dish.', 'price' => 150, 'is_veg' => true, 'image' => $this->img('bisibele-bath'), 'rating' => 4.5],
                    ],
                    'Beverages' => [
                        ['name' => 'Filter Coffee', 'description' => 'South Indian style filter coffee.', 'price' => 45, 'is_veg' => true, 'image' => $this->img('filter-coffee'), 'bestseller' => true, 'rating' => 4.8],
                        ['name' => 'Buttermilk', 'description' => 'Spiced chilled buttermilk.', 'price' => 35, 'is_veg' => true, 'image' => $this->img('buttermilk'), 'rating' => 4.3],
                    ],
                ],
            ],
            [
                'slug' => 'dragon-wok',
                'name' => 'Dragon Wok',
                'description' => 'Indo-Chinese & Asian favorites.',
                'address' => '8, Market Complex, Adityapur, Jamshedpur',
                'phone' => '9204885003',
                'lat' => 22.7845, 'lng' => 86.1720,
                'cuisines' => ['Chinese'],
                'primary_cuisine' => 'Chinese',
                'pure_veg' => false,
                'delivery_time' => 30,
                'rating' => 4.1, 'total_ratings' => 158,
                'banner' => $this->img('dragon-wok-banner'),
                'logo' => $this->img('dragon-wok-logo', 200, 200),
                'menu' => [
                    'Starters' => [
                        ['name' => 'Veg Manchurian', 'description' => 'Fried vegetable balls in tangy sauce.', 'price' => 180, 'is_veg' => true, 'image' => $this->img('veg-manchurian'), 'bestseller' => true, 'rating' => 4.4],
                        ['name' => 'Chilli Chicken', 'description' => 'Wok-tossed chicken with peppers & soy.', 'price' => 240, 'is_veg' => false, 'image' => $this->img('chilli-chicken'), 'recommended' => true, 'rating' => 4.5],
                        ['name' => 'Spring Rolls', 'description' => 'Crispy vegetable spring rolls.', 'price' => 150, 'is_veg' => true, 'image' => $this->img('dragon-spring-rolls'), 'rating' => 4.0],
                    ],
                    'Noodles & Rice' => [
                        ['name' => 'Veg Hakka Noodles', 'description' => 'Stir-fried noodles with vegetables.', 'price' => 170, 'is_veg' => true, 'image' => $this->img('hakka-noodles'), 'rating' => 4.2],
                        ['name' => 'Chicken Fried Rice', 'description' => 'Wok-fried rice with chicken & egg.', 'price' => 200, 'is_veg' => false, 'image' => $this->img('chicken-fried-rice'), 'bestseller' => true, 'rating' => 4.3, 'orders' => 289],
                        ['name' => 'Schezwan Fried Rice', 'description' => 'Spicy schezwan sauce fried rice.', 'price' => 190, 'is_veg' => true, 'image' => $this->img('schezwan-rice'), 'rating' => 4.1],
                    ],
                ],
            ],
            [
                'slug' => 'pizza-piazza',
                'name' => 'Pizza Piazza',
                'description' => 'Wood-fired pizzas & Italian classics.',
                'address' => '3, City Center, Adityapur, Jamshedpur',
                'phone' => '9204885004',
                'lat' => 22.7930, 'lng' => 86.1590,
                'cuisines' => ['Italian', 'Fast Food'],
                'primary_cuisine' => 'Italian',
                'pure_veg' => false,
                'delivery_time' => 32,
                'rating' => 4.4, 'total_ratings' => 267,
                'banner' => $this->img('pizza-piazza-banner'),
                'logo' => $this->img('pizza-piazza-logo', 200, 200),
                'menu' => [
                    'Pizzas' => [
                        ['name' => 'Margherita Pizza', 'description' => 'Classic tomato, mozzarella & basil.', 'price' => 249, 'is_veg' => true, 'image' => $this->img('margherita'), 'bestseller' => true, 'rating' => 4.6, 'orders' => 401],
                        ['name' => 'Farmhouse Pizza', 'description' => 'Loaded with garden vegetables.', 'price' => 329, 'is_veg' => true, 'image' => $this->img('farmhouse-pizza'), 'recommended' => true, 'rating' => 4.5],
                        ['name' => 'Chicken Pepperoni Pizza', 'description' => 'Spicy chicken pepperoni & cheese.', 'price' => 399, 'is_veg' => false, 'image' => $this->img('pepperoni-pizza'), 'rating' => 4.6],
                    ],
                    'Pasta' => [
                        ['name' => 'Penne Alfredo', 'description' => 'Creamy white sauce pasta.', 'price' => 220, 'is_veg' => true, 'image' => $this->img('penne-alfredo'), 'rating' => 4.3],
                        ['name' => 'Arrabbiata Pasta', 'description' => 'Spicy tomato & garlic pasta.', 'price' => 210, 'is_veg' => true, 'image' => $this->img('arrabbiata'), 'rating' => 4.2],
                    ],
                    'Sides' => [
                        ['name' => 'Garlic Bread', 'description' => 'Toasted bread with garlic butter.', 'price' => 120, 'is_veg' => true, 'image' => $this->img('garlic-bread'), 'rating' => 4.4],
                    ],
                ],
            ],
            [
                'slug' => 'biryani-junction',
                'name' => 'Biryani Junction',
                'description' => 'Slow-cooked dum biryanis.',
                'address' => '21, Sakchi Road, Adityapur, Jamshedpur',
                'phone' => '9204885005',
                'lat' => 22.7875, 'lng' => 86.1610,
                'cuisines' => ['Biryani', 'Hyderabadi'],
                'primary_cuisine' => 'Biryani',
                'pure_veg' => false,
                'delivery_time' => 40,
                'rating' => 4.6, 'total_ratings' => 489,
                'banner' => $this->img('biryani-junction-banner'),
                'logo' => $this->img('biryani-junction-logo', 200, 200),
                'menu' => [
                    'Biryanis' => [
                        ['name' => 'Hyderabadi Chicken Biryani', 'description' => 'Fragrant basmati, slow-cooked with chicken.', 'price' => 260, 'is_veg' => false, 'image' => $this->img('chicken-biryani'), 'bestseller' => true, 'rating' => 4.8, 'orders' => 733],
                        ['name' => 'Mutton Biryani', 'description' => 'Tender mutton, aromatic dum-cooked rice.', 'price' => 340, 'is_veg' => false, 'image' => $this->img('mutton-biryani'), 'recommended' => true, 'rating' => 4.7],
                        ['name' => 'Veg Dum Biryani', 'description' => 'Mixed vegetables & basmati rice.', 'price' => 210, 'is_veg' => true, 'image' => $this->img('veg-biryani'), 'rating' => 4.3],
                        ['name' => 'Egg Biryani', 'description' => 'Boiled eggs tossed in spiced rice.', 'price' => 200, 'is_veg' => false, 'image' => $this->img('egg-biryani'), 'rating' => 4.4],
                    ],
                    'Sides' => [
                        ['name' => 'Mirchi Ka Salan', 'description' => 'Tangy peanut & chilli curry.', 'price' => 90, 'is_veg' => true, 'image' => $this->img('mirchi-salan'), 'rating' => 4.2],
                        ['name' => 'Raita', 'description' => 'Cooling spiced yogurt dip.', 'price' => 50, 'is_veg' => true, 'image' => $this->img('raita'), 'rating' => 4.1],
                    ],
                ],
            ],
            [
                'slug' => 'sweet-treats-cafe',
                'name' => 'Sweet Treats & Cafe',
                'description' => 'Desserts, shakes & cafe bites.',
                'address' => '5, Circle Road, Adityapur, Jamshedpur',
                'phone' => '9204885006',
                'lat' => 22.7920, 'lng' => 86.1680,
                'cuisines' => ['Desserts', 'Beverages'],
                'primary_cuisine' => 'Desserts',
                'pure_veg' => true,
                'delivery_time' => 20,
                'rating' => 4.2, 'total_ratings' => 176,
                'banner' => $this->img('sweet-treats-banner'),
                'logo' => $this->img('sweet-treats-logo', 200, 200),
                'menu' => [
                    'Desserts' => [
                        ['name' => 'Chocolate Brownie', 'description' => 'Warm fudgy brownie with walnuts.', 'price' => 130, 'is_veg' => true, 'image' => $this->img('brownie'), 'bestseller' => true, 'rating' => 4.6],
                        ['name' => 'Gulab Jamun (2 pcs)', 'description' => 'Soft milk dumplings in sugar syrup.', 'price' => 80, 'is_veg' => true, 'image' => $this->img('gulab-jamun'), 'rating' => 4.5],
                        ['name' => 'Red Velvet Pastry', 'description' => 'Classic red velvet with cream cheese.', 'price' => 110, 'is_veg' => true, 'image' => $this->img('red-velvet'), 'recommended' => true, 'rating' => 4.4],
                    ],
                    'Beverages' => [
                        ['name' => 'Cold Coffee', 'description' => 'Blended chilled coffee with ice cream.', 'price' => 120, 'is_veg' => true, 'image' => $this->img('cold-coffee'), 'bestseller' => true, 'rating' => 4.5],
                        ['name' => 'Mango Smoothie', 'description' => 'Fresh mango blended smoothie.', 'price' => 110, 'is_veg' => true, 'image' => $this->img('mango-smoothie'), 'rating' => 4.3],
                        ['name' => 'Oreo Milkshake', 'description' => 'Creamy milkshake with Oreo crumbs.', 'price' => 140, 'is_veg' => true, 'image' => $this->img('oreo-shake'), 'rating' => 4.6],
                    ],
                ],
            ],
        ];
    }
}
