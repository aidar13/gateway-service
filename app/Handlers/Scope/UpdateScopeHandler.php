<?php

declare(strict_types=1);

namespace App\Handlers\Scope;

use App\Commands\Scope\UpdateScopeCommand;
use App\Contracts\ScopeRepository;
use App\Models\AccessScope;

final readonly class UpdateScopeHandler
{
    public function __construct(
        private ScopeRepository $scopes,
    ) {
    }

    public function handle(UpdateScopeCommand $command): AccessScope
    {
        return $this->scopes->update($this->scopes->get($command->scopeId), $command->changes);
    }
}
