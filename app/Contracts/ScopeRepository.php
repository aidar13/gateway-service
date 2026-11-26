<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\ResourceNotFound;
use App\Models\AccessScope;
use App\Models\Microservice;
use App\Models\ServiceRoute;
use Illuminate\Database\Eloquent\Collection;

interface ScopeRepository
{
    /** @return Collection<int, AccessScope> */
    public function all(): Collection;

    /**
     * With bound services and routes.
     *
     * @throws ResourceNotFound
     */
    public function get(int $id): AccessScope;

    public function create(string $name, ?string $description, bool $isActive): AccessScope;

    /** @param array{description?: string|null, is_active?: bool} $changes */
    public function update(AccessScope $scope, array $changes): AccessScope;

    public function delete(AccessScope $scope): void;

    public function bindService(AccessScope $scope, Microservice $service): void;

    public function unbindService(AccessScope $scope, Microservice $service): void;

    public function bindRoute(AccessScope $scope, ServiceRoute $route): void;

    public function unbindRoute(AccessScope $scope, ServiceRoute $route): void;

    /** @return array<string, string> active scope name => description, as OAuth advertises them */
    public function activeDescriptions(): array;

    /**
     * Is there an active scope of this client, among the requested ones, that covers the route?
     *
     * @param list<string> $requested scopes the token was narrowed to; empty = all client scopes
     */
    public function grants(int $clientId, array $requested, int $serviceId, int $routeId): bool;
}
