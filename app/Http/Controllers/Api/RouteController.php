<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\ServiceRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRoutesRequest;
use App\Http\Resources\ServiceRouteResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

final class RouteController extends Controller
{
    public function __construct(
        private readonly ServiceRepository $services,
    ) {}

    #[OA\Get(
        path: '/api/admin/routes',
        summary: 'Registered routes of all services',
        security: [['Bearer' => []]],
        tags: ['Registry'],
        parameters: [
            new OA\Parameter(name: 'service_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', maximum: 200)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated routes')],
    )]
    public function index(ListRoutesRequest $request): AnonymousResourceCollection
    {
        return ServiceRouteResource::collection(
            $this->services->paginateRoutes($request->serviceId(), $request->search(), $request->perPage()),
        );
    }
}
