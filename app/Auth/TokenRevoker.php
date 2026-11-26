<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\User;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\TokenRepository;

/** Revokes access tokens together with their refresh tokens. */
final class TokenRevoker
{
    public function __construct(
        private readonly TokenRepository $accessTokens,
        private readonly RefreshTokenRepository $refreshTokens,
    ) {}

    public function revoke(string $accessTokenId): void
    {
        $this->accessTokens->revokeAccessToken($accessTokenId);
        $this->refreshTokens->revokeRefreshTokensByAccessTokenId($accessTokenId);
    }

    public function revokeAll(User $user): void
    {
        foreach ($user->tokens()->pluck('id') as $id) {
            $this->revoke((string) $id);
        }
    }
}
