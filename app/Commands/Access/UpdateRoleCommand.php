<?php

declare(strict_types=1);

namespace App\Commands\Access;

use App\Contracts\Command;
use Spatie\Permission\Models\Role;

/**
 * A null field is left unchanged.
 *
 * @implements Command<Role>
 */
final readonly class UpdateRoleCommand implements Command
{
    /** @param list<string>|null $permissions */
    public function __construct(
        public int $roleId,
        public ?string $name,
        public ?array $permissions,
    ) {
    }
}
