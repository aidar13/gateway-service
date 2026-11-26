<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\OtpDestination;
use App\Enums\OtpChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class OtpRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        // Exactly one destination.
        return [
            'phone' => ['required_without:email', 'prohibits:email', 'string', 'regex:/^\+[0-9]{7,15}$/'],
            'email' => ['required_without:phone', 'email', 'max:255'],
        ];
    }

    public function destination(): OtpDestination
    {
        return $this->filled('phone')
            ? new OtpDestination(OtpChannel::Sms, (string) $this->validated('phone'))
            : new OtpDestination(OtpChannel::Email, (string) $this->validated('email'));
    }
}
