<?php

declare(strict_types=1);

namespace App\Exceptions;

final class OtpThrottled extends ApiException
{
    public static function cooldown(int $seconds): self
    {
        return new self("A code was sent recently. Try again in {$seconds} seconds.");
    }

    public static function resendLimit(): self
    {
        return new self('Resend limit reached. Request a new code later.');
    }

    public function httpStatus(): int
    {
        return 429;
    }
}
