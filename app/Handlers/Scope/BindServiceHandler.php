<?php

declare(strict_types=1);

namespace App\Handlers\Scope;

use App\Commands\Scope\BindServiceCommand;
use App\Contracts\ScopeRepository;
use App\Contracts\ServiceRepository;
use App\Models\AccessScope;

final class BindServiceHandler
{
    public function __construct(
        private readonly ScopeRepository $scopes,
        private readonly ServiceRepository $services,
    ) {}

    public function handle(BindServiceCommand $command): AccessScope
    {
        $scope = $this->scopes->get($command->scopeId);
        $this->scopes->bindService($scope, $this->services->get($command->serviceId));

        return $this->scopes->get($scope->id);
    }
}
