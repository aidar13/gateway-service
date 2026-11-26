<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AccessScope;
use App\Models\Microservice;
use Database\Seeders\Concerns\CreatesPasswordClient;
use Illuminate\Database\Seeder;

/** A second application with a narrower scope: it reaches the order service and nothing else. */
final class DemoClientSeeder extends Seeder
{
    use CreatesPasswordClient;

    public function run(): void
    {
        $client = $this->passwordClient('mobile', (string)config('gateway.seed.mobile_client_secret'));
        $scope  = AccessScope::query()->firstOrCreate(['name' => 'orders'], ['description' => 'Order service only']);

        if ($scope->wasRecentlyCreated) {
            $scope->services()->attach(Microservice::query()->where('name', 'orders')->pluck('id')->all());
            $scope->clients()->attach($client->getKey());
        }

        $this->command->info("OAuth client 'mobile': id {$client->getKey()} (secret: SEED_MOBILE_CLIENT_SECRET)");
    }
}
