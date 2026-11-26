<?php

declare(strict_types=1);

namespace App\Commands\Scope;

use App\Contracts\Command;
use App\Models\AccessScope;

/** @implements Command<AccessScope> */
final readonly class CreateScopeCommand implements Command
{
    public function __construct(
        public string $name,
        public ?string $description,
        public bool $isActive,
    ) {}
}
