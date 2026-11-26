<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ScopeRepository;
use App\Exceptions\ResourceNotFound;
use App\Models\AccessScope;
use App\Models\Microservice;
use App\Models\ServiceRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class EloquentScopeRepository implements ScopeRepository
{
    public function all(): Collection
    {
        return AccessScope::query()->withCount(['services', 'routes', 'clients'])->orderBy('name')->get();
    }

    public function get(int $id): AccessScope
    {
        return AccessScope::query()->with(['services', 'routes'])->find($id)
            ?? throw ResourceNotFound::make('Scope');
    }

    public function create(string $name, ?string $description, bool $isActive): AccessScope
    {
        $scope = AccessScope::query()->create(['name' => $name, 'description' => $description, 'is_active' => $isActive]);

        return $this->get($scope->id);
    }

    public function update(AccessScope $scope, array $changes): AccessScope
    {
        $scope->update($changes);

        return $scope;
    }

    public function delete(AccessScope $scope): void
    {
        $scope->delete();
    }

    public function bindService(AccessScope $scope, Microservice $service): void
    {
        $scope->services()->syncWithoutDetaching([$service->id]);
    }

    public function unbindService(AccessScope $scope, Microservice $service): void
    {
        $scope->services()->detach($service->id);
    }

    public function bindRoute(AccessScope $scope, ServiceRoute $route): void
    {
        $scope->routes()->syncWithoutDetaching([$route->id]);
    }

    public function unbindRoute(AccessScope $scope, ServiceRoute $route): void
    {
        $scope->routes()->detach($route->id);
    }

    public function activeDescriptions(): array
    {
        $descriptions = [];

        foreach (AccessScope::query()->where('is_active', true)->get() as $scope) {
            $descriptions[$scope->name] = $scope->description ?? $scope->name;
        }

        return $descriptions;
    }

    public function grants(int $clientId, array $requested, int $serviceId, int $routeId): bool
    {
        return AccessScope::query()
            ->where('is_active', true)
            ->when($requested !== [], fn (Builder $q) => $q->whereIn('name', $requested))
            ->whereHas('clients', fn (Builder $q) => $q->whereKey($clientId))
            ->where(fn (Builder $q) => $q
                ->whereHas('services', fn (Builder $s) => $s->whereKey($serviceId))
                ->orWhereHas('routes', fn (Builder $r) => $r->whereKey($routeId)))
            ->exists();
    }
}
