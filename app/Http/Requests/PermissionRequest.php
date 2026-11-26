<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PermissionRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'regex:/^[a-z][a-z0-9_.-]{1,99}$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'api')->ignore($this->route('id')),
            ],
        ];
    }
}
