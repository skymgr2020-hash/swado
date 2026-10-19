<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'admin', 'accountant'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // Local admin account so the app is usable before the identity sync runs.
        User::updateOrCreate(
            ['email' => 'accountant@swado.local'],
            ['name' => 'Accountant', 'phone' => '9000000010', 'password' => Hash::make('password'), 'is_active' => true]
        )->syncRoles(['accountant']);

        $this->command?->info('Accounts seeded. Login: accountant@swado.local / password');
    }
}
