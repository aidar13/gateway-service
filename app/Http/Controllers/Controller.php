<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Gateway Service API',
    description: 'Authentication (password and one-time code), RBAC and a reverse proxy in front of the microservices.',
)]
#[OA\SecurityScheme(securityScheme: 'Bearer', type: 'http', scheme: 'bearer')]
abstract class Controller
{
}
