<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\CodeGenerator;
use App\Jobs\DeliverOtp;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FixedCodeGenerator;

final class OtpLoginTest extends ApiTestCase
{
    private const PHONE = '+15550009999';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(CodeGenerator::class, new FixedCodeGenerator('123456'));
    }

    private function requestCode(string $phone = self::PHONE): string
    {
        return (string) $this->postJson('/api/auth/code', ['phone' => $phone])->assertCreated()->json('data.hash');
    }

    public function test_requesting_a_code_stores_only_a_hash_and_creates_no_account(): void
    {
        Queue::fake();

        $this->requestCode();

        $this->assertNotSame('123456', OtpCode::query()->firstOrFail()->code_hash);
        $this->assertDatabaseCount('users', 0);
        Queue::assertPushedOn('otp', DeliverOtp::class);
    }

    public function test_confirming_the_code_creates_the_account_and_returns_tokens(): void
    {
        $hash = $this->requestCode();

        $token = $this->token('hash_and_code', ['hash' => $hash, 'code' => '123456'])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'refresh_token'])
            ->json('access_token');

        $user = User::query()->where('phone', self::PHONE)->firstOrFail();
        $this->assertNull($user->password);
        $this->assertTrue($user->hasRole('client'));
        $this->freshAuth($token)->getJson('/api/auth/me')->assertJsonPath('data.phone', self::PHONE);
    }

    public function test_existing_user_is_reused(): void
    {
        $user = User::factory()->create(['email' => 'kim@example.test']);
        $hash = (string) $this->postJson('/api/auth/code', ['email' => 'KIM@example.test'])->json('data.hash');

        $this->token('hash_and_code', ['hash' => $hash, 'code' => '123456'])->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($user->id, User::query()->firstOrFail()->id);
    }

    public function test_code_works_only_once(): void
    {
        $hash = $this->requestCode();

        $this->token('hash_and_code', ['hash' => $hash, 'code' => '123456'])->assertOk();
        $this->token('hash_and_code', ['hash' => $hash, 'code' => '123456'])->assertStatus(400);
    }

    public function test_wrong_code_spends_the_attempt_budget(): void
    {
        config(['gateway.otp.max_attempts' => 3]);
        $hash = $this->requestCode();

        foreach (range(1, 3) as $ignored) {
            $this->token('hash_and_code', ['hash' => $hash, 'code' => '000000'])->assertStatus(400);
        }

        $this->token('hash_and_code', ['hash' => $hash, 'code' => '123456'])->assertStatus(400);
        $this->assertSame(3, OtpCode::query()->firstOrFail()->attempts);
    }

    public function test_expired_code_is_rejected(): void
    {
        $hash = $this->requestCode();

        $this->travel(6)->minutes();

        $this->token('hash_and_code', ['hash' => $hash, 'code' => '123456'])->assertStatus(400);
    }

    public function test_cooldown_between_requests(): void
    {
        $this->requestCode();

        $this->postJson('/api/auth/code', ['phone' => self::PHONE])->assertStatus(429);

        $this->travel(61)->seconds();
        $this->requestCode();
    }

    public function test_repeat_sends_a_new_code_but_keeps_spent_attempts_and_has_a_limit(): void
    {
        config(['gateway.otp.max_resends' => 1]);
        $hash = $this->requestCode();
        $this->token('hash_and_code', ['hash' => $hash, 'code' => '000000']);

        $this->postJson('/api/auth/code-repeat', ['hash' => $hash])->assertStatus(429);
        $this->travel(61)->seconds();
        $this->postJson('/api/auth/code-repeat', ['hash' => $hash])->assertOk();
        $this->assertSame(1, OtpCode::query()->firstOrFail()->attempts);

        $this->travel(61)->seconds();
        $this->postJson('/api/auth/code-repeat', ['hash' => $hash])->assertStatus(429);
    }

    public function test_exactly_one_destination_is_required(): void
    {
        $this->postJson('/api/auth/code', [])->assertUnprocessable();
        $this->postJson('/api/auth/code', ['phone' => self::PHONE, 'email' => 'a@b.test'])->assertUnprocessable();
        $this->postJson('/api/auth/code', ['phone' => '8700'])->assertUnprocessable();
    }

    public function test_code_is_sent_to_the_notification_service(): void
    {
        config(['gateway.notifications.url' => 'http://notifications.test', 'gateway.notifications.api_key' => 'n-key']);
        Http::fake(['notifications.test/*' => Http::response([], 202)]);

        $this->requestCode();

        Http::assertSent(fn ($request) => $request->url() === 'http://notifications.test/api/notifications'
            && $request->hasHeader('X-Api-Key', 'n-key')
            && $request['event'] === 'auth.otp'
            && $request['recipient'] === ['phone' => self::PHONE]
            && $request['payload']['code'] === '123456');
    }

    public function test_auth_endpoints_are_rate_limited(): void
    {
        foreach (range(1, 10) as $i) {
            $this->postJson('/api/auth/code', ['phone' => '+1555000000'.$i]);
        }

        $this->postJson('/api/auth/code', ['phone' => '+15550001111'])->assertStatus(429);
    }

    public function test_owner_of_a_contact_takes_over_an_unverified_registration(): void
    {
        // Someone registers with a phone that is not theirs and keeps a session.
        $this->postJson('/api/auth/register', [
            'name' => 'Mallory', 'username' => 'mallory', 'email' => 'mallory@example.test',
            'phone' => self::PHONE, 'password' => 'password-123',
        ])->assertCreated();
        $stolen = (string) $this->token('password', ['username' => 'mallory', 'password' => 'password-123'])->json('access_token');

        $hash = $this->requestCode();
        $this->token('hash_and_code', ['hash' => $hash, 'code' => '123456'])->assertOk();

        $user = User::query()->where('phone', self::PHONE)->firstOrFail();
        $this->assertNull($user->password);
        $this->assertNull($user->email);
        $this->freshAuth($stolen)->getJson('/api/auth/me')->assertUnauthorized();
        $this->freshAuth()->token('password', ['username' => 'mallory', 'password' => 'password-123'])->assertStatus(400);
    }

    public function test_second_code_sign_in_changes_nothing(): void
    {
        $first = (string) $this->token('hash_and_code', ['hash' => $this->requestCode(), 'code' => '123456'])->json('access_token');

        $this->token('hash_and_code', ['hash' => $this->requestCode(), 'code' => '123456'])->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->freshAuth($first)->getJson('/api/auth/me')->assertOk();
    }
}
