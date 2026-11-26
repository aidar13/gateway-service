<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

final class UpstreamUnavailable extends ApiException
{
    public static function service(string $name, ?Throwable $previous = null): self
    {
        return new self("Service '{$name}' is unavailable.", 0, $previous);
    }

    public function httpStatus(): int
    {
        return 502;
    }
}
