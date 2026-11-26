<?php

declare(strict_types=1);

namespace App\Commands\Client;

use App\Contracts\Command;
use App\Models\OAuthClient;

/** @implements Command<OAuthClient> */
final readonly class SyncClientScopesCommand implements Command
{
    /** @param list<int> $scopeIds */
    public function __construct(
        public int $clientId,
        public array $scopeIds,
    ) {
    }
}
