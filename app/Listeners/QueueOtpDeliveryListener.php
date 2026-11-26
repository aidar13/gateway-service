<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OtpIssuedEvent;
use App\Jobs\DeliverOtp;

final class QueueOtpDeliveryListener
{
    public function handle(OtpIssuedEvent $event): void
    {
        DeliverOtp::dispatch($event->destination, $event->code, $event->ttlMinutes);
    }
}
