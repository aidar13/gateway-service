<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\RegisterUserDataDTO;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Must start with a letter so a username can never collide with a phone number.
            'username' => ['required', 'string', 'regex:/^[a-z][a-z0-9_.-]{2,31}$/', 'unique:users,username'],
            'email'    => ['required', 'email', 'lowercase', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'regex:/^\+[0-9]{7,15}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ];
    }

    public function toDto(): RegisterUserDataDTO
    {
        /** @var array{name: string, username: string, email: string, phone?: string|null, password: string} $data */
        $data = $this->validated();

        return new RegisterUserDataDTO($data['name'], $data['username'], $data['email'], $data['phone'] ?? null, $data['password']);
    }
}
