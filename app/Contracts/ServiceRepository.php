<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Gateway\RouteDefinition;
use App\Models\Microservice;
use Illuminate\Database\Eloquent\Collection;

interface ServiceRepository
{
    public function create(string $name, string $baseUrl, string $openApiPath): Microservice;

    public function find(int $id): ?Microservice;

    /** Active service with its routes loaded. */
    public function findActiveByName(string $name): ?Microservice;

    /** @return Collection<int, Microservice> */
    public function all(): Collection;

    /**
     * Makes the stored routes equal to the given list.
     *
     * @param  list<RouteDefinition>  $routes
     */
    public function replaceRoutes(Microservice $service, array $routes): void;

    /** @return list<RouteDefinition> */
    public function routeDefinitions(Microservice $service): array;
}
