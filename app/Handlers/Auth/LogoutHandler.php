<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Auth\TokenRevoker;
use App\Commands\Auth\LogoutCommand;
use Laravel\Passport\Token;

final class LogoutHandler
{
    public function __construct(private readonly TokenRevoker $tokens) {}

    public function handle(LogoutCommand $command): void
    {
        $user = $command->user;

        $token = $user->token();

        if ($token instanceof Token) {
            $this->tokens->revoke((string) $token->id);
        }
    }
}
