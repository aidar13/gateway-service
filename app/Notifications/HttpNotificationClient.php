<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\NotificationClient;
use App\DTO\OtpDestination;
use App\Enums\OtpChannel;
use Illuminate\Http\Client\Factory as Http;

final class HttpNotificationClient implements NotificationClient
{
    public function __construct(
        private readonly Http $http,
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {}

    public function sendOtp(OtpDestination $destination, string $code, int $ttlMinutes): void
    {
        $recipient = match ($destination->channel) {
            OtpChannel::Sms => ['phone' => $destination->value],
            OtpChannel::Email => ['email' => $destination->value],
        };

        $this->http
            ->withHeaders(['X-Api-Key' => $this->apiKey])
            ->timeout(5)
            ->acceptJson()
            ->post(rtrim($this->baseUrl, '/').'/api/notifications', [
                'event' => 'auth.otp',
                'recipient' => $recipient,
                'payload' => ['code' => $code, 'ttl_minutes' => $ttlMinutes],
            ])
            ->throw();
    }
}
