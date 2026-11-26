<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\SystemRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

/** @mixin Role */
#[OA\Schema(schema: 'Role', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string', example: 'manager'),
    new OA\Property(property: 'is_system', type: 'boolean'),
    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
])]
final class RoleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'is_system'   => SystemRole::tryFrom($this->name) !== null,
            'permissions' => $this->permissions->pluck('name')->sort()->values(),
        ];
    }
}
