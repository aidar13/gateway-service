<?php

declare(strict_types=1);

namespace App\Actions\Gateway;

use App\Contracts\ServiceRepository;
use App\Contracts\UpstreamClient;
use App\Exceptions\ProxyDenied;
use App\Exceptions\ResourceNotFound;
use App\Gateway\RouteDefinition;
use App\Gateway\RouteMatcher;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Resolve service → match route → authorise → forward. Unregistered routes are never forwarded. */
final class ProxyRequest
{
    public function __construct(
        private readonly ServiceRepository $services,
        private readonly RouteMatcher $matcher,
        private readonly UpstreamClient $upstream,
    ) {}

    public function execute(string $serviceName, string $path, Request $request, ?User $user): Response
    {
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
