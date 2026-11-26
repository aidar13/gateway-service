<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\CodeGenerator;

final class FixedCodeGenerator implements CodeGenerator
{
    public function __construct(public string $code = '123456') {}

    public function generate(): string
    {
        return $this->code;
    }
}
