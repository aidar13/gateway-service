<?php

declare(strict_types=1);

namespace App\Handlers\Gateway;

use App\Commands\Gateway\RegisterServiceCommand;
use App\Contracts\ServiceRepository;
use App\Models\Microservice;

final readonly class RegisterServiceHandler
{
    public function __construct(private ServiceRepository $services)
    {
    }

    public function handle(RegisterServiceCommand $command): Microservice
    {
        return $this->services->create($command->name, $command->baseUrl, $command->openApiPath);
    }
}
