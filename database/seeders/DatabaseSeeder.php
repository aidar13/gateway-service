<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Every seeder can be run again: nothing is duplicated and manual changes are kept. */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // What the platform cannot work without.
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
            ServiceSeeder::class,
            ClientSeeder::class,
            ScopeSeeder::class,
        ]);

        // Accounts with well-known passwords: never outside a developer machine and tests.
        if (app()->environment('local', 'testing')) {
            $this->call([
                DemoRoleSeeder::class,
                DemoUserSeeder::class,
                DemoClientSeeder::class,
            ]);
        }
    }
}
