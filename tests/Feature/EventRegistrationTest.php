<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\OtpDestinationDTO;
use App\Enums\OtpChannel;
use App\Events\OtpIssuedEvent;
use App\Jobs\DeliverOtp;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class EventRegistrationTest extends TestCase
{
    /** Exactly once: a second registration would send the user two messages. */
    public function test_issued_code_is_queued_for_delivery_exactly_once(): void
    {
        Queue::fake();

        Event::dispatch(new OtpIssuedEvent(new OtpDestinationDTO(OtpChannel::Email, 'user@example.test'), '1234', 5));

        Queue::assertPushed(DeliverOtp::class, 1);
    }
}
