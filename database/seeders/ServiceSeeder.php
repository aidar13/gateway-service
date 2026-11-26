<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Microservice;
use Illuminate\Database\Seeder;

/**
 * Registers the services listed in `gateway.seed.services`. Their routes are not seeded:
 * `php artisan gateway:import-routes` reads them from each service's OpenAPI document.
 */
final class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, string> $services */
        $services = config('gateway.seed.services');

        foreach ($services as $name => $url) {
            Microservice::query()->firstOrCreate(['name' => $name], ['base_url' => $url]);
        }
    }
}
