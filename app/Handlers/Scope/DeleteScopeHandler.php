<?php

declare(strict_types=1);

namespace App\Handlers\Scope;

use App\Commands\Scope\DeleteScopeCommand;
use App\Contracts\ScopeRepository;

final readonly class DeleteScopeHandler
{
    public function __construct(
        private ScopeRepository $scopes,
    ) {
    }

    public function handle(DeleteScopeCommand $command): void
    {
        $this->scopes->delete($this->scopes->get($command->scopeId));
    }
}
