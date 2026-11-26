<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ResourceNotFound extends ApiException
{
    public static function make(string $what): self
    {
        return new self("{$what} not found.");
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
