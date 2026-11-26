<?php

declare(strict_types=1);

namespace App\Commands\Gateway;

use App\Contracts\Command;
use App\Models\Microservice;

/**
 * Resolves to the number of routes now registered.
 *
 * @implements Command<int>
 */
final readonly class ImportServiceRoutesCommand implements Command
{
    public function __construct(
        public Microservice $service,
    ) {
    }
}
