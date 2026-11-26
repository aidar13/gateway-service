<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\NotificationClient;
use App\DTO\OtpDestination;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Encrypted on the queue: the payload contains a live one-time code. */
final class DeliverOtp implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public readonly OtpDestination $destination,
        private readonly string $code,
        private readonly int $ttlMinutes,
    ) {
        $this->onQueue('otp');
    }

    public function handle(NotificationClient $notifications): void
    {
        $notifications->sendOtp($this->destination, $this->code, $this->ttlMinutes);
    }
}
