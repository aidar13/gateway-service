<?php

declare(strict_types=1);

namespace App\Handlers\Gateway;

use App\Commands\Gateway\ImportServiceRoutesCommand;
use App\Contracts\OpenApiFetcher;
use App\Contracts\ServiceRepository;
use App\Gateway\OpenApiParser;

/** Synchronises the route registry with the service's own OpenAPI document. */
final readonly class ImportServiceRoutesHandler
{
    public function __construct(
        private OpenApiFetcher $fetcher,
        private OpenApiParser $parser,
        private ServiceRepository $services,
    ) {
    }

    /** @return int number of routes now registered */
    public function handle(ImportServiceRoutesCommand $command): int
    {
        $service = $command->service;

        // Parse first: a broken document must not wipe the routes that already work.
        $routes = $this->parser->parse($this->fetcher->fetch($service));

        $this->services->syncRoutes($service, $routes);

        return count($routes);
    }
}
