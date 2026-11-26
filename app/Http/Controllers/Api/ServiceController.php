<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Commands\Gateway\DeleteServiceCommand;
use App\Commands\Gateway\ImportServiceRoutesCommand;
use App\Commands\Gateway\RegisterServiceCommand;
use App\Commands\Gateway\UpdateServiceCommand;
use App\Contracts\CommandBus;
use App\Contracts\ServiceRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

final class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceRepository $services,
        private readonly CommandBus $bus,
    ) {}

    #[OA\Get(
        path: '/api/admin/services',
        summary: 'Registered microservices',
        security: [['Bearer' => []]],
        tags: ['Registry'],
        responses: [new OA\Response(response: 200, description: 'Services')],
    )]
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection($this->services->all());
    }

    #[OA\Post(
        path: '/api/admin/services',
        summary: 'Register a microservice',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Service')),
        tags: ['Registry'],
        responses: [new OA\Response(response: 201, description: 'Registered'), new OA\Response(response: 403, description: 'Not an admin')],
    )]
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = $this->bus->dispatch(new RegisterServiceCommand(
            (string) $request->validated('name'),
            (string) $request->validated('base_url'),
            (string) $request->validated('openapi_path', '/api/openapi.json'),
        ));

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/admin/services/{id}',
        summary: 'A service with its routes',
        security: [['Bearer' => []]],
        tags: ['Registry'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Service'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function show(int $id): ServiceResource
    {
        return new ServiceResource($this->services->get($id));
    }

    #[OA\Post(
        path: '/api/admin/services/{id}/import-routes',
        summary: 'Re-read the OpenAPI document of the service and replace its routes',
        security: [['Bearer' => []]],
        tags: ['Registry'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Imported'), new OA\Response(response: 502, description: 'Service unavailable')],
    )]
    public function importRoutes(int $id): ServiceResource
    {
        $service = $this->services->get($id);
        $this->bus->dispatch(new ImportServiceRoutesCommand($service));

        return new ServiceResource($service);
    }

    #[OA\Patch(
        path: '/api/admin/services/{id}',
        summary: 'Change address, OpenAPI path or switch a service off',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'base_url', type: 'string'), new OA\Property(property: 'openapi_path', type: 'string'), new OA\Property(property: 'is_active', type: 'boolean')])),
        tags: ['Registry'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')],
    )]
    public function update(int $id, UpdateServiceRequest $request): ServiceResource
    {
        return new ServiceResource($this->bus->dispatch(new UpdateServiceCommand($id, $request->changes())));
    }

    #[OA\Delete(
        path: '/api/admin/services/{id}',
        summary: 'Delete a service with its routes',
        security: [['Bearer' => []]],
        tags: ['Registry'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'OK'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function destroy(int $id): Response
    {
        $this->bus->dispatch(new DeleteServiceCommand($id));

        return response()->noContent();
    }
}
