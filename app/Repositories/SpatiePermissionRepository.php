<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\PermissionRepository;
use App\Exceptions\ResourceNotFound;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

final class SpatiePermissionRepository implements PermissionRepository
{
    private const GUARD = 'api';

    public function all(): Collection
    {
        return Permission::query()->where('guard_name', self::GUARD)->orderBy('name')->get();
    }

    public function get(int $id): Permission
    {
        return Permission::query()->where('guard_name', self::GUARD)->find($id)
            ?? throw ResourceNotFound::make('Permission');
    }

    public function create(string $name): Permission
    {
        $permission = Permission::create(['name' => $name, 'guard_name' => self::GUARD]);

        return $this->get((int) $permission->getKey());
    }

    public function rename(Permission $permission, string $name): Permission
    {
        $permission->update(['name' => $name]);

        return $permission;
    }

    public function delete(Permission $permission): void
    {
        $permission->delete();
    }
}
