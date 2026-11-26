<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SystemPermission;
use Illuminate\Foundation\Http\FormRequest;

final class StoreClientRequest extends FormRequest
{
    /** Creating a client must not be a way around the permission for handing out scopes. */
    public function authorize(): bool
    {
        return in_array($this->input('scope_ids'), [null, []], true)
            || $this->user()?->can(SystemPermission::ClientsAssignScopes->value) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'scope_ids'   => ['sometimes', 'array'],
            'scope_ids.*' => ['integer', 'distinct', 'exists:access_scopes,id'],
        ];
    }

    /** @return list<int> */
    public function scopeIds(): array
    {
        return array_values(array_map(intval(...), (array)$this->validated('scope_ids', [])));
    }
}
