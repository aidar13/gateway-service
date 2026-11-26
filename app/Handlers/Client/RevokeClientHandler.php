<?php

declare(strict_types=1);

namespace App\Handlers\Client;

use App\Commands\Client\RevokeClientCommand;
use App\Contracts\OAuthClientRepository;
use App\Exceptions\ProtectedResource;

final class RevokeClientHandler
{
    public function __construct(
        private readonly OAuthClientRepository $clients,
    ) {}

    public function handle(RevokeClientCommand $command): void
    {
        if ($command->clientId === $command->currentClientId) {
            throw ProtectedResource::because('You cannot revoke the client you are signed in with.');
        }

        $this->clients->revoke($this->clients->get($command->clientId));
    }
}
