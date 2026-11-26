<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Commands\Scope\CreateScopeCommand;
use App\Commands\Scope\DeleteScopeCommand;
use App\Commands\Scope\UpdateScopeCommand;
use App\Contracts\CommandBus;
use App\Contracts\ScopeRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScopeRequest;
use App\Http\Requests\UpdateScopeRequest;
use App\Http\Resources\ScopeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

final class ScopeController extends Controller
{
    public function __construct(
        private readonly ScopeRepository $scopes,
        private readonly CommandBus $bus,
    ) {}

    #[OA\Get(
        path: '/api/admin/scopes',
        summary: 'Scopes',
        security: [['Bearer' => []]],
        tags: ['Scopes'],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function index(): AnonymousResourceCollection
    {
        return ScopeResource::collection($this->scopes->all());
    }

    #[OA\Get(
        path: '/api/admin/scopes/{id}',
        summary: 'A scope with its services and routes',
        security: [['Bearer' => []]],
        tags: ['Scopes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function show(int $id): ScopeResource
    {
        return new ScopeResource($this->scopes->get($id));
    }

    #[OA\Post(
        path: '/api/admin/scopes',
        summary: 'Create a scope',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'name', type: 'string', example: 'orders:read'), new OA\Property(property: 'description', type: 'string'), new OA\Property(property: 'is_active', type: 'boolean')])),
        tags: ['Scopes'],
        responses: [new OA\Response(response: 201, description: 'OK'), new OA\Response(response: 422, description: 'Validation error')],
    )]
    public function store(StoreScopeRequest $request): JsonResponse
    {
        $scope = $this->bus->dispatch(new CreateScopeCommand(
            (string) $request->validated('name'),
            $request->filled('description') ? $request->string('description')->toString() : null,
            $request->boolean('is_active', true),
        ));

        return (new ScopeResource($scope))->response()->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/api/admin/scopes/{id}',
        summary: 'Change description or activity (the name is immutable)',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'description', type: 'string'), new OA\Property(property: 'is_active', type: 'boolean')])),
        tags: ['Scopes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')],
    )]
    public function update(int $id, UpdateScopeRequest $request): ScopeResource
    {
        return new ScopeResource($this->bus->dispatch(new UpdateScopeCommand($id, $request->changes())));
    }

    #[OA\Delete(
        path: '/api/admin/scopes/{id}',
        summary: 'Delete a scope',
        security: [['Bearer' => []]],
        tags: ['Scopes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'OK'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function destroy(int $id): Response
    {
        $this->bus->dispatch(new DeleteScopeCommand($id));

        return response()->noContent();
    }
}
