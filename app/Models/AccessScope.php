<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A named set of access rights granted to OAuth clients: whole services and/or single routes.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 */
class AccessScope extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsToMany<Microservice, $this> */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Microservice::class, 'scope_service', 'scope_id', 'microservice_id');
    }

    /** @return BelongsToMany<ServiceRoute, $this> */
    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(ServiceRoute::class, 'scope_route', 'scope_id', 'service_route_id');
    }

    /** @return BelongsToMany<OAuthClient, $this> */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(OAuthClient::class, 'client_scope', 'scope_id', 'client_id');
    }
}
