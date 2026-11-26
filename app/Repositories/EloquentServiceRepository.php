<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ServiceRepository;
use App\Exceptions\ResourceNotFound;
use App\Gateway\RouteDefinition;
use App\Models\Microservice;
use App\Models\ServiceRoute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentServiceRepository implements ServiceRepository
{
    public function create(string $name, string $baseUrl, string $openApiPath): Microservice
    {
        return Microservice::query()->create([
            'name'         => $name,
            'base_url'     => $baseUrl,
            'openapi_path' => $openApiPath,
            'is_active'    => true,
        ]);
    }

    public function get(int $id): Microservice
    {
        return Microservice::query()->with('routes')->find($id) ?? throw ResourceNotFound::make('Service');
    }

    public function update(Microservice $service, array $changes): Microservice
    {
        $service->update($changes);

        return $service;
    }

    public function delete(Microservice $service): void
    {
        $service->delete();
    }

    public function getRoute(int $id): ServiceRoute
    {
        return ServiceRoute::query()->find($id) ?? throw ResourceNotFound::make('Route');
    }

    public function paginateRoutes(?int $serviceId, ?string $search, int $perPage): LengthAwarePaginator
    {
        return ServiceRoute::query()
            ->when($serviceId, fn($q, int $id) => $q->where('microservice_id', $id))
            ->when($search, fn($q, string $term) => $q->where('path', 'like', '%' . $term . '%'))
            ->orderBy('microservice_id')
            ->orderBy('path')
            ->orderBy('method')
            ->paginate($perPage);
    }

    public function findActiveByName(string $name): ?Microservice
    {
        return Microservice::query()->with('routes')->where('name', $name)->where('is_active', true)->first();
    }

    public function all(): Collection
    {
        return Microservice::query()->withCount('routes')->orderBy('name')->get();
    }

    public function syncRoutes(Microservice $service, array $routes): void
    {
        DB::transaction(function () use ($service, $routes): void {
            $kept = [];

            foreach ($routes as $route) {
                $kept[] = $service->routes()->updateOrCreate(
                    ['method' => $route->method, 'path' => $route->path],
                    [
                        'requires_auth' => $route->requiresAuth,
                        'permission'    => $route->permission,
                        'summary'       => $route->summary,
                    ],
                )->getKey();
            }

            $service->routes()->whereNotIn('id', $kept)->delete();
        });

        $service->load('routes');
    }

    public function routeDefinitions(Microservice $service): array
    {
        return array_values($service->routes->map(static fn(ServiceRoute $r): RouteDefinition => new RouteDefinition(
            $r->method,
            $r->path,
            $r->requires_auth,
            $r->permission,
            $r->summary,
            $r->id,
        ))->all());
    }
}
