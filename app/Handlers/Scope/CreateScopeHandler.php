<?php

declare(strict_types=1);

namespace App\Handlers\Scope;

use App\Commands\Scope\CreateScopeCommand;
use App\Contracts\ScopeRepository;
use App\Models\AccessScope;

final readonly class CreateScopeHandler
{
    public function __construct(
        private ScopeRepository $scopes,
    ) {
    }

    public function handle(CreateScopeCommand $command): AccessScope
    {
        return $this->scopes->create($command->name, $command->description, $command->isActive);
    }
}
