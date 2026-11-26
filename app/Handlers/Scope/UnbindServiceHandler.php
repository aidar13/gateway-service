<?php

declare(strict_types=1);

namespace App\Handlers\Scope;

use App\Commands\Scope\UnbindServiceCommand;
use App\Contracts\ScopeRepository;
use App\Contracts\ServiceRepository;
use App\Models\AccessScope;

final readonly class UnbindServiceHandler
{
    public function __construct(
        private ScopeRepository $scopes,
        private ServiceRepository $services,
    ) {
    }

    public function handle(UnbindServiceCommand $command): AccessScope
    {
        $scope = $this->scopes->get($command->scopeId);
        $this->scopes->unbindService($scope, $this->services->get($command->serviceId));

        return $this->scopes->get($scope->id);
    }
}
