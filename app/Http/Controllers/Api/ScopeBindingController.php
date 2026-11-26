<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Commands\Scope\BindRouteCommand;
use App\Commands\Scope\BindServiceCommand;
use App\Commands\Scope\UnbindRouteCommand;
use App\Commands\Scope\UnbindServiceCommand;
use App\Contracts\CommandBus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ScopeResource;
use OpenApi\Attributes as OA;

final class ScopeBindingController extends Controller
{
    public function __construct(
        private readonly CommandBus $bus,
    ) {
    }

    #[OA\Put(
        path: '/api/admin/scopes/{id}/services/{serviceId}',
        summary: 'Grant a whole service to the scope',
        security: [['Bearer' => []]],
        tags: ['Scopes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'serviceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function bindService(int $id, int $serviceId): ScopeResource
    {
        return new ScopeResource($this->bus->dispatch(new BindServiceCommand($id, $serviceId)));
    }

    #[OA\Delete(
        path: '/api/admin/scopes/{id}/services/{serviceId}',
        summary: 'Remove a service from the scope',
        security: [['Bearer' => []]],
        tags: ['Scopes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'serviceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function unbindService(int $id, int $serviceId): ScopeResource
    {
        return new ScopeResource($this->bus->dispatch(new UnbindServiceCommand($id, $serviceId)));
    }

    #[OA\Put(
        path: '/api/admin/scopes/{id}/routes/{routeId}',
        summary: 'Grant a single route to the scope',
        security: [['Bearer' => []]],
        tags: ['Scopes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'routeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function bindRoute(int $id, int $routeId): ScopeResource
    {
        return new ScopeResource($this->bus->dispatch(new BindRouteCommand($id, $routeId)));
    }

    #[OA\Delete(
        path: '/api/admin/scopes/{id}/routes/{routeId}',
        summary: 'Remove a route from the scope',
        security: [['Bearer' => []]],
        tags: ['Scopes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'routeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function unbindRoute(int $id, int $routeId): ScopeResource
    {
        return new ScopeResource($this->bus->dispatch(new UnbindRouteCommand($id, $routeId)));
    }
}
