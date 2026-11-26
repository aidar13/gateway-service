<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\OtpDestinationDTO;

/** Port to the notification microservice. */
interface NotificationClient
{
    public function sendOtp(OtpDestinationDTO $destination, string $code, int $ttlMinutes): void;
}
