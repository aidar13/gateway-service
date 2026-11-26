<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;

final class ClientManagementTest extends ApiTestCase
{
    public function test_new_client_can_sign_users_in_and_revoking_it_kills_its_tokens(): void
    {
        $scope = $this->grantScope('web');
        $user = User::factory()->create();
        $this->asAdmin();

        $response = $this->postJson('/api/admin/clients', ['name' => 'mobile', 'scope_ids' => [$scope->id]])
            ->assertCreated()
            ->assertJsonPath('data.scopes', ['web']);
        $id = (int) $response->json('data.id');
        $secret = (string) $response->json('secret');
        $this->getJson("/api/admin/clients/{$id}")->assertOk()->assertJsonMissingPath('secret');

        $login = [
            'grant_type' => 'password', 'client_id' => (string) $id, 'client_secret' => $secret,
            'username' => (string) $user->username, 'password' => 'password-123',
        ];
        $token = (string) $this->freshAuth()->postJson('/oauth/token', $login)->assertOk()->json('access_token');
        $this->freshAuth($token)->getJson('/api/auth/me')->assertOk();

        $this->asAdmin()->deleteJson("/api/admin/clients/{$id}")->assertNoContent();

        $this->freshAuth($token)->getJson('/api/auth/me')->assertUnauthorized();
        $this->freshAuth()->postJson('/oauth/token', $login)->assertUnauthorized();
    }

    public function test_admin_cannot_revoke_the_client_in_use(): void
    {
        $this->asAdmin()->deleteJson('/api/admin/clients/'.$this->oauth->getKey())->assertStatus(409);
    }

    public function test_listing_and_validation(): void
    {
        $this->asAdmin();

        $this->getJson('/api/admin/clients')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/admin/clients', ['name' => 'x', 'scope_ids' => [999]])->assertUnprocessable();
        $this->putJson('/api/admin/clients/999/scopes', ['scope_ids' => []])->assertNotFound();
    }
}
