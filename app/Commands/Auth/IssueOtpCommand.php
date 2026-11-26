<?php

declare(strict_types=1);

namespace App\Commands\Auth;

use App\Contracts\Command;
use App\DTO\OtpDestinationDTO;
use App\Models\OtpCode;

/**
 * First request of a one-time code for a phone number or e-mail.
 *
 * @implements Command<OtpCode>
 */
final readonly class IssueOtpCommand implements Command
{
    public function __construct(
        public OtpDestinationDTO $destination,
    ) {
    }
}
