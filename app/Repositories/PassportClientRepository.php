<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\OAuthClientRepository;
use App\DTO\NewClient;
use App\Exceptions\ResourceNotFound;
use App\Models\OAuthClient;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Passport\ClientRepository;

final class PassportClientRepository implements OAuthClientRepository
{
    public function __construct(private readonly ClientRepository $passport) {}

    public function all(): Collection
    {
        return OAuthClient::query()->where('revoked', false)->with('accessScopes')->orderBy('id')->get();
    }

    public function get(int $id): OAuthClient
    {
        return OAuthClient::query()->where('revoked', false)->with('accessScopes')->find($id)
            ?? throw ResourceNotFound::make('Client');
    }

    public function createPasswordClient(string $name): NewClient
    {
        $created = $this->passport->createPasswordGrantClient(null, $name, 'http://localhost');
        $secret = (string) $created->plainSecret;

        return new NewClient($this->get((int) $created->getKey()), $secret);
    }

    public function syncScopes(OAuthClient $client, array $scopeIds): void
    {
        $client->accessScopes()->sync($scopeIds);
        $client->load('accessScopes');
    }

    public function revoke(OAuthClient $client): void
    {
        $this->passport->delete($client);
    }
}
