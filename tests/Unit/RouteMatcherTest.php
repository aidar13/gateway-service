<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Gateway\RouteDefinition;
use App\Gateway\RouteMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RouteMatcherTest extends TestCase
{
    /** @return list<RouteDefinition> */
    private function routes(): array
    {
        return [
            new RouteDefinition('GET', '/api/orders/{id}'),
            new RouteDefinition('GET', '/api/orders/summary'),
            new RouteDefinition('POST', '/api/orders/{id}/pay'),
        ];
    }

    /** @return array<string, array{string, string, string|null}> */
    public static function cases(): array
    {
        return [
            'parameter'                   => ['GET', '/api/orders/42', '/api/orders/{id}'],
            'literal wins over parameter' => ['GET', '/api/orders/summary', '/api/orders/summary'],
            'nested'                      => ['post', '/api/orders/42/pay', '/api/orders/{id}/pay'],
            'wrong method'                => ['DELETE', '/api/orders/42', null],
            'extra segment'               => ['GET', '/api/orders/42/secret', null],
            'dot segment'                 => ['GET', '/api/orders/..', null],
            'encoded dot segment'         => ['GET', '/api/orders/%2e%2e', null],
        ];
    }

    #[DataProvider('cases')]
    public function test_match(string $method, string $path, ?string $expected): void
    {
        $this->assertSame($expected, (new RouteMatcher)->match($this->routes(), $method, $path)?->path);
    }
}
