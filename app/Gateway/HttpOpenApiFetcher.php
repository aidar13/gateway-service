<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Contracts\OpenApiFetcher;
use App\Exceptions\UpstreamUnavailable;
use App\Models\Microservice;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\RequestException;

final readonly class HttpOpenApiFetcher implements OpenApiFetcher
{
    public function __construct(private Http $http)
    {
    }

    public function fetch(Microservice $service): array
    {
        try {
            $document = $this->http
                ->timeout(10)
                ->acceptJson()
                ->get(rtrim($service->base_url, '/') . '/' . ltrim($service->openapi_path, '/'))
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException $e) {
            throw UpstreamUnavailable::service($service->name, $e);
        }

        return is_array($document) ? $document : [];
    }
}
