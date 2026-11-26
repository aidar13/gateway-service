<?php

declare(strict_types=1);

namespace App\Handlers\Scope;

use App\Commands\Scope\BindRouteCommand;
use App\Contracts\ScopeRepository;
use App\Contracts\ServiceRepository;
use App\Models\AccessScope;

final readonly class BindRouteHandler
{
    public function __construct(
        private ScopeRepository $scopes,
        private ServiceRepository $services,
    ) {
    }

    public function handle(BindRouteCommand $command): AccessScope
    {
        $scope = $this->scopes->get($command->scopeId);
        $this->scopes->bindRoute($scope, $this->services->getRoute($command->routeId));

        return $this->scopes->get($scope->id);
    }
}
