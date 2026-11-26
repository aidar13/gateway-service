<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Commands\Client\CreateClientCommand;
use App\Commands\Client\RevokeClientCommand;
use App\Commands\Client\SyncClientScopesCommand;
use App\Contracts\CommandBus;
use App\Contracts\OAuthClientRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\SyncClientScopesRequest;
use App\Http\Resources\ClientResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Laravel\Passport\Token;
use OpenApi\Attributes as OA;

final class ClientController extends Controller
{
    public function __construct(
        private readonly OAuthClientRepository $clients,
        private readonly CommandBus $bus,
    ) {}

    #[OA\Get(
        path: '/api/admin/clients',
        summary: 'Active OAuth clients with their scopes',
        security: [['Bearer' => []]],
        tags: ['Clients'],
        responses: [new OA\Response(response: 200, description: 'OK')],
    )]
    public function index(): AnonymousResourceCollection
    {
        return ClientResource::collection($this->clients->all());
    }

    #[OA\Get(
        path: '/api/admin/clients/{id}',
        summary: 'A client',
        security: [['Bearer' => []]],
        tags: ['Clients'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')],
    )]
    public function show(int $id): ClientResource
    {
        return new ClientResource($this->clients->get($id));
    }

    #[OA\Post(
        path: '/api/admin/clients',
        summary: 'Create a client; the secret is shown once',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'scope_ids', type: 'array', items: new OA\Items(type: 'integer'))])),
        tags: ['Clients'],
        responses: [new OA\Response(response: 201, description: 'OK'), new OA\Response(response: 422, description: 'Validation error')],
    )]
    public function store(StoreClientRequest $request): JsonResponse
    {
        $created = $this->bus->dispatch(new CreateClientCommand((string) $request->validated('name'), $request->scopeIds()));

        return (new ClientResource($created->client))
            ->additional(['secret' => $created->plainSecret])
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/admin/clients/{id}/scopes',
        summary: 'Replace the scopes of a client',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'scope_ids', type: 'array', items: new OA\Items(type: 'integer'))])),
        tags: ['Clients'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')],
    )]
    public function syncScopes(int $id, SyncClientScopesRequest $request): ClientResource
    {
        return new ClientResource($this->bus->dispatch(new SyncClientScopesCommand($id, $request->scopeIds())));
    }

    #[OA\Delete(
        path: '/api/admin/clients/{id}',
        summary: 'Revoke a client and all its tokens',
        security: [['Bearer' => []]],
        tags: ['Clients'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 409, description: 'Protected')],
    )]
    public function destroy(int $id, Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $token = $user->token();

        $this->bus->dispatch(new RevokeClientCommand($id, $token instanceof Token ? (int) $token->client_id : null));

        return response()->noContent();
    }
}
