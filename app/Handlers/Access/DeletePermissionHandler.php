<?php

declare(strict_types=1);

namespace App\Handlers\Access;

use App\Commands\Access\DeletePermissionCommand;
use App\Contracts\PermissionRepository;
use App\Enums\SystemPermission;
use App\Exceptions\ProtectedResource;

final class DeletePermissionHandler
{
    public function __construct(
        private readonly PermissionRepository $permissions,
    ) {}

    public function handle(DeletePermissionCommand $command): void
    {
        $permission = $this->permissions->get($command->permissionId);

        if (SystemPermission::tryFrom($permission->name) !== null) {
            throw ProtectedResource::because("System permission '{$permission->name}' cannot be deleted.");
        }

        $this->permissions->delete($permission);
    }
}
