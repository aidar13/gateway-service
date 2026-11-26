<?php

declare(strict_types=1);

namespace App\Commands\Gateway;

use App\Contracts\Command;

/**
 * Removes the service together with its routes and scope bindings.
 *
 * @implements Command<null>
 */
final readonly class DeleteServiceCommand implements Command
{
    public function __construct(
        public int $serviceId,
    ) {
    }
}
