<?php

declare(strict_types=1);

namespace App\Gateway;

/** One operation of a downstream service as the gateway sees it. */
final readonly class RouteDefinition
{
    public function __construct(
        public string $method,
        public string $path,
        public bool $requiresAuth = true,
        public ?string $permission = null,
        public ?string $summary = null,
        /** Id in the registry; null for a route that was only parsed. */
        public ?int $id = null,
    ) {}
}
