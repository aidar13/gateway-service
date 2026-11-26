<?php

declare(strict_types=1);

namespace App\Auth;

use App\Commands\Auth\VerifyOtpCommand;
use App\Contracts\CommandBus;
use DateInterval;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Custom OAuth2 grant `hash_and_code`: exchanges a one-time code for access and refresh tokens.
 * POST /oauth/token {grant_type, client_id, client_secret, hash, code}
 */
final class OtpGrant extends AbstractGrant
{
    public const IDENTIFIER = 'hash_and_code';

    public function __construct(
        private readonly CommandBus $bus,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->refreshTokenTTL = new DateInterval('P1M');
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope));

        $hash = $this->getRequestParameter('hash', $request);
        if (! is_string($hash)) {
            throw OAuthServerException::invalidRequest('hash');
        }

        $code = $this->getRequestParameter('code', $request);
        if (! is_string($code)) {
            throw OAuthServerException::invalidRequest('code');
        }

        $user = $this->bus->dispatch(new VerifyOtpCommand($hash, $code)) ?? throw OAuthServerException::invalidGrant();
        $userId = (string) $user->id;

        $scopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $userId);

        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $userId, $scopes);
        $responseType->setAccessToken($accessToken);

        $refreshToken = $this->issueRefreshToken($accessToken);
        if ($refreshToken !== null) {
            $responseType->setRefreshToken($refreshToken);
        }

        return $responseType;
    }
}
