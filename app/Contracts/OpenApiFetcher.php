<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\UpstreamUnavailable;
use App\Models\Microservice;

interface OpenApiFetcher
{
    /**
     * @return array<array-key, mixed>
     *
     * @throws UpstreamUnavailable
     */
    public function fetch(Microservice $service): array;
}
