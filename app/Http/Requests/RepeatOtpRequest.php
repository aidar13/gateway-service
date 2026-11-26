<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RepeatOtpRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['hash' => ['required', 'uuid']];
    }

    public function otpHash(): string
    {
        return (string) $this->validated('hash');
    }
}
