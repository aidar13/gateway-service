<?php

declare(strict_types=1);

namespace App\Exceptions;

/** The change is valid in form but would break the platform. */
final class ProtectedResource extends ApiException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }

    public function httpStatus(): int
    {
        return 409;
    }
}
