<?php

declare(strict_types=1);

namespace App\Commands\Auth;

use App\Contracts\Command;
use App\Models\User;

/**
 * Resolves to null when the code is wrong, expired, used up or the account is disabled.
 *
 * @implements Command<User|null>
 */
final readonly class VerifyOtpCommand implements Command
{
    public function __construct(
        public string $hash,
        public string $code,
    ) {}
}
