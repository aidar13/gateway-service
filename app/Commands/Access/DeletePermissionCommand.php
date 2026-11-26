<?php

declare(strict_types=1);

namespace App\Commands\Access;

use App\Contracts\Command;

/** @implements Command<null> */
final readonly class DeletePermissionCommand implements Command
{
    public function __construct(
        public int $permissionId,
    ) {
    }
}
