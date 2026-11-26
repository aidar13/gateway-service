<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Microservice;
use Illuminate\Support\Facades\Http;

final class ServiceManagementTest extends ApiTestCase
{
    public function test_update_switch_off_and_delete(): void
    {
        $service = Microservice::factory()->create(['name' => 'billing']);
        $service->routes()->create(['method' => 'GET', 'path' => '/api/ping', 'requires_auth' => false]);
        $scope = $this->grantScope('web', $service);
        $this->asAdmin();

        $this->patchJson("/api/admin/services/{$service->id}", ['base_url' => 'http://billing-v2', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.base_url', 'http://billing-v2')
            ->assertJsonPath('data.is_active', false);
        $this->getJson('/gateway/billing/api/ping')->assertNotFound();
        $this->patchJson("/api/admin/services/{$service->id}", ['base_url' => 'not-a-url'])->assertUnprocessable();

        $this->deleteJson("/api/admin/services/{$service->id}")->assertNoContent();
        $this->assertDatabaseCount('service_routes', 0);
        $this->assertSame(0, $scope->services()->count());
        $this->getJson("/api/admin/services/{$service->id}")->assertNotFound();
    }

    public function test_reimport_keeps_scope_bindings_of_surviving_routes(): void
    {
        Http::fake(['billing.test/*' => Http::sequence()
            ->push(['openapi' => '3.0.0', 'paths' => ['/api/a' => ['get' => []], '/api/b' => ['get' => []]]])
            ->push(['openapi' => '3.0.0', 'paths' => ['/api/a' => ['get' => ['summary' => 'Kept']], '/api/c' => ['get' => []]]])]);
        $service = Microservice::factory()->create(['base_url' => 'http://billing.test']);
        $scope = $this->grantScope('web');
        $this->asAdmin();

        $this->postJson("/api/admin/services/{$service->id}/import-routes")->assertOk();
        $scope->routes()->attach($service->routes()->pluck('id')->all());
        $keptId = $service->routes()->where('path', '/api/a')->value('id');

        $this->postJson("/api/admin/services/{$service->id}/import-routes")->assertOk()->assertJsonCount(2, 'data.routes');

        $this->assertSame([$keptId], $scope->routes()->pluck('service_routes.id')->all());
        $this->assertDatabaseHas('service_routes', ['id' => $keptId, 'summary' => 'Kept']);
    }

    public function test_route_list_can_be_filtered(): void
    {
        $service = Microservice::factory()->create();
        $service->routes()->createMany([
            ['method' => 'GET', 'path' => '/api/invoices'],
            ['method' => 'GET', 'path' => '/api/payments'],
        ]);

        $this->asAdmin()
            ->getJson("/api/admin/routes?service_id={$service->id}&search=invo")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.path', '/api/invoices');
    }
}
