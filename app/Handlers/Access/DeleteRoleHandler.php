<?php

declare(strict_types=1);

namespace App\Handlers\Access;

use App\Commands\Access\DeleteRoleCommand;
use App\Contracts\RoleRepository;
use App\Enums\SystemRole;
use App\Exceptions\ProtectedResource;

final readonly class DeleteRoleHandler
{
    public function __construct(
        private RoleRepository $roles,
    ) {
    }

    public function handle(DeleteRoleCommand $command): void
    {
        $role = $this->roles->get($command->roleId);

        if (SystemRole::tryFrom($role->name) !== null) {
            throw ProtectedResource::because("System role '{$role->name}' cannot be deleted.");
        }

        $this->roles->delete($role);
    }
}
