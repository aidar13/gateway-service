<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRoleRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', 'string', 'regex:/^[a-z][a-z0-9_-]{1,49}$/',
                Rule::unique('roles', 'name')->where('guard_name', 'api')->ignore($this->route('id')),
            ],
            'permissions'   => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'api')],
        ];
    }

    /** @return list<string>|null */
    public function permissionNames(): ?array
    {
        $names = $this->validated('permissions');

        return is_array($names) ? array_values(array_map(strval(...), $names)) : null;
    }

    public function roleName(): ?string
    {
        $name = $this->validated('name');

        return is_string($name) ? $name : null;
    }
}
