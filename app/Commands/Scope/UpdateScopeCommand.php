<?php

declare(strict_types=1);

namespace App\Commands\Scope;

use App\Contracts\Command;
use App\Models\AccessScope;

/**
 * The name is immutable: clients and issued tokens refer to it.
 *
 * @implements Command<AccessScope>
 */
final readonly class UpdateScopeCommand implements Command
{
    /** @param array{description?: string|null, is_active?: bool} $changes */
    public function __construct(
        public int $scopeId,
        public array $changes,
    ) {
    }
}
