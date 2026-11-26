<?php

declare(strict_types=1);

namespace App\Commands\Client;

use App\Contracts\Command;

/**
 * Revokes the client and every token issued to it.
 *
 * @implements Command<null>
 */
final readonly class RevokeClientCommand implements Command
{
    public function __construct(
        public int $clientId,
        public ?int $currentClientId,
    ) {
    }
}
