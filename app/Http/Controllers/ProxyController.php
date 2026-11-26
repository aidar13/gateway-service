<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Gateway\ProxyRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProxyController extends Controller
{
    public function __invoke(string $service, string $path, Request $request, ProxyRequest $action): Response
    {
        /** @var User|null $user null for anonymous callers and for invalid or revoked tokens */
        $user = $request->user('api');

        return $action->execute($service, '/'.ltrim($path, '/'), $request, $user?->is_active ? $user : null);
    }
}
