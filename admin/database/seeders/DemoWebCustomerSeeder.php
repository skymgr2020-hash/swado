<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local-dev-only seeder: creates one customer account with a known
 * email/password so the new customer website (web/) can exercise the
 * real login -> cart -> checkout flow. Phone-OTP signup is unusable in
 * this environment because no SMS provider (Twilio/MSG91/Exotel) is
 * configured, so this is the only way to get a working login for testing.
 */
class DemoWebCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo.customer@swado.test'],
            [
                'name' => 'Demo Customer',
                'phone' => '9999900001',
                'password' => Hash::make('DemoPass123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('customer')) {
            $user->assignRole('customer');
        }

        $this->command->info('Demo customer ready: demo.customer@swado.test / DemoPass123!');
    }
}
