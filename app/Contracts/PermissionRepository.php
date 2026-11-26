<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\ResourceNotFound;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

interface PermissionRepository
{
    /** @return Collection<int, Permission> */
    public function all(): Collection;

    /** @throws ResourceNotFound */
    public function get(int $id): Permission;

    public function create(string $name): Permission;

    public function rename(Permission $permission, string $name): Permission;

    public function delete(Permission $permission): void;
}
