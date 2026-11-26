<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\OtpIssuedEvent;
use App\Listeners\QueueOtpDeliveryListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * The explicit map of events to listeners. Auto-discovery is switched off in
 * bootstrap/app.php, otherwise every listener would be registered twice.
 */
final class EventServiceProvider extends ServiceProvider
{
    /** @var array<class-string, list<class-string>> */
    protected $listen = [
        OtpIssuedEvent::class => [
            QueueOtpDeliveryListener::class,
        ],
    ];

    /** The framework's own event provider has already done this. */
    protected function configureEmailVerification(): void
    {
    }
}
