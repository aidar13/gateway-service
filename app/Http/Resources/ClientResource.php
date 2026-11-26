<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OAuthClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/** @mixin OAuthClient */
#[OA\Schema(schema: 'Client', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string', example: 'mobile app'),
    new OA\Property(property: 'scopes', type: 'array', items: new OA\Items(type: 'string')),
    new OA\Property(property: 'secret', type: 'string', description: 'Only in the response that created the client'),
])]
final class ClientResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'scopes' => $this->accessScopes->pluck('name')->sort()->values(),
        ];
    }
}
