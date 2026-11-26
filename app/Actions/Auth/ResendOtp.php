<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Auth\OtpSettings;
use App\Contracts\CodeGenerator;
use App\Contracts\OtpRepository;
use App\DTO\OtpDestination;
use App\Events\OtpIssued;
use App\Exceptions\OtpNotFound;
use App\Exceptions\OtpThrottled;
use App\Models\OtpCode;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;

final class ResendOtp
{
    public function __construct(
        private readonly OtpRepository $codes,
        private readonly CodeGenerator $generator,
        private readonly Hasher $hasher,
        private readonly Dispatcher $events,
        private readonly OtpSettings $settings,
    ) {}

    public function execute(string $hash): OtpCode
    {
        $otp = $this->codes->findByHash($hash);

        // An expired code may be re-sent; a consumed or brute-forced one may not.
        if ($otp === null || $otp->consumed_at !== null || $otp->attempts >= $this->settings->maxAttempts) {
            throw new OtpNotFound;
        }

        if ($otp->resends >= $this->settings->maxResends) {
            throw OtpThrottled::resendLimit();
        }

        $availableAt = $otp->last_sent_at->addSeconds($this->settings->resendCooldownSeconds);
        if ($availableAt->isFuture()) {
            throw OtpThrottled::cooldown((int) ceil(CarbonImmutable::now()->diffInSeconds($availableAt)));
        }

        $code = $this->generator->generate();
        $this->codes->replaceCode(
            $otp,
            $this->hasher->make($code),
            CarbonImmutable::now()->addMinutes($this->settings->ttlMinutes),
        );

        $this->events->dispatch(new OtpIssued(
            new OtpDestination($otp->channel, $otp->destination),
            $code,
            $this->settings->ttlMinutes,
        ));

        return $otp;
    }
}
