<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Passport\Client;

/**
 * @property int $id
 * @property string $name
 * @property bool $revoked
 * @property bool $password_client
 * @property-read Collection<int, AccessScope> $accessScopes
 */
class OAuthClient extends Client
{
    /**
     * Named so on purpose: Passport's own client model already uses a `scopes` attribute.
     *
     * @return BelongsToMany<AccessScope, $this>
     */
    public function accessScopes(): BelongsToMany
    {
        return $this->belongsToMany(AccessScope::class, 'client_scope', 'client_id', 'scope_id');
    }
}
