<?php

declare(strict_types=1);

namespace App\Auth;

use App\Contracts\CodeGenerator;

/** Cryptographically secure numeric code, zero-padded. */
final readonly class RandomCodeGenerator implements CodeGenerator
{
    public function __construct(private int $length)
    {
    }

    public function generate(): string
    {
        return str_pad((string)random_int(0, 10 ** $this->length - 1), $this->length, '0', STR_PAD_LEFT);
    }
}
