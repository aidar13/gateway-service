<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Auth\TokenRevoker;
use App\Models\User;
use Laravel\Passport\Token;

final class Logout
{
    public function __construct(private readonly TokenRevoker $tokens) {}

    public function execute(User $user): void
    {
        $token = $user->token();

        if ($token instanceof Token) {
            $this->tokens->revoke((string) $token->id);
        }
    }
}
