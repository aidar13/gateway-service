<?php

declare(strict_types=1);

namespace App\Handlers\Gateway;

use App\Commands\Gateway\ProxyRequestCommand;
use App\Contracts\ServiceRepository;
use App\Contracts\UpstreamClient;
use App\Exceptions\ProxyDenied;
use App\Exceptions\ResourceNotFound;
use App\Gateway\ClientScopeGate;
use App\Gateway\RouteDefinition;
use App\Gateway\RouteMatcher;
use App\Models\Microservice;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

/** Resolve service → match route → authorise (user, scope, permission) → forward. Unregistered routes are never forwarded. */
final class ProxyRequestHandler
{
    public function __construct(
        private readonly ServiceRepository $services,
        private readonly RouteMatcher $matcher,
        private readonly UpstreamClient $upstream,
        private readonly ClientScopeGate $scopes,
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

        $this->authorise($route, $service, $user);

        return $this->upstream->forward($service, $path, $request, $user);
    }

    /** Public routes are open; others need a user, an application scope and, if declared, a permission. */
    private function authorise(RouteDefinition $route, Microservice $service, ?User $user): void
    {
        if (! $route->requiresAuth && $route->permission === null) {
            return;
        }

        if ($user === null) {
            throw ProxyDenied::unauthenticated();
        }

        if (! $this->scopes->allows($user, $service, $route)) {
            throw ProxyDenied::outOfScope();
        }

        if ($route->permission !== null && ! $user->can($route->permission)) {
            throw ProxyDenied::forbidden($route->permission);
        }
    }
}
