<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AccessScope;
use App\Models\Microservice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

final class DatabaseSeeder extends Seeder
{
    public function run(ClientRepository $clients): void
    {
        $this->call(RoleSeeder::class);

        $admin = User::query()->firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrator', 'email' => 'admin@gateway.example', 'password' => (string)config('gateway.seed.admin_password')],
        );
        $admin->syncRoles(['admin']);

        /** @var array<string, string> $services */
        $services = config('gateway.seed.services');
        foreach ($services as $name => $url) {
            Microservice::query()->firstOrCreate(['name' => $name], ['base_url' => $url]);
        }

        // First-party OAuth client for the password and hash_and_code grants.
        $client = Client::query()->where('password_client', true)->first();
        if ($client === null) {
            $client = $clients->createPasswordGrantClient(null, 'web', 'http://localhost');
            $client->forceFill(['secret' => (string)config('gateway.seed.client_secret')])->save();
        }

        // Default scope: the first-party application may use every seeded service.
        $scope = AccessScope::query()->firstOrCreate(['name' => 'web'], ['description' => 'First-party applications']);
        $scope->services()->syncWithoutDetaching(
            Microservice::query()->whereIn('name', array_keys($services))->pluck('id')->all(),
        );
        $scope->clients()->syncWithoutDetaching([$client->getKey()]);

        $this->command->info("OAuth client_id: {$client->getKey()} (secret: SEED_CLIENT_SECRET)");
    }
}
