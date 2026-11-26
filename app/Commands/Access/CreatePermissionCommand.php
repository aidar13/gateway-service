<?php

declare(strict_types=1);

namespace App\Commands\Access;

use App\Contracts\Command;
use Spatie\Permission\Models\Permission;

/** @implements Command<Permission> */
final readonly class CreatePermissionCommand implements Command
{
    public function __construct(
        public string $name,
    ) {
    }
}
