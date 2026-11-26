<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Commands\Access\CreatePermissionCommand;
use App\Commands\Access\DeletePermissionCommand;
use App\Commands\Access\RenamePermissionCommand;
use App\Contracts\CommandBus;
use App\Contracts\PermissionRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

final class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionRepository $permissions,
        private readonly CommandBus $bus,
    ) {
    }

    #[OA\Get(
        path: '/api/admin/permissions',
        summary: 'Permissions',
        security: [['Bearer' => []]],
        tags: ['Access'],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function index(): AnonymousResourceCollection
    {
        return PermissionResource::collection($this->permissions->all());
    }

    #[OA\Get(
        path: '/api/admin/permissions/{id}',
        summary: 'A permission',
        security: [['Bearer' => []]],
        tags: ['Access'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function show(int $id): PermissionResource
    {
        return new PermissionResource($this->permissions->get($id));
    }

    #[OA\Post(
        path: '/api/admin/permissions',
        summary: 'Create a permission',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'name', type: 'string', example: 'reports.view')])),
        tags: ['Access'],
        responses: [new OA\Response(response: 201, description: 'OK'), new OA\Response(response: 422, description: 'Validation error')],
    )]
    public function store(PermissionRequest $request): JsonResponse
    {
        $permission = $this->bus->dispatch(new CreatePermissionCommand((string)$request->validated('name')));

        return (new PermissionResource($permission))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/admin/permissions/{id}',
        summary: 'Rename a permission',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'name', type: 'string', example: 'reports.view')])),
        tags: ['Access'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 409, description: 'Protected'), new OA\Response(response: 422, description: 'Validation error')],
    )]
    public function update(int $id, PermissionRequest $request): PermissionResource
    {
        return new PermissionResource($this->bus->dispatch(
            new RenamePermissionCommand($id, (string)$request->validated('name')),
        ));
    }

    #[OA\Delete(
        path: '/api/admin/permissions/{id}',
        summary: 'Delete a permission',
        security: [['Bearer' => []]],
        tags: ['Access'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 409, description: 'Protected')],
    )]
    public function destroy(int $id): Response
    {
        $this->bus->dispatch(new DeletePermissionCommand($id));

        return response()->noContent();
    }
}
