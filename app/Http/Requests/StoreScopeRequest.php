<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreScopeRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            // Scope names travel space-separated in OAuth requests.
            'name'        => ['required', 'string', 'regex:/^[a-z][a-z0-9:._-]{1,49}$/', 'unique:access_scopes,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
