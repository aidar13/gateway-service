<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\NotificationClient;
use App\DTO\OtpDestination;
use Psr\Log\LoggerInterface;

/** Used when no notification service is configured. Writes the code to the log in local/testing only. */
final class LogNotificationClient implements NotificationClient
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $revealCode,
    ) {}

    public function sendOtp(OtpDestination $destination, string $code, int $ttlMinutes): void
    {
        $this->logger->info('otp', [
            'to' => $destination->value,
            'code' => $this->revealCode ? $code : '[hidden]',
            'ttl_minutes' => $ttlMinutes,
        ]);
    }
}
