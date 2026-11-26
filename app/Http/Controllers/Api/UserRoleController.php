<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\UserRepository;
use App\Exceptions\ResourceNotFound;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRolesRequest;
use App\Http\Resources\UserResource;
use OpenApi\Attributes as OA;

final class UserRoleController extends Controller
{
    #[OA\Put(
        path: '/api/admin/users/{id}/roles',
        summary: 'Replace the roles of a user',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', enum: ['admin', 'client'])),
        ])),
        tags: ['Access'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Updated user')],
    )]
    public function __invoke(int $id, AssignRolesRequest $request, UserRepository $users): UserResource
    {
        $user = $users->find($id) ?? throw ResourceNotFound::make('User');
        $user->syncRoles($request->roles());

        return new UserResource($user);
    }
}
