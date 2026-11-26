<?php

declare(strict_types=1);

namespace App\Commands\Scope;

use App\Contracts\Command;
use App\Models\AccessScope;

/**
 * Grants every route of the service, including routes imported later.
 *
 * @implements Command<AccessScope>
 */
final readonly class BindServiceCommand implements Command
{
    public function __construct(
        public int $scopeId,
        public int $serviceId,
    ) {}
}
