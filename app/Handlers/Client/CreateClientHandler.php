<?php

declare(strict_types=1);

namespace App\Handlers\Client;

use App\Commands\Client\CreateClientCommand;
use App\Contracts\OAuthClientRepository;
use App\DTO\NewClientDTO;

final readonly class CreateClientHandler
{
    public function __construct(
        private OAuthClientRepository $clients,
    ) {
    }

    public function handle(CreateClientCommand $command): NewClientDTO
    {
        $created = $this->clients->createPasswordClient($command->name);
        $this->clients->syncScopes($created->client, $command->scopeIds);

        return $created;
    }
}
