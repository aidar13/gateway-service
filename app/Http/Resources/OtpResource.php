<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/** @mixin OtpCode */
#[OA\Schema(schema: 'Otp', properties: [
    new OA\Property(property: 'hash', type: 'string', format: 'uuid'),
    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'resend_available_at', type: 'string', format: 'date-time'),
])]
final class OtpResource extends JsonResource
{
    /** @return array<string, string> */
    public function toArray(Request $request): array
    {
        return [
            'hash' => $this->hash,
            'expires_at' => $this->expires_at->toIso8601String(),
            'resend_available_at' => $this->last_sent_at
                ->addSeconds((int) config('gateway.otp.resend_cooldown_seconds'))
                ->toIso8601String(),
        ];
    }
}
