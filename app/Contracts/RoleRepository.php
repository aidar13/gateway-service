<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\ResourceNotFound;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

interface RoleRepository
{
    /** @return Collection<int, Role> with permissions */
    public function all(): Collection;

    /** @throws ResourceNotFound */
    public function get(int $id): Role;

    /** @param list<string> $permissions */
    public function create(string $name, array $permissions): Role;

    /** @param list<string>|null $permissions null keeps the current set */
    public function update(Role $role, ?string $name, ?array $permissions): Role;

    public function delete(Role $role): void;
}
