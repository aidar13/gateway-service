<?php

declare(strict_types=1);

namespace App\Gateway;

use InvalidArgumentException;

/**
 * Turns an OpenAPI 3 document into route definitions. Vendor extensions:
 *  - `x-unauthenticated: true` — the operation is public;
 *  - `x-permission: "name"`    — the caller needs this permission.
 * Everything else requires authentication (secure by default).
 */
final class OpenApiParser
{
    private const METHODS = ['get', 'post', 'put', 'patch', 'delete'];

    /**
     * @param  array<array-key, mixed>  $document
     * @return list<RouteDefinition>
     */
    public function parse(array $document): array
    {
        $paths = $document['paths'] ?? null;

        if (! isset($document['openapi']) || ! is_array($paths)) {
            throw new InvalidArgumentException('Not an OpenAPI 3 document.');
        }

        $routes = [];

        foreach ($paths as $path => $operations) {
            if (! is_array($operations)) {
                continue;
            }

            foreach (self::METHODS as $method) {
                $operation = $operations[$method] ?? null;
                if (! is_array($operation)) {
                    continue;
                }

                $permission = $operation['x-permission'] ?? null;
                $summary = $operation['summary'] ?? null;

                $routes[] = new RouteDefinition(
                    strtoupper($method),
                    '/'.trim((string) $path, '/'),
                    ($operation['x-unauthenticated'] ?? false) !== true,
                    is_string($permission) ? $permission : null,
                    is_string($summary) ? $summary : null,
                );
            }
        }

        return $routes;
    }
}
