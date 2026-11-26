<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Models\OAuthClient;
use Laravel\Passport\ClientRepository;

trait CreatesPasswordClient
{
    /** An existing client with this name is returned as it is, even if it has been revoked. */
    private function passwordClient(string $name, string $secret): OAuthClient
    {
        $existing = OAuthClient::query()->where('password_client', true)->where('name', $name)->first();

        if ($existing !== null) {
            return $existing;
        }

        $created = app(ClientRepository::class)->createPasswordGrantClient(null, $name, 'http://localhost');
        $created->forceFill(['secret' => $secret])->save();

        return OAuthClient::query()->findOrFail((int)$created->getKey());
    }
}
