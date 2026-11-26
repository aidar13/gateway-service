<?php

declare(strict_types=1);

namespace App\Handlers\Access;

use App\Commands\Access\AssignRolesCommand;
use App\Contracts\UserRepository;
use App\Enums\SystemRole;
use App\Exceptions\ProtectedResource;
use App\Exceptions\ResourceNotFound;
use App\Models\User;

final readonly class AssignRolesHandler
{
    public function __construct(
        private UserRepository $users,
    ) {
    }

    public function handle(AssignRolesCommand $command): User
    {
        $user  = $this->users->find($command->userId) ?? throw ResourceNotFound::make('User');
        $admin = SystemRole::Admin->value;

        if ($user->hasRole($admin) && !in_array($admin, $command->roles, true)
            && $this->users->countActiveWithRole($admin) <= 1) {
            throw ProtectedResource::because('The last active administrator cannot lose the admin role.');
        }

        $user->syncRoles($command->roles);

        return $user;
    }
}
