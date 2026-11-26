<?php

declare(strict_types=1);

namespace App\Auth;

final readonly class OtpSettings
{
    public function __construct(
        public int $ttlMinutes,
        public int $maxAttempts,
        public int $resendCooldownSeconds,
        public int $maxResends,
    ) {
    }
}
