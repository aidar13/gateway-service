<?php

declare(strict_types=1);

namespace App\Exceptions;

final class OtpNotFound extends ApiException
{
    public function __construct()
    {
        parent::__construct('Code request not found or no longer active.');
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
