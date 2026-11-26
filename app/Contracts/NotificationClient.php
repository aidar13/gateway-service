<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\OtpDestination;

/** Port to the notification microservice. */
interface NotificationClient
{
    public function sendOtp(OtpDestination $destination, string $code, int $ttlMinutes): void;
}
