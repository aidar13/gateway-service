<?php

declare(strict_types=1);

namespace App\Enums;

enum OtpChannel: string
{
    case Sms = 'sms';
    case Email = 'email';

    public function verifiedColumn(): string
    {
        return $this->userColumn().'_verified_at';
    }

    /** Name of the user column that holds this kind of destination. */
    public function userColumn(): string
    {
        return match ($this) {
            self::Sms => 'phone',
            self::Email => 'email',
        };
    }
}
