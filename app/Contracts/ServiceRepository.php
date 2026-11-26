<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\ResourceNotFound;
use App\Gateway\RouteDefinition;
use App\Models\Microservice;
use App\Models\ServiceRoute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ServiceRepository
{
    public function create(string $name, string $baseUrl, string $openApiPath): Microservice;

    /**
     * With routes loaded.
     *
     * @throws ResourceNotFound
     */
    public function get(int $id): Microservice;

    /** @param  array{base_url?: string, openapi_path?: string, is_active?: bool}  $changes */
    public function update(Microservice $service, array $changes): Microservice;

    public function delete(Microservice $service): void;

    /** @throws ResourceNotFound */
    public function getRoute(int $id): ServiceRoute;

    /** @return LengthAwarePaginator<int, ServiceRoute> */
    public function paginateRoutes(?int $serviceId, ?string $search, int $perPage): LengthAwarePaginator;

    /** Active service with its routes loaded. */
    public function findActiveByName(string $name): ?Microservice;

    /** @return Collection<int, Microservice> */
    public function all(): Collection;

    /**
     * Makes the stored routes equal to the given list. Routes that stay keep their id (and scope bindings).
     *
     * @param  list<RouteDefinition>  $routes
     */
    public function syncRoutes(Microservice $service, array $routes): void;

    /** @return list<RouteDefinition> */
    public function routeDefinitions(Microservice $service): array;
}
