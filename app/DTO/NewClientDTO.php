<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\OAuthClient;

/** A freshly created client. The plain secret exists only now and is shown once. */
final readonly class NewClientDTO
{
    public function __construct(
        public OAuthClient $client,
        public string $plainSecret,
    ) {
    }
}
