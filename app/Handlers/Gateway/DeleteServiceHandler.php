<?php

declare(strict_types=1);

namespace App\Handlers\Gateway;

use App\Commands\Gateway\DeleteServiceCommand;
use App\Contracts\ServiceRepository;

final readonly class DeleteServiceHandler
{
    public function __construct(
        private ServiceRepository $services,
    ) {
    }

    public function handle(DeleteServiceCommand $command): void
    {
        $this->services->delete($this->services->get($command->serviceId));
    }
}
