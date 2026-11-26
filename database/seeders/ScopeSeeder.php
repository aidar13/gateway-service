<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AccessScope;
use App\Models\Microservice;
use App\Models\OAuthClient;
use Illuminate\Database\Seeder;

/** Default scope: the first-party application may use every seeded service. */
final class ScopeSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, string> $services */
        $services = config('gateway.seed.services');

        $scope = AccessScope::query()->firstOrCreate(['name' => 'web'], ['description' => 'First-party applications']);
        $scope->services()->syncWithoutDetaching(
            Microservice::query()->whereIn('name', array_keys($services))->pluck('id')->all(),
        );
        $scope->clients()->syncWithoutDetaching(
            OAuthClient::query()->where('name', ClientSeeder::NAME)->pluck('id')->all(),
        );
    }
}
