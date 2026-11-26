<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;

final class PasswordLoginTest extends ApiTestCase
{
    public function test_register_then_login_and_read_profile(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Jane', 'username' => 'jane', 'email' => 'jane@example.test', 'password' => 'password-123',
        ])->assertCreated()->assertJsonPath('data.roles.0', 'client');

        $token = $this->token('password', ['username' => 'jane', 'password' => 'password-123'])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'refresh_token', 'expires_in'])
            ->json('access_token');

        $this->freshAuth($token)->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.username', 'jane');
    }

    /** @return array<string, array{string}> */
    public static function logins(): array
    {
        return ['username' => ['username'], 'email' => ['email'], 'phone' => ['phone']];
    }

    #[DataProvider('logins')]
    public function test_login_accepts_username_email_or_phone(string $field): void
    {
        $user = User::factory()->create();

        $this->token('password', ['username' => (string)$user->getAttribute($field), 'password' => 'password-123'])->assertOk();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->token('password', ['username' => (string)$user->username, 'password' => 'nope-nope-nope'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_inactive_and_passwordless_accounts_cannot_log_in(): void
    {
        $inactive     = User::factory()->inactive()->create();
        $passwordless = User::factory()->passwordless()->create();

        $this->token('password', ['username' => (string)$inactive->username, 'password' => 'password-123'])->assertStatus(400);
        $this->token('password', ['username' => (string)$passwordless->phone, 'password' => md5((string)$passwordless->phone)])->assertStatus(400);
    }

    public function test_refresh_token_issues_a_new_pair(): void
    {
        $user    = User::factory()->create();
        $refresh = $this->token('password', ['username' => (string)$user->username, 'password' => 'password-123'])->json('refresh_token');

        $this->token('refresh_token', ['refresh_token' => (string)$refresh])->assertOk()->assertJsonStructure(['access_token']);
        $this->token('refresh_token', ['refresh_token' => (string)$refresh])->assertStatus(401);
    }

    public function test_logout_revokes_tokens(): void
    {
        $user   = User::factory()->create();
        $pair   = $this->token('password', ['username' => (string)$user->username, 'password' => 'password-123']);
        $access = (string)$pair->json('access_token');

        $this->freshAuth($access)->postJson('/api/auth/logout')->assertNoContent();

        $this->freshAuth($access)->getJson('/api/auth/me')->assertUnauthorized();
        $this->freshAuth()->token('refresh_token', ['refresh_token' => (string)$pair->json('refresh_token')])->assertStatus(401);
    }

    public function test_deactivation_takes_effect_for_issued_tokens(): void
    {
        $user  = User::factory()->create();
        $token = $this->accessTokenFor($user);
        $user->update(['is_active' => false]);

        $this->freshAuth($token)->getJson('/api/auth/me')->assertForbidden();
    }
}
