<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ServiceRoute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceRoute */
final class ServiceRouteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'method' => $this->method,
            'path' => $this->path,
            'requires_auth' => $this->requires_auth,
            'permission' => $this->permission,
            'summary' => $this->summary,
        ];
    }
}
