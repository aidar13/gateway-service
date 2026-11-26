<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RoleRepository;
use App\Exceptions\ResourceNotFound;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

final class SpatieRoleRepository implements RoleRepository
{
    private const GUARD = 'api';

    public function all(): Collection
    {
        return Role::query()->where('guard_name', self::GUARD)->with('permissions')->orderBy('name')->get();
    }

    public function get(int $id): Role
    {
        return Role::query()->where('guard_name', self::GUARD)->with('permissions')->find($id)
            ?? throw ResourceNotFound::make('Role');
    }

    public function create(string $name, array $permissions): Role
    {
        $role = Role::create(['name' => $name, 'guard_name' => self::GUARD]);
        $role->syncPermissions($permissions);

        return $this->get((int)$role->getKey());
    }

    public function update(Role $role, ?string $name, ?array $permissions): Role
    {
        if ($name !== null) {
            $role->update(['name' => $name]);
        }

        if ($permissions !== null) {
            $role->syncPermissions($permissions);
        }

        return $this->get((int)$role->getKey());
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
