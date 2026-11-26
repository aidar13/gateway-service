<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Contracts\ScopeRepository;
use App\Models\Microservice;
use App\Models\User;
use Laravel\Passport\Token;

/**
 * What the calling application may touch, independently of who the user is:
 * effective scopes = scopes of the OAuth client ∩ scopes the token was narrowed to.
 */
final readonly class ClientScopeGate
{
    public function __construct(private ScopeRepository $scopes)
    {
    }

    public function allows(User $user, Microservice $service, RouteDefinition $route): bool
    {
        $token = $user->token();

        if (!$token instanceof Token || $route->id === null) {
            return false;
        }

        $requested = array_values(array_diff((array)$token->scopes, ['*']));

        return $this->scopes->grants((int)$token->client_id, $requested, $service->id, $route->id);
    }
}
