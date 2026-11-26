<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $microservice_id
 * @property string $method
 * @property string $path
 * @property bool $requires_auth
 * @property string|null $permission
 * @property string|null $summary
 */
class ServiceRoute extends Model
{
    protected $fillable = ['microservice_id', 'method', 'path', 'requires_auth', 'permission', 'summary'];

    protected function casts(): array
    {
        return ['requires_auth' => 'boolean'];
    }
}
