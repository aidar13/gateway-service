<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Auth\OtpSettings;
use App\Contracts\CodeGenerator;
use App\Contracts\OtpRepository;
use App\DTO\OtpDestination;
use App\Events\OtpIssued;
use App\Exceptions\OtpThrottled;
use App\Models\OtpCode;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;

/** First request of a one-time code for a phone number or e-mail. */
final class IssueOtp
{
    public function __construct(
        private readonly OtpRepository $codes,
        private readonly CodeGenerator $generator,
        private readonly Hasher $hasher,
        private readonly Dispatcher $events,
        private readonly OtpSettings $settings,
    ) {}

    public function execute(OtpDestination $destination): OtpCode
    {
        $this->guardCooldown($this->codes->latestFor($destination));

        $code = $this->generator->generate();
        $otp = $this->codes->create($destination, $this->hasher->make($code), $this->expiry());

        $this->events->dispatch(new OtpIssued($destination, $code, $this->settings->ttlMinutes));

        return $otp;
    }

    private function guardCooldown(?OtpCode $previous): void
    {
        if ($previous === null) {
            return;
        }

        $availableAt = $previous->last_sent_at->addSeconds($this->settings->resendCooldownSeconds);

        if ($availableAt->isFuture()) {
            throw OtpThrottled::cooldown((int) ceil(CarbonImmutable::now()->diffInSeconds($availableAt)));
        }
    }

    private function expiry(): CarbonImmutable
    {
        return CarbonImmutable::now()->addMinutes($this->settings->ttlMinutes);
    }
}
