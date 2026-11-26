<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Seeder;

final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstWhere('username', 'admin');

        if ($admin === null) {
            $admin = new User([
                'name'      => 'Administrator',
                'username'  => 'admin',
                'email'     => 'admin@gateway.example',
                'password'  => (string)config('gateway.seed.admin_password'),
                'is_active' => true,
            ]);
            // Verified, otherwise the first code sign-in by e-mail would claim the account and drop the password.
            $admin->forceFill(['email_verified_at' => now()])->save();
        }

        $admin->assignRole(SystemRole::Admin->value);
    }
}
