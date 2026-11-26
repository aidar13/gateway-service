<?php

declare(strict_types=1);

namespace App\Commands\Gateway;

use App\Contracts\Command;
use App\Models\Microservice;

/** @implements Command<Microservice> */
final readonly class RegisterServiceCommand implements Command
{
    public function __construct(
        public string $name,
        public string $baseUrl,
        public string $openApiPath,
    ) {
    }
}
