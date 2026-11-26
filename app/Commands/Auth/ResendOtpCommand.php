<?php

declare(strict_types=1);

namespace App\Commands\Auth;

use App\Contracts\Command;
use App\Models\OtpCode;

/** @implements Command<OtpCode> */
final readonly class ResendOtpCommand implements Command
{
    public function __construct(
        public string $hash,
    ) {}
}
