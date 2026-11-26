<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Microservice;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

final class RegistryAndProxyTest extends ApiTestCase
{
    private Microservice $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['gateway.internal_api_key' => 'internal-key']);
        $this->service = Microservice::factory()->create(['name' => 'orders', 'base_url' => 'http://orders.test']);
        $this->service->routes()->createMany([
            ['method' => 'GET', 'path' => '/api/orders/{id}', 'requires_auth' => true],
            ['method' => 'POST', 'path' => '/api/payments/webhook', 'requires_auth' => false],
            ['method' => 'GET', 'path' => '/api/reports', 'requires_auth' => true, 'permission' => 'services.manage'],
        ]);
        // The test application may use the whole service; scopes have their own tests.
        $this->grantScope('web', $this->service);
    }

    public function test_registry_is_for_admins_only(): void
    {
        $this->getJson('/api/admin/services')->assertUnauthorized();

        $client = User::factory()->create();
        $client->assignRole('client');
        $this->freshAuth($this->accessTokenFor($client))->getJson('/api/admin/services')->assertForbidden();

        $this->freshAuth($this->accessTokenFor($this->admin()))->getJson('/api/admin/services')->assertOk();
    }

    public function test_admin_registers_a_service_and_imports_its_routes(): void
    {
        Http::fake(['billing.test/*' => Http::response([
            'openapi' => '3.0.0',
            'paths' => [
                '/api/invoices' => ['get' => ['summary' => 'List'], 'post' => ['x-permission' => 'services.manage']],
                '/api/health' => ['get' => ['x-unauthenticated' => true]],
            ],
        ])]);
        $this->freshAuth($this->accessTokenFor($this->admin()));

        $id = (int) $this->postJson('/api/admin/services', ['name' => 'billing', 'base_url' => 'http://billing.test'])
            ->assertCreated()->json('data.id');

        $this->postJson("/api/admin/services/{$id}/import-routes")->assertOk()->assertJsonCount(3, 'data.routes');
        $this->assertDatabaseHas('service_routes', ['microservice_id' => $id, 'path' => '/api/health', 'requires_auth' => false]);
    }

    public function test_broken_document_keeps_previous_routes(): void
    {
        Http::fake(['orders.test/*' => Http::response('oops', 500)]);

        $this->freshAuth($this->accessTokenFor($this->admin()))
            ->postJson("/api/admin/services/{$this->service->id}/import-routes")
            ->assertStatus(502);

        $this->assertDatabaseCount('service_routes', 3);
    }

    public function test_protected_route_requires_a_token(): void
    {
        Http::fake();

        $this->getJson('/gateway/orders/api/orders/5')->assertUnauthorized();
        $this->withToken('garbage')->getJson('/gateway/orders/api/orders/5')->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_forwards_identity_and_ignores_spoofed_headers(): void
    {
        Http::fake(['orders.test/*' => Http::response(['data' => ['id' => 5]], 200)]);
        $user = User::factory()->create();

        $this->freshAuth($this->accessTokenFor($user))
            ->withHeaders(['X-User' => '999', 'X-Api-Key' => 'forged', 'X-Evil' => '1'])
            ->getJson('/gateway/orders/api/orders/5?expand=items')
            ->assertOk()
            ->assertJsonPath('data.id', 5);

        Http::assertSent(fn (Request $r) => $r->url() === 'http://orders.test/api/orders/5?expand=items'
            && $r->header('X-User') === [(string) $user->id]
            && $r->header('X-User-Email') === [(string) $user->email]
            && $r->header('X-Api-Key') === ['internal-key']
            && ! $r->hasHeader('X-Evil')
            && ! $r->hasHeader('Authorization'));
    }

    public function test_public_route_passes_raw_body_and_signature_untouched(): void
    {
        Http::fake(['orders.test/*' => Http::response(['ok' => true])]);
        $body = '{"payment_id":"p-1",  "status":"succeeded"}';

        $this->call('POST', '/gateway/orders/api/payments/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => 'abc', 'HTTP_X_USER' => '1',
        ], $body)->assertOk();

        Http::assertSent(fn (Request $r) => $r->body() === $body
            && $r->header('X-Signature') === ['abc']
            && ! $r->hasHeader('X-User'));
    }

    public function test_permission_protected_route(): void
    {
        Http::fake(['orders.test/*' => Http::response([])]);

        $this->freshAuth($this->accessTokenFor(User::factory()->create()))
            ->getJson('/gateway/orders/api/reports')->assertForbidden();
        $this->freshAuth($this->accessTokenFor($this->admin()))
            ->getJson('/gateway/orders/api/reports')->assertOk();
    }

    public function test_unregistered_route_or_inactive_service_is_404(): void
    {
        Http::fake();

        $this->getJson('/gateway/orders/api/secret')->assertNotFound();
        $this->deleteJson('/gateway/orders/api/payments/webhook')->assertNotFound();
        $this->getJson('/gateway/unknown/api/orders/1')->assertNotFound();

        $this->service->update(['is_active' => false]);
        $this->postJson('/gateway/orders/api/payments/webhook')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_upstream_status_is_preserved_and_outage_becomes_502(): void
    {
        Http::fakeSequence('orders.test/*')->push(['message' => 'nope'], 422);
        $this->postJson('/gateway/orders/api/payments/webhook', ['a' => 1])->assertStatus(422);

        $this->app->forgetInstance(Factory::class);
        Http::clearResolvedInstances();
        Http::fake(fn () => throw new ConnectionException('refused'));
        $this->postJson('/gateway/orders/api/payments/webhook', ['a' => 1])->assertStatus(502);
    }

    public function test_encoded_delimiters_cannot_change_the_downstream_route(): void
    {
        Http::fake(['orders.test/*' => Http::response([])]);

        $this->withToken($this->accessTokenFor(User::factory()->create()))
            ->getJson('/gateway/orders/api/orders/5%3Fadmin%23x')
            ->assertOk();

        Http::assertSent(fn (Request $r) => $r->url() === 'http://orders.test/api/orders/5%3Fadmin%23x');
    }
}
