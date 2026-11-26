<?php

declare(strict_types=1);

namespace App\Handlers\Access;

use App\Commands\Access\UpdateRoleCommand;
use App\Contracts\RoleRepository;
use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Exceptions\ProtectedResource;
use Spatie\Permission\Models\Role;

final class UpdateRoleHandler
{
    public function __construct(
        private readonly RoleRepository $roles,
    ) {}

    public function handle(UpdateRoleCommand $command): Role
    {
        $role = $this->roles->get($command->roleId);
        $system = SystemRole::tryFrom($role->name);

        if ($system !== null && $command->name !== null && $command->name !== $role->name) {
            throw ProtectedResource::because("System role '{$role->name}' cannot be renamed.");
        }

        // Otherwise an administrator could lock everybody out of the admin API.
        if ($system === SystemRole::Admin && $command->permissions !== null
            && array_diff(SystemPermission::values(), $command->permissions) !== []) {
            throw ProtectedResource::because('The admin role must keep every system permission.');
        }

        return $this->roles->update($role, $command->name, $command->permissions);
    }
}
