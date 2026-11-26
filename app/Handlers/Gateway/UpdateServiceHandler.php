<?php

declare(strict_types=1);

namespace App\Handlers\Gateway;

use App\Commands\Gateway\UpdateServiceCommand;
use App\Contracts\ServiceRepository;
use App\Models\Microservice;

final class UpdateServiceHandler
{
    public function __construct(
        private readonly ServiceRepository $services,
    ) {}

    public function handle(UpdateServiceCommand $command): Microservice
    {
        return $this->services->update($this->services->get($command->serviceId), $command->changes);
    }
}
