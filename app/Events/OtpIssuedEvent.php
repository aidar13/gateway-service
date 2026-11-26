<?php

declare(strict_types=1);

namespace App\Events;

use App\DTO\OtpDestinationDTO;
use Illuminate\Foundation\Events\Dispatchable;

final class OtpIssuedEvent
{
    use Dispatchable;

    public function __construct(
        public readonly OtpDestinationDTO $destination,
        public readonly string $code,
        public readonly int $ttlMinutes,
    ) {
    }
}
