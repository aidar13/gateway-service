<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OtpChannel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $hash
 * @property OtpChannel $channel
 * @property string $destination
 * @property string $code_hash
 * @property int $attempts
 * @property int $resends
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable $last_sent_at
 * @property CarbonImmutable|null $consumed_at
 */
class OtpCode extends Model
{
    protected $fillable = [
        'hash', 'channel', 'destination', 'code_hash', 'attempts', 'resends', 'expires_at', 'last_sent_at', 'consumed_at',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'channel'      => OtpChannel::class,
            'attempts'     => 'integer',
            'resends'      => 'integer',
            'expires_at'   => 'immutable_datetime',
            'last_sent_at' => 'immutable_datetime',
            'consumed_at'  => 'immutable_datetime',
        ];
    }

    public function isUsable(int $maxAttempts): bool
    {
        return $this->consumed_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < $maxAttempts;
    }
}
