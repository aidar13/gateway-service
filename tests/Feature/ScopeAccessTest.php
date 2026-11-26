<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AccessScope;
use App\Models\Microservice;
use App\Models\ServiceRoute;
use App\Models\User;
use Illuminate\Support\Facades\Http;

final class ScopeAccessTest extends ApiTestCase
{
    private Microservice $service;

    private ServiceRoute $list;

    private ServiceRoute $create;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Microservice::factory()->create(['name' => 'orders', 'base_url' => 'http://orders.test']);
        $this->list = $this->service->routes()->create(['method' => 'GET', 'path' => '/api/orders', 'requires_auth' => true]);
        $this->create = $this->service->routes()->create(['method' => 'POST', 'path' => '/api/orders', 'requires_auth' => true]);
        $this->service->routes()->create(['method' => 'GET', 'path' => '/api/products', 'requires_auth' => false]);
        Http::fake(['orders.test/*' => Http::response([])]);
    }

    private function userToken(?string $scope = null): string
    {
        $user = User::factory()->create();
        $params = ['username' => (string) $user->username, 'password' => 'password-123'];

        return (string) $this->token('password', $scope === null ? $params : $params + ['scope' => $scope])
            ->assertOk()
            ->json('access_token');
    }

    public function test_private_route_is_closed_without_a_scope_but_public_one_is_open(): void
    {
        $token = $this->userToken();

        $this->freshAuth($token)->getJson('/gateway/orders/api/orders')
            ->assertForbidden()
            ->assertJsonPath('message', 'This application has no scope for the route.');
        $this->freshAuth($token)->getJson('/gateway/orders/api/products')->assertOk();
    }

    public function test_service_binding_grants_all_routes(): void
    {
        $this->grantScope('web', $this->service);
        $token = $this->userToken();

        $this->freshAuth($token)->getJson('/gateway/orders/api/orders')->assertOk();
        $this->freshAuth($token)->postJson('/gateway/orders/api/orders')->assertOk();
    }

    public function test_route_binding_grants_only_that_route(): void
    {
        $this->grantScope('orders:read')->routes()->attach($this->list->id);
        $token = $this->userToken();

        $this->freshAuth($token)->getJson('/gateway/orders/api/orders')->assertOk();
        $this->freshAuth($token)->postJson('/gateway/orders/api/orders')->assertForbidden();
    }

    public function test_inactive_scope_grants_nothing(): void
    {
        $this->grantScope('web', $this->service)->update(['is_active' => false]);

        $this->freshAuth($this->userToken())->getJson('/gateway/orders/api/orders')->assertForbidden();
    }

    public function test_token_can_be_narrowed_to_a_requested_scope(): void
    {
        $this->grantScope('orders:read')->routes()->attach($this->list->id);
        $this->grantScope('orders:write')->routes()->attach($this->create->id);

        $token = $this->userToken('orders:read');

        $this->freshAuth($token)->getJson('/gateway/orders/api/orders')->assertOk();
        $this->freshAuth($token)->postJson('/gateway/orders/api/orders')->assertForbidden();
    }

    public function test_requesting_a_scope_of_another_client_gives_nothing(): void
    {
        AccessScope::query()->create(['name' => 'partner'])->services()->attach($this->service->id);
        $this->grantScope('web');

        $this->freshAuth($this->userToken('partner'))->getJson('/gateway/orders/api/orders')->assertForbidden();
    }

    public function test_unknown_scope_is_rejected_at_the_token_endpoint(): void
    {
        $user = User::factory()->create();

        $this->token('password', ['username' => (string) $user->username, 'password' => 'password-123', 'scope' => 'nope'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_scope');
    }

    public function test_admin_manages_scopes_and_bindings_take_effect_immediately(): void
    {
        $token = $this->userToken();
        $this->asAdmin();

        $id = (int) $this->postJson('/api/admin/scopes', ['name' => 'orders', 'description' => 'Order access'])
            ->assertCreated()->json('data.id');
        $this->putJson('/api/admin/clients/'.$this->oauth->getKey().'/scopes', ['scope_ids' => [$id]])
            ->assertOk()->assertJsonPath('data.scopes', ['orders']);
        $this->putJson("/api/admin/scopes/{$id}/routes/{$this->list->id}")->assertOk()->assertJsonCount(1, 'data.routes');
        $this->freshAuth($token)->getJson('/gateway/orders/api/orders')->assertOk();

        $this->asAdmin();
        $this->deleteJson("/api/admin/scopes/{$id}/routes/{$this->list->id}")->assertOk()->assertJsonCount(0, 'data.routes');
        $this->putJson("/api/admin/scopes/{$id}/services/{$this->service->id}")->assertOk()->assertJsonCount(1, 'data.services');
        $this->patchJson("/api/admin/scopes/{$id}", ['is_active' => false])->assertOk()->assertJsonPath('data.is_active', false);
        $this->freshAuth($token)->getJson('/gateway/orders/api/orders')->assertForbidden();

        $this->asAdmin();
        $this->patchJson("/api/admin/scopes/{$id}", ['name' => 'renamed'])->assertUnprocessable();
        $this->deleteJson("/api/admin/scopes/{$id}/services/{$this->service->id}")->assertOk();
        $this->deleteJson("/api/admin/scopes/{$id}")->assertNoContent();
        $this->getJson("/api/admin/scopes/{$id}")->assertNotFound();
        $this->putJson('/api/admin/scopes/999/services/1')->assertNotFound();
    }
}
