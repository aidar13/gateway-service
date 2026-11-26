<?php

declare(strict_types=1);

namespace App\Commands\Client;

use App\Contracts\Command;
use App\DTO\NewClientDTO;

/**
 * A first-party application that signs users in (web, mobile…).
 *
 * @implements Command<NewClientDTO>
 */
final readonly class CreateClientCommand implements Command
{
    /** @param list<int> $scopeIds */
    public function __construct(
        public string $name,
        public array $scopeIds,
    ) {
    }
}
