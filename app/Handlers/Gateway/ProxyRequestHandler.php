<?php

declare(strict_types=1);

namespace App\Handlers\Gateway;

use App\Commands\Gateway\ProxyRequestCommand;
use App\Contracts\ServiceRepository;
use App\Contracts\UpstreamClient;
use App\Exceptions\ProxyDenied;
use App\Exceptions\ResourceNotFound;
use App\Gateway\RouteDefinition;
use App\Gateway\RouteMatcher;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

/** Resolve service → match route → authorise → forward. Unregistered routes are never forwarded. */
final class ProxyRequestHandler
{
    public function __construct(
        private readonly ServiceRepository $services,
        private readonly RouteMatcher $matcher,
        private readonly UpstreamClient $upstream,
    ) {}

    public function handle(ProxyRequestCommand $command): Response
    {
        $serviceName = $command->serviceName;
        $path = $command->path;
        $request = $command->request;
        $user = $command->user;

        $service = $this->services->findActiveByName($serviceName)
            ?? throw ResourceNotFound::make('Service');

        $route = $this->matcher->match($this->services->routeDefinitions($service), $request->method(), $path)
            ?? throw ResourceNotFound::make('Route');

        $this->authorise($route, $user);

        return $this->upstream->forward($service, $path, $request, $user);
    }

    private function authorise(RouteDefinition $route, ?User $user): void
    {
        if (($route->requiresAuth || $route->permission !== null) && $user === null) {
            throw ProxyDenied::unauthenticated();
        }

        if ($route->permission !== null && $user?->can($route->permission) !== true) {
            throw ProxyDenied::forbidden($route->permission);
        }
    }
}
