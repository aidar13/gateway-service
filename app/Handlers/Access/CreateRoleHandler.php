<?php

declare(strict_types=1);

namespace App\Handlers\Access;

use App\Commands\Access\CreateRoleCommand;
use App\Contracts\RoleRepository;
use Spatie\Permission\Models\Role;

final class CreateRoleHandler
{
    public function __construct(
        private readonly RoleRepository $roles,
    ) {}

    public function handle(CreateRoleCommand $command): Role
    {
        return $this->roles->create($command->name, $command->permissions);
    }
}
