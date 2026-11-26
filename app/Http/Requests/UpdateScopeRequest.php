<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateScopeRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
            'name'        => ['prohibited'],
        ];
    }

    /** @return array{description?: string|null, is_active?: bool} */
    public function changes(): array
    {
        /** @var array{description?: string|null, is_active?: bool} $changes */
        $changes = $this->safe()->only(['description', 'is_active']);

        return $changes;
    }
}
