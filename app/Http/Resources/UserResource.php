<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/** @mixin User */
#[OA\Schema(schema: 'User', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'username', type: 'string', nullable: true),
    new OA\Property(property: 'email', type: 'string', nullable: true),
    new OA\Property(property: 'phone', type: 'string', nullable: true),
    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
])]
final class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'roles' => $this->getRoleNames(),
        ];
    }
}
