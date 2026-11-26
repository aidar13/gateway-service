<?php

declare(strict_types=1);

namespace App\Commands\Gateway;

use App\Contracts\Command;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** @implements Command<Response> */
final readonly class ProxyRequestCommand implements Command
{
    public function __construct(
        public string $serviceName,
        public string $path,
        public Request $request,
        public ?User $user,
    ) {}
}
