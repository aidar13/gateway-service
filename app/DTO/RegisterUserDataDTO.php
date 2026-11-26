<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class RegisterUserDataDTO
{
    public function __construct(
        public string $name,
        public string $username,
        public string $email,
        public ?string $phone,
        public string $password,
    ) {
    }
}
