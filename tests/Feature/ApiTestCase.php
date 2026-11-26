<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected Client $oauth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->oauth = $this->setUpAuth();
    }

    /**
     * @param  array<string, string>  $params
     * @return TestResponse<Response>
     */
    protected function token(string $grant, array $params): TestResponse
    {
        return $this->postJson('/oauth/token', [
            'grant_type' => $grant,
            'client_id' => (string) $this->oauth->getKey(),
            'client_secret' => (string) $this->oauth->plainSecret,
            ...$params,
        ]);
    }

    protected function accessTokenFor(User $user): string
    {
        return (string) $this->token('password', ['username' => (string) $user->username, 'password' => 'password-123'])
            ->assertOk()
            ->json('access_token');
    }

    /** The test client keeps guard instances between requests; drop them like a new request would. */
    protected function freshAuth(?string $token = null): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $token === null ? $this : $this->withToken($token);
    }
}
