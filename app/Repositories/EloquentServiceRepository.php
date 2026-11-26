<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ServiceRepository;
use App\Gateway\RouteDefinition;
use App\Models\Microservice;
use App\Models\ServiceRoute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentServiceRepository implements ServiceRepository
{
    public function create(string $name, string $baseUrl, string $openApiPath): Microservice
    {
        return Microservice::query()->create([
            'name' => $name,
            'base_url' => $baseUrl,
            'openapi_path' => $openApiPath,
            'is_active' => true,
        ]);
    }

    public function find(int $id): ?Microservice
    {
        return Microservice::query()->with('routes')->find($id);
    }

    public function findActiveByName(string $name): ?Microservice
    {
        return Microservice::query()->with('routes')->where('name', $name)->where('is_active', true)->first();
    }

    public function all(): Collection
    {
        return Microservice::query()->withCount('routes')->orderBy('name')->get();
    }

    public function replaceRoutes(Microservice $service, array $routes): void
    {
        DB::transaction(function () use ($service, $routes): void {
            $service->routes()->delete();

            foreach ($routes as $route) {
                $service->routes()->create([
                    'method' => $route->method,
                    'path' => $route->path,
                    'requires_auth' => $route->requiresAuth,
                    'permission' => $route->permission,
                    'summary' => $route->summary,
                ]);
            }
        });

        $service->load('routes');
    }

    public function routeDefinitions(Microservice $service): array
    {
        return array_values($service->routes->map(static fn (ServiceRoute $r): RouteDefinition => new RouteDefinition(
            $r->method,
            $r->path,
            $r->requires_auth,
            $r->permission,
            $r->summary,
        ))->all());
    }
}
