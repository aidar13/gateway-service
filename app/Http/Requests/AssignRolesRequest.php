<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignRolesRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'roles' => ['present', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'api')],
        ];
    }

    /** @return list<string> */
    public function roles(): array
    {
        /** @var list<string> $roles */
        $roles = array_values((array) $this->validated('roles'));

        return $roles;
    }
}
