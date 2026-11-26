<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SyncClientScopesRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'scope_ids'   => ['present', 'array'],
            'scope_ids.*' => ['integer', 'distinct', 'exists:access_scopes,id'],
        ];
    }

    /** @return list<int> */
    public function scopeIds(): array
    {
        return array_values(array_map(intval(...), (array)$this->validated('scope_ids', [])));
    }
}
