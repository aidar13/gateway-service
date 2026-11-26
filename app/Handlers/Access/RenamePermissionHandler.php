<?php

declare(strict_types=1);

namespace App\Handlers\Access;

use App\Commands\Access\RenamePermissionCommand;
use App\Contracts\PermissionRepository;
use App\Enums\SystemPermission;
use App\Exceptions\ProtectedResource;
use Spatie\Permission\Models\Permission;

final class RenamePermissionHandler
{
    public function __construct(
        private readonly PermissionRepository $permissions,
    ) {}

    public function handle(RenamePermissionCommand $command): Permission
    {
        $permission = $this->permissions->get($command->permissionId);

        if (SystemPermission::tryFrom($permission->name) !== null) {
            throw ProtectedResource::because("System permission '{$permission->name}' cannot be changed.");
        }

        return $this->permissions->rename($permission, $command->name);
    }
}
