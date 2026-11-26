<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Gateway\OpenApiParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OpenApiParserTest extends TestCase
{
    public function test_routes_are_secure_by_default(): void
    {
        $routes = (new OpenApiParser)->parse([
            'openapi' => '3.0.0',
            'paths'   => ['/api/things/' => [
                'get'        => [],
                'post'       => ['x-unauthenticated' => true, 'summary' => 'Open'],
                'parameters' => [],
            ]],
        ]);

        $this->assertCount(2, $routes);
        $this->assertSame('/api/things', $routes[0]->path);
        $this->assertTrue($routes[0]->requiresAuth);
        $this->assertFalse($routes[1]->requiresAuth);
        $this->assertSame('Open', $routes[1]->summary);
    }

    public function test_rejects_other_documents(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OpenApiParser)->parse(['swagger' => '2.0']);
    }
}
