<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ProxyDenied extends ApiException
{
    private function __construct(string $message, private readonly int $status)
    {
        parent::__construct($message);
    }

    public static function unauthenticated(): self
    {
        return new self('Unauthenticated.', 401);
    }

    public static function outOfScope(): self
    {
        return new self('This application has no scope for the route.', 403);
    }

    public static function forbidden(string $permission): self
    {
        return new self("Missing permission '{$permission}'.", 403);
    }

    public function httpStatus(): int
    {
        return $this->status;
    }
}
