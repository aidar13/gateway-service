<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Auth\OtpSettings;
use App\Commands\Auth\IssueOtpCommand;
use App\Contracts\CodeGenerator;
use App\Contracts\OtpRepository;
use App\Events\OtpIssuedEvent;
use App\Exceptions\OtpThrottled;
use App\Models\OtpCode;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;

/** First request of a one-time code for a phone number or e-mail. */
final readonly class IssueOtpHandler
{
    public function __construct(
        private OtpRepository $codes,
        private CodeGenerator $generator,
        private Hasher $hasher,
        private Dispatcher $events,
        private OtpSettings $settings,
    ) {
    }

    public function handle(IssueOtpCommand $command): OtpCode
    {
        $destination = $command->destination;

        $this->guardCooldown($this->codes->latestFor($destination));

        $code = $this->generator->generate();
        $otp  = $this->codes->create($destination, $this->hasher->make($code), $this->expiry());

        $this->events->dispatch(new OtpIssuedEvent($destination, $code, $this->settings->ttlMinutes));

        return $otp;
    }

    private function guardCooldown(?OtpCode $previous): void
    {
        if ($previous === null) {
            return;
        }

        $availableAt = $previous->last_sent_at->addSeconds($this->settings->resendCooldownSeconds);

        if ($availableAt->isFuture()) {
            throw OtpThrottled::cooldown((int)ceil(CarbonImmutable::now()->diffInSeconds($availableAt)));
        }
    }

    private function expiry(): CarbonImmutable
    {
        return CarbonImmutable::now()->addMinutes($this->settings->ttlMinutes);
    }
}
