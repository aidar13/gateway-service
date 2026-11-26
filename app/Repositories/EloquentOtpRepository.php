<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\OtpRepository;
use App\DTO\OtpDestination;
use App\Models\OtpCode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class EloquentOtpRepository implements OtpRepository
{
    public function create(OtpDestination $destination, string $codeHash, CarbonImmutable $expiresAt): OtpCode
    {
        return OtpCode::query()->create([
            'hash' => (string) Str::uuid(),
            'channel' => $destination->channel,
            'destination' => $destination->value,
            'code_hash' => $codeHash,
            'attempts' => 0,
            'resends' => 0,
            'expires_at' => $expiresAt,
            'last_sent_at' => now(),
        ]);
    }

    public function findByHash(string $hash): ?OtpCode
    {
        return OtpCode::query()->where('hash', $hash)->first();
    }

    public function latestFor(OtpDestination $destination): ?OtpCode
    {
        return OtpCode::query()
            ->where('channel', $destination->channel)
            ->where('destination', $destination->value)
            ->whereNull('consumed_at')
            ->orderByDesc('last_sent_at')
            ->orderByDesc('id')
            ->first();
    }

    public function replaceCode(OtpCode $otp, string $codeHash, CarbonImmutable $expiresAt): void
    {
        // The attempt counter is intentionally kept: resending must not refill the guessing budget.
        $otp->forceFill([
            'code_hash' => $codeHash,
            'expires_at' => $expiresAt,
            'last_sent_at' => now(),
            'resends' => $otp->resends + 1,
        ])->save();
    }

    public function registerAttempt(OtpCode $otp, int $maxAttempts): bool
    {
        $updated = OtpCode::query()
            ->whereKey($otp->id)
            ->where('attempts', '<', $maxAttempts)
            ->increment('attempts');

        return $updated === 1;
    }

    public function consume(OtpCode $otp): bool
    {
        return OtpCode::query()
            ->whereKey($otp->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]) === 1;
    }
}
