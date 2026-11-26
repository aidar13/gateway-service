<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Commands\Access\CreateRoleCommand;
use App\Commands\Access\DeleteRoleCommand;
use App\Commands\Access\UpdateRoleCommand;
use App\Contracts\CommandBus;
use App\Contracts\RoleRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

final class RoleController extends Controller
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly CommandBus $bus,
    ) {}

    #[OA\Get(
        path: '/api/admin/roles',
        summary: 'Roles with their permissions',
        security: [['Bearer' => []]],
        tags: ['Access'],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection($this->roles->all());
    }

    #[OA\Get(
        path: '/api/admin/roles/{id}',
        summary: 'A role',
        security: [['Bearer' => []]],
        tags: ['Access'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function show(int $id): RoleResource
    {
        return new RoleResource($this->roles->get($id));
    }

    #[OA\Post(
        path: '/api/admin/roles',
        summary: 'Create a role',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'name', type: 'string', example: 'manager'), new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'))])),
        tags: ['Access'],
        responses: [new OA\Response(response: 201, description: 'OK'), new OA\Response(response: 422, description: 'Validation error')],
    )]
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->bus->dispatch(new CreateRoleCommand(
            (string) $request->validated('name'),
            $request->permissionNames() ?? [],
        ));

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/admin/roles/{id}',
        summary: 'Rename a role and/or replace its permissions',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'name', type: 'string', example: 'manager'), new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'))])),
        tags: ['Access'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 409, description: 'Protected'), new OA\Response(response: 422, description: 'Validation error')],
    )]
    public function update(int $id, UpdateRoleRequest $request): RoleResource
    {
        return new RoleResource($this->bus->dispatch(
            new UpdateRoleCommand($id, $request->roleName(), $request->permissionNames()),
        ));
    }

    #[OA\Delete(
        path: '/api/admin/roles/{id}',
        summary: 'Delete a role',
        security: [['Bearer' => []]],
        tags: ['Access'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 409, description: 'Protected')],
    )]
    public function destroy(int $id): Response
    {
        $this->bus->dispatch(new DeleteRoleCommand($id));

        return response()->noContent();
    }
}
