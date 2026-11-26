<?php

declare(strict_types=1);

namespace App\Gateway;

/** Matches a concrete request path against templates such as `/api/orders/{id}`. */
final class RouteMatcher
{
    /**
     * @param iterable<RouteDefinition> $routes
     */
    public function match(iterable $routes, string $method, string $path): ?RouteDefinition
    {
        $segments = explode('/', trim($path, '/'));

        // Dot segments would be normalised downstream and could reach a different route.
        if (array_intersect(array_map(rawurldecode(...), $segments), ['.', '..']) !== []) {
            return null;
        }

        $best      = null;
        $bestScore = -1;

        foreach ($routes as $route) {
            if ($route->method !== strtoupper($method)) {
                continue;
            }

            $score = $this->score(explode('/', trim($route->path, '/')), $segments);

            if ($score !== null && $score > $bestScore) {
                [$best, $bestScore] = [$route, $score];
            }
        }

        return $best;
    }

    /**
     * @param list<string> $template
     * @param list<string> $segments
     * @return int|null number of literal segments matched (so `/orders/quote` beats `/orders/{id}`)
     */
    private function score(array $template, array $segments): ?int
    {
        if (count($template) !== count($segments)) {
            return null;
        }

        $literals = 0;

        foreach ($template as $i => $part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                if ($segments[$i] === '') {
                    return null;
                }

                continue;
            }

            if ($part !== $segments[$i]) {
                return null;
            }

            $literals++;
        }

        return $literals;
    }
}
