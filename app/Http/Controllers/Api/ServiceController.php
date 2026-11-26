<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Gateway\ImportRoutes;
use App\Contracts\ServiceRepository;
use App\Exceptions\ResourceNotFound;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Microservice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

final class ServiceController extends Controller
{
    public function __construct(private readonly ServiceRepository $services) {}

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
        $service = $this->services->create(
            (string) $request->validated('name'),
            (string) $request->validated('base_url'),
            (string) $request->validated('openapi_path', '/api/openapi.json'),
        );

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
        return new ServiceResource($this->findOrFail($id));
    }

    #[OA\Post(
        path: '/api/admin/services/{id}/import-routes',
        summary: 'Re-read the OpenAPI document of the service and replace its routes',
        security: [['Bearer' => []]],
        tags: ['Registry'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Imported'), new OA\Response(response: 502, description: 'Service unavailable')],
    )]
    public function importRoutes(int $id, ImportRoutes $action): ServiceResource
    {
        $service = $this->findOrFail($id);
        $action->execute($service);

        return new ServiceResource($service);
    }

    private function findOrFail(int $id): Microservice
    {
        return $this->services->find($id) ?? throw ResourceNotFound::make('Service');
    }
}
