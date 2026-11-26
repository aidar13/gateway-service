<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\OtpDestination;
use App\Models\OtpCode;
use Carbon\CarbonImmutable;

interface OtpRepository
{
    public function create(OtpDestination $destination, string $codeHash, CarbonImmutable $expiresAt): OtpCode;

    public function findByHash(string $hash): ?OtpCode;

    /** Most recently sent, not yet consumed code for the destination. */
    public function latestFor(OtpDestination $destination): ?OtpCode;

    public function replaceCode(OtpCode $otp, string $codeHash, CarbonImmutable $expiresAt): void;

    /** Atomic: returns false when the attempt budget is already spent. */
    public function registerAttempt(OtpCode $otp, int $maxAttempts): bool;

    /** Atomic: returns false when somebody else consumed the code first. */
    public function consume(OtpCode $otp): bool;
}
