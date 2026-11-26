<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\NewClient;
use App\Exceptions\ResourceNotFound;
use App\Models\OAuthClient;
use Illuminate\Database\Eloquent\Collection;

interface OAuthClientRepository
{
    /** @return Collection<int, OAuthClient> with scopes */
    public function all(): Collection;

    /** @throws ResourceNotFound for unknown and revoked clients */
    public function get(int $id): OAuthClient;

    public function createPasswordClient(string $name): NewClient;

    /** @param list<int> $scopeIds */
    public function syncScopes(OAuthClient $client, array $scopeIds): void;

    public function revoke(OAuthClient $client): void;
}
