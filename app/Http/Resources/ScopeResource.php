<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AccessScope;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/** @mixin AccessScope */
#[OA\Schema(schema: 'Scope', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string', example: 'orders:read'),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'is_active', type: 'boolean'),
    new OA\Property(property: 'services', type: 'array', items: new OA\Items(type: 'object'), nullable: true),
    new OA\Property(property: 'routes', type: 'array', items: new OA\Items(type: 'object'), nullable: true),
])]
final class ScopeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'is_active'      => $this->is_active,
            'services_count' => $this->whenCounted('services'),
            'routes_count'   => $this->whenCounted('routes'),
            'clients_count'  => $this->whenCounted('clients'),
            'services'       => ServiceResource::collection($this->whenLoaded('services')),
            'routes'         => ServiceRouteResource::collection($this->whenLoaded('routes')),
        ];
    }
}
