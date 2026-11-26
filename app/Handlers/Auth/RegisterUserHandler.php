<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Commands\Auth\RegisterUserCommand;
use App\Contracts\UserRepository;
use App\Models\User;

final readonly class RegisterUserHandler
{
    public function __construct(private UserRepository $users)
    {
    }

    public function handle(RegisterUserCommand $command): User
    {
        $data = $command->data;

        $user = $this->users->create($data);
        $user->assignRole('client');

        return $user;
    }
}
