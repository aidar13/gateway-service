<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Contracts\UpstreamClient;
use App\Exceptions\UpstreamUnavailable;
use App\Models\Microservice;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reverse proxy. Headers are built from scratch: only an allow-list is copied from the
 * caller, so nobody can smuggle `X-User` or the internal key through the gateway.
 */
final readonly class HttpUpstreamClient implements UpstreamClient
{
    /**
     * @param list<string> $forwardedHeaders
     */
    public function __construct(
        private Http $http,
        private string $internalKey,
        private array $forwardedHeaders,
        private int $timeoutSeconds,
    ) {
    }

    public function forward(Microservice $service, string $path, Request $request, ?User $user): Response
    {
        // Segments are re-encoded so a decoded "?", "#" or "%" cannot change which route is hit downstream.
        $encoded = implode('/', array_map(rawurlencode(...), explode('/', ltrim($path, '/'))));
        $url     = rtrim($service->base_url, '/') . '/' . $encoded;
        if (($query = $request->server->get('QUERY_STRING')) !== null && $query !== '') {
            $url .= '?' . $query;
        }

        $pending = $this->http
            ->withHeaders($this->headers($request, $user))
            ->timeout($this->timeoutSeconds)
            ->withoutRedirecting();

        try {
            $upstream = $this->attachBody($pending, $request)->send($request->method(), $url);
        } catch (ConnectionException $e) {
            throw UpstreamUnavailable::service($service->name, $e);
        }

        return response($upstream->body(), $upstream->status(), array_filter([
            'Content-Type' => $upstream->header('Content-Type'),
        ]));
    }

    /** @return array<string, string> */
    private function headers(Request $request, ?User $user): array
    {
        $headers = [];

        foreach ($this->forwardedHeaders as $name) {
            if ($request->headers->has($name)) {
                $headers[$name] = (string)$request->headers->get($name);
            }
        }

        $headers['X-Api-Key']    = $this->internalKey;
        $headers['X-Request-Id'] = (string)Str::uuid();

        if ($user !== null) {
            $headers['X-User']       = (string)$user->id;
            $headers['X-User-Email'] = (string)$user->email;
            $headers['X-User-Phone'] = (string)$user->phone;
            $headers['X-User-Roles'] = $user->getRoleNames()->implode(',');
        }

        return $headers;
    }

    /**
     * JSON and other bodies are passed through byte for byte (webhook signatures stay valid);
     * only multipart uploads have to be rebuilt.
     */
    private function attachBody(PendingRequest $pending, Request $request): PendingRequest
    {
        if ($request->allFiles() === []) {
            $content = $request->getContent();

            return $content === ''
                ? $pending
                : $pending->withBody($content, $request->header('Content-Type') ?? 'application/json');
        }

        foreach (Arr::dot($request->allFiles()) as $field => $file) {
            if ($file instanceof UploadedFile) {
                $pending->attach($this->fieldName((string)$field), (string)$file->get(), $file->getClientOriginalName());
            }
        }

        $parts = [];
        foreach (Arr::dot($request->request->all()) as $field => $value) {
            $parts[] = ['name' => $this->fieldName((string)$field), 'contents' => is_scalar($value) ? (string)$value : ''];
        }

        return $pending->asMultipart()->withOptions(['multipart' => $parts]);
    }

    /** `items.0.name` → `items[0][name]` */
    private function fieldName(string $dotted): string
    {
        $segments = explode('.', $dotted);
        $first    = array_shift($segments);

        return $first . implode('', array_map(static fn(string $s): string => "[{$s}]", $segments));
    }
}
