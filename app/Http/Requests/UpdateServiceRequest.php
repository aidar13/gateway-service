<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateServiceRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'base_url'     => ['sometimes', 'url:http,https', 'max:255'],
            'openapi_path' => ['sometimes', 'string', 'starts_with:/', 'max:255'],
            'is_active'    => ['sometimes', 'boolean'],
        ];
    }

    /** @return array{base_url?: string, openapi_path?: string, is_active?: bool} */
    public function changes(): array
    {
        /** @var array{base_url?: string, openapi_path?: string, is_active?: bool} $changes */
        $changes = $this->validated();

        return $changes;
    }
}
