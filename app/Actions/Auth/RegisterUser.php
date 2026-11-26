<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\UserRepository;
use App\DTO\RegisterUserData;
use App\Models\User;

final class RegisterUser
{
    public function __construct(private readonly UserRepository $users) {}

    public function execute(RegisterUserData $data): User
    {
        $user = $this->users->create($data);
        $user->assignRole('client');

        return $user;
    }
}
