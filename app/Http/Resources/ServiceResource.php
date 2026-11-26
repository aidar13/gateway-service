<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Microservice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/** @mixin Microservice */
#[OA\Schema(schema: 'Service', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string', example: 'orders'),
    new OA\Property(property: 'base_url', type: 'string', example: 'http://order-nginx'),
    new OA\Property(property: 'openapi_path', type: 'string', example: '/api/openapi.json'),
    new OA\Property(property: 'is_active', type: 'boolean'),
    new OA\Property(property: 'routes', type: 'array', items: new OA\Items(type: 'object'), nullable: true),
])]
final class ServiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'base_url'     => $this->base_url,
            'openapi_path' => $this->openapi_path,
            'is_active'    => $this->is_active,
            'routes'       => ServiceRouteResource::collection($this->whenLoaded('routes')),
        ];
    }
}
