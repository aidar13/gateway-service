<?php

declare(strict_types=1);

namespace App\Commands\Auth;

use App\Contracts\Command;
use App\DTO\RegisterUserDataDTO;
use App\Models\User;

/** @implements Command<User> */
final readonly class RegisterUserCommand implements Command
{
    public function __construct(
        public RegisterUserDataDTO $data,
    ) {
    }
}
