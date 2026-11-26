<?php

declare(strict_types=1);

namespace App\Commands\Gateway;

use App\Contracts\Command;
use App\Models\Microservice;

/** @implements Command<Microservice> */
final readonly class UpdateServiceCommand implements Command
{
    /** @param array{base_url?: string, openapi_path?: string, is_active?: bool} $changes */
    public function __construct(
        public int $serviceId,
        public array $changes,
    ) {
    }
}
