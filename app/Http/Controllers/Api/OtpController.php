<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Commands\Auth\IssueOtpCommand;
use App\Commands\Auth\ResendOtpCommand;
use App\Contracts\CommandBus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OtpRequest;
use App\Http\Requests\RepeatOtpRequest;
use App\Http\Resources\OtpResource;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class OtpController extends Controller
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Post(
        path: '/api/auth/code',
        summary: 'Send a one-time code to a phone or e-mail (exactly one of them)',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'phone', type: 'string', example: '+15550001122'),
            new OA\Property(property: 'email', type: 'string'),
        ])),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 201, description: 'Code sent', content: new OA\JsonContent(ref: '#/components/schemas/Otp')),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Cooldown or rate limit'),
        ],
    )]
    public function store(OtpRequest $request): JsonResponse
    {
        return (new OtpResource($this->bus->dispatch(new IssueOtpCommand($request->destination()))))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/api/auth/code-repeat',
        summary: 'Send a new code for an existing request',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'hash', type: 'string', format: 'uuid'),
        ])),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Code sent', content: new OA\JsonContent(ref: '#/components/schemas/Otp')),
            new OA\Response(response: 404, description: 'Request not active'),
            new OA\Response(response: 429, description: 'Cooldown or resend limit'),
        ],
    )]
    public function repeat(RepeatOtpRequest $request): OtpResource
    {
        return new OtpResource($this->bus->dispatch(new ResendOtpCommand($request->otpHash())));
    }
}
