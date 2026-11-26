<?php

declare(strict_types=1);

namespace App\Events;

use App\DTO\OtpDestination;
use Illuminate\Foundation\Events\Dispatchable;

final class OtpIssued
{
    use Dispatchable;

    public function __construct(
        public readonly OtpDestination $destination,
        public readonly string $code,
        public readonly int $ttlMinutes,
    ) {}
}
