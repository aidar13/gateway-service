<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreServiceRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'regex:/^[a-z][a-z0-9-]{1,49}$/', 'unique:microservices,name'],
            'base_url' => ['required', 'url:http,https', 'max:255'],
            'openapi_path' => ['sometimes', 'string', 'starts_with:/', 'max:255'],
        ];
    }
}
