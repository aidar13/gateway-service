<?php

declare(strict_types=1);

namespace App\Commands\Auth;

use App\Contracts\Command;
use App\Models\User;

/** @implements Command<null> */
final readonly class LogoutCommand implements Command
{
    public function __construct(
        public User $user,
    ) {}
}
