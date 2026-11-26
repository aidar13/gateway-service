<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OtpIssued;
use App\Jobs\DeliverOtp;

final class QueueOtpDelivery
{
    public function handle(OtpIssued $event): void
    {
        DeliverOtp::dispatch($event->destination, $event->code, $event->ttlMinutes);
    }
}
