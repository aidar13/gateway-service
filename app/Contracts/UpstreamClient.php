<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\UpstreamUnavailable;
use App\Models\Microservice;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface UpstreamClient
{
    /** @throws UpstreamUnavailable */
    public function forward(Microservice $service, string $path, Request $request, ?User $user): Response;
}
