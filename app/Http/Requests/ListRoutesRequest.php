<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListRoutesRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'service_id' => ['sometimes', 'integer'],
            'search'     => ['sometimes', 'string', 'max:100'],
            'per_page'   => ['sometimes', 'integer', 'min:1', 'max:200'],
        ];
    }

    public function serviceId(): ?int
    {
        return $this->filled('service_id') ? $this->integer('service_id') : null;
    }

    public function search(): ?string
    {
        return $this->filled('search') ? $this->string('search')->toString() : null;
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 50);
    }
}
