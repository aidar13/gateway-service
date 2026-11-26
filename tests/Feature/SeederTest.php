<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AccessScope;
use App\Models\Microservice;
use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

final class SeederTest extends ApiTestCase
{
    /** @return TestResponse<Response> */
    private function signIn(string $username, string $password = 'password-123'): TestResponse
    {
        $web = OAuthClient::query()->where('name', 'web')->firstOrFail();

        return $this->freshAuth()->token('password', [
            'client_id'     => (string)$web->getKey(),
            'client_secret' => 'local-web-client-secret',
            'username'      => $username,
            'password'      => $password,
        ]);
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return [
            'users'    => User::query()->count(),
            'roles'    => Role::query()->count(),
            'services' => Microservice::query()->count(),
            'scopes'   => AccessScope::query()->count(),
            'clients'  => OAuthClient::query()->count(),
        ];
    }

    public function test_seeders_fill_the_platform_and_can_run_again(): void
    {
        $this->seed();
        $first = $this->counts();
        // The extra client is the one the tests create for themselves.
        $this->assertSame(['users' => 7, 'roles' => 5, 'services' => 1, 'scopes' => 2, 'clients' => 3], $first);

        $this->seed();
        $this->assertSame($first, $this->counts());
    }

    public function test_manual_changes_survive_a_second_run(): void
    {
        $this->seed();
        Role::findByName('auditor', 'api')->revokePermissionTo('clients.view');
        User::query()->where('username', 'client')->update(['name' => 'Renamed']);

        $this->seed();

        $this->assertFalse(Role::findByName('auditor', 'api')->hasPermissionTo('clients.view'));
        $this->assertDatabaseHas('users', ['username' => 'client', 'name' => 'Renamed']);
    }

    public function test_seeded_accounts_get_exactly_their_access(): void
    {
        $this->seed();

        $this->signIn('admin', 'admin-password-123')->assertOk();
        $this->signIn('blocked')->assertStatus(400);

        $token = (string)$this->signIn('auditor')->assertOk()->json('access_token');
        $this->freshAuth($token)->getJson('/api/admin/services')->assertOk();
        $this->freshAuth($token)->postJson('/api/admin/services', [])->assertForbidden();
    }

    public function test_seeded_contacts_are_verified(): void
    {
        $this->seed();

        $this->assertNotNull(User::query()->where('username', 'admin')->firstOrFail()->getAttribute('email_verified_at'));
        $phoneOnly = User::query()->where('phone', '+15550000002')->firstOrFail();
        $this->assertNull($phoneOnly->password);
        $this->assertNotNull($phoneOnly->getAttribute('phone_verified_at'));
    }

    public function test_demo_accounts_are_not_created_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertSame(['admin'], User::query()->pluck('username')->all());
        $this->assertSame(2, Role::query()->count());
        $this->assertDatabaseMissing('oauth_clients', ['name' => 'mobile']);
    }
}
