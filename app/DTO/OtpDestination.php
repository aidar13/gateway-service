<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\OtpChannel;

final readonly class OtpDestination
{
    public function __construct(
        public OtpChannel $channel,
        public string $value,
    ) {}
}
