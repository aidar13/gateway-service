<?php

declare(strict_types=1);

namespace App\Commands\Access;

use App\Contracts\Command;
use Spatie\Permission\Models\Role;

/** @implements Command<Role> */
final readonly class CreateRoleCommand implements Command
{
    /** @param list<string> $permissions */
    public function __construct(
        public string $name,
        public array $permissions,
    ) {
    }
}
