<?php

declare(strict_types=1);

namespace App\Actions\Gateway;

use App\Contracts\OpenApiFetcher;
use App\Contracts\ServiceRepository;
use App\Gateway\OpenApiParser;
use App\Models\Microservice;

/** Synchronises the route registry with the service's own OpenAPI document. */
final class ImportRoutes
{
    public function __construct(
        private readonly OpenApiFetcher $fetcher,
        private readonly OpenApiParser $parser,
        private readonly ServiceRepository $services,
    ) {}

    /** @return int number of routes now registered */
    public function execute(Microservice $service): int
    {
        // Parse first: a broken document must not wipe the routes that already work.
        $routes = $this->parser->parse($this->fetcher->fetch($service));

        $this->services->replaceRoutes($service, $routes);

        return count($routes);
    }
}
