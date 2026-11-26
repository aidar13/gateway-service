<?php

declare(strict_types=1);

namespace App\Contracts;

interface CodeGenerator
{
    public function generate(): string;
}
