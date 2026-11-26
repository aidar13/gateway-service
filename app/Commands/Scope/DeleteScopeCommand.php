<?php

declare(strict_types=1);

namespace App\Commands\Scope;

use App\Contracts\Command;

/** @implements Command<null> */
final readonly class DeleteScopeCommand implements Command
{
    public function __construct(
        public int $scopeId,
    ) {}
}
