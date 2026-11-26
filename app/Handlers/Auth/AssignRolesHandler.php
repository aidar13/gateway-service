<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Commands\Auth\AssignRolesCommand;
use App\Contracts\UserRepository;
use App\Exceptions\ResourceNotFound;
use App\Models\User;

final class AssignRolesHandler
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(AssignRolesCommand $command): User
    {
        $user = $this->users->find($command->userId) ?? throw ResourceNotFound::make('User');
        $user->syncRoles($command->roles);

        return $user;
    }
}
