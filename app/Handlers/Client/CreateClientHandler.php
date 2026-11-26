<?php

declare(strict_types=1);

namespace App\Handlers\Client;

use App\Commands\Client\CreateClientCommand;
use App\Contracts\OAuthClientRepository;
use App\DTO\NewClient;

final class CreateClientHandler
{
    public function __construct(
        private readonly OAuthClientRepository $clients,
    ) {}

    public function handle(CreateClientCommand $command): NewClient
    {
        $created = $this->clients->createPasswordClient($command->name);
        $this->clients->syncScopes($created->client, $command->scopeIds);

        return $created;
    }
}
