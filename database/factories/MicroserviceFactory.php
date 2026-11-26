<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Microservice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Microservice> */
class MicroserviceFactory extends Factory
{
    protected $model = Microservice::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'base_url' => 'http://'.fake()->unique()->domainWord().'.internal',
            'openapi_path' => '/api/openapi.json',
            'is_active' => true,
        ];
    }
}
