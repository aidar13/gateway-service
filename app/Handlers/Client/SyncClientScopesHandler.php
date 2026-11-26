<?php

declare(strict_types=1);

namespace App\Handlers\Client;

use App\Commands\Client\SyncClientScopesCommand;
use App\Contracts\OAuthClientRepository;
use App\Models\OAuthClient;

final class SyncClientScopesHandler
{
    public function __construct(
        private readonly OAuthClientRepository $clients,
    ) {}

    public function handle(SyncClientScopesCommand $command): OAuthClient
    {
        $client = $this->clients->get($command->clientId);
        $this->clients->syncScopes($client, $command->scopeIds);

        return $client;
    }
}
