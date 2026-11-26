<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\SystemPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Permission;

/** @mixin Permission */
#[OA\Schema(schema: 'Permission', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string', example: 'reports.view'),
    new OA\Property(property: 'is_system', type: 'boolean'),
])]
final class PermissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'is_system' => SystemPermission::tryFrom($this->name) !== null,
        ];
    }
}
