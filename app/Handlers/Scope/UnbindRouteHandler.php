<?php

declare(strict_types=1);

namespace App\Handlers\Scope;

use App\Commands\Scope\UnbindRouteCommand;
use App\Contracts\ScopeRepository;
use App\Contracts\ServiceRepository;
use App\Models\AccessScope;

final readonly class UnbindRouteHandler
{
    public function __construct(
        private ScopeRepository $scopes,
        private ServiceRepository $services,
    ) {
    }

    public function handle(UnbindRouteCommand $command): AccessScope
    {
        $scope = $this->scopes->get($command->scopeId);
        $this->scopes->unbindRoute($scope, $this->services->getRoute($command->routeId));

        return $this->scopes->get($scope->id);
    }
}
