<?php

declare(strict_types=1);

namespace App\Handlers\Access;

use App\Commands\Access\CreatePermissionCommand;
use App\Contracts\PermissionRepository;
use Spatie\Permission\Models\Permission;

final readonly class CreatePermissionHandler
{
    public function __construct(
        private PermissionRepository $permissions,
    ) {
    }

    public function handle(CreatePermissionCommand $command): Permission
    {
        return $this->permissions->create($command->name);
    }
}
