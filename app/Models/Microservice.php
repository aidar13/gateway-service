<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MicroserviceFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $base_url
 * @property string $openapi_path
 * @property bool $is_active
 * @property-read Collection<int, ServiceRoute> $routes
 */
class Microservice extends Model
{
    /** @use HasFactory<MicroserviceFactory> */
    use HasFactory;

    protected $fillable = ['name', 'base_url', 'openapi_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<ServiceRoute, $this> */
    public function routes(): HasMany
    {
        return $this->hasMany(ServiceRoute::class);
    }
}
