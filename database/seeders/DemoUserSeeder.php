<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/** One account per situation worth trying by hand. Phone numbers and addresses are fictional. */
final class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string)config('gateway.seed.user_password');
        $client   = SystemRole::Client->value;

        $this->user(['username' => 'manager', 'name' => 'Service Manager', 'email' => 'manager@gateway.example', 'password' => $password], 'service-manager');
        $this->user(['username' => 'security', 'name' => 'Access Manager', 'email' => 'security@gateway.example', 'password' => $password], 'access-manager');
        $this->user(['username' => 'auditor', 'name' => 'Auditor', 'email' => 'auditor@gateway.example', 'password' => $password], 'auditor');
        $this->user(['username' => 'client', 'name' => 'Demo Client', 'email' => 'client@gateway.example', 'phone' => '+15550000001', 'password' => $password], $client);
        // Signs in with a one-time code only.
        $this->user(['name' => 'Phone Only', 'phone' => '+15550000002'], $client);
        $this->user(['username' => 'blocked', 'name' => 'Blocked Client', 'email' => 'blocked@gateway.example', 'password' => $password, 'is_active' => false], $client);
    }

    /** @param array{name: string, username?: string, email?: string, phone?: string, password?: string, is_active?: bool} $attributes */
    private function user(array $attributes, string $role): void
    {
        $column = isset($attributes['username']) ? 'username' : 'phone';

        if (User::query()->where($column, $attributes[$column] ?? null)->exists()) {
            return;
        }

        $user = new User(['is_active' => true, ...$attributes]);
        // Contacts count as verified, so a code sign-in keeps the password.
        $user->forceFill([
            'email_verified_at' => isset($attributes['email']) ? now() : null,
            'phone_verified_at' => isset($attributes['phone']) ? now() : null,
        ])->save();
        $user->assignRole($role);
    }
}
