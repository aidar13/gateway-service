<?php

declare(strict_types=1);

namespace App\Commands\Access;

use App\Contracts\Command;
use App\Models\User;

/** @implements Command<User> */
final readonly class AssignRolesCommand implements Command
{
    /** @param list<string> $roles */
    public function __construct(
        public int $userId,
        public array $roles,
    ) {
    }
}
