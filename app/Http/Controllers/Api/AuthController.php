<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\Logout;
use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/oauth/token',
    summary: 'Issue tokens: grant_type = password (login + password) | hash_and_code (one-time code) | refresh_token',
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['grant_type', 'client_id', 'client_secret'],
        properties: [
            new OA\Property(property: 'grant_type', type: 'string', enum: ['password', 'hash_and_code', 'refresh_token']),
            new OA\Property(property: 'client_id', type: 'string'),
            new OA\Property(property: 'client_secret', type: 'string'),
            new OA\Property(property: 'username', type: 'string', description: 'password grant: username, e-mail or phone'),
            new OA\Property(property: 'password', type: 'string'),
            new OA\Property(property: 'hash', type: 'string', description: 'hash_and_code grant'),
            new OA\Property(property: 'code', type: 'string', description: 'hash_and_code grant'),
            new OA\Property(property: 'refresh_token', type: 'string'),
        ],
    )),
    tags: ['Auth'],
    responses: [
        new OA\Response(response: 200, description: 'access_token, refresh_token, expires_in'),
        new OA\Response(response: 400, description: 'invalid_grant / invalid_request'),
        new OA\Response(response: 401, description: 'invalid_client'),
    ],
)]
final class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Create an account with a login and password',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'username', 'email', 'password'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'username', type: 'string', example: 'jane'),
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
                new OA\Property(property: 'password', type: 'string'),
            ],
        )),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 201, description: 'Registered', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function register(RegisterRequest $request, RegisterUser $action): JsonResponse
    {
        return (new UserResource($action->execute($request->toDto())))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Current user',
        security: [['Bearer' => []]],
        tags: ['Auth'],
        responses: [new OA\Response(response: 200, description: 'User', content: new OA\JsonContent(ref: '#/components/schemas/User'))],
    )]
    public function me(#[CurrentUser] User $user): UserResource
    {
        return new UserResource($user);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Revoke the current access and refresh tokens',
        security: [['Bearer' => []]],
        tags: ['Auth'],
        responses: [new OA\Response(response: 204, description: 'Logged out')],
    )]
    public function logout(#[CurrentUser] User $user, Logout $action): Response
    {
        $action->execute($user);

        return response()->noContent();
    }
}
