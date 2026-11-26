<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\OtpGrant;
use App\Auth\OtpSettings;
use App\Auth\RandomCodeGenerator;
use App\Contracts\CodeGenerator;
use App\Contracts\CommandBus;
use App\Contracts\NotificationClient;
use App\Contracts\OpenApiFetcher;
use App\Contracts\OtpRepository;
use App\Contracts\ServiceRepository;
use App\Contracts\UpstreamClient;
use App\Contracts\UserRepository;
use App\Gateway\HttpOpenApiFetcher;
use App\Gateway\HttpUpstreamClient;
use App\Notifications\HttpNotificationClient;
use App\Notifications\LogNotificationClient;
use App\Repositories\EloquentOtpRepository;
use App\Repositories\EloquentServiceRepository;
use App\Repositories\EloquentUserRepository;
use DateInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Log\LoggerInterface;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Only /oauth/token is exposed (declared in routes/gateway.php).
        Passport::ignoreRoutes();

        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(OtpRepository::class, EloquentOtpRepository::class);
        $this->app->bind(ServiceRepository::class, EloquentServiceRepository::class);
        $this->app->bind(OpenApiFetcher::class, HttpOpenApiFetcher::class);

        $this->app->bind(CodeGenerator::class, fn () => new RandomCodeGenerator((int) config('gateway.otp.length')));

        $this->app->bind(OtpSettings::class, fn () => new OtpSettings(
            (int) config('gateway.otp.ttl_minutes'),
            (int) config('gateway.otp.max_attempts'),
            (int) config('gateway.otp.resend_cooldown_seconds'),
            (int) config('gateway.otp.max_resends'),
        ));

        $this->app->bind(UpstreamClient::class, function (Application $app): UpstreamClient {
            /** @var list<string> $headers */
            $headers = config('gateway.proxy.forwarded_headers');

            return new HttpUpstreamClient(
                $app->make(Http::class),
                (string) config('gateway.internal_api_key'),
                $headers,
                (int) config('gateway.proxy.timeout'),
            );
        });

        $this->app->bind(NotificationClient::class, function (Application $app): NotificationClient {
            $url = (string) config('gateway.notifications.url');

            return $url === ''
                ? new LogNotificationClient($app->make(LoggerInterface::class), $app->environment('local', 'testing'))
                : new HttpNotificationClient(
                    $app->make(Http::class),
                    $url,
                    (string) config('gateway.notifications.api_key'),
                );
        });
    }

    public function boot(): void
    {
        Passport::enablePasswordGrant();
        $accessTtl = new DateInterval('PT1H');
        $refreshTtl = new DateInterval('P30D');
        Passport::tokensExpireIn($accessTtl);
        Passport::refreshTokensExpireIn($refreshTtl);

        $this->app->afterResolving(AuthorizationServer::class, function (AuthorizationServer $server) use ($accessTtl, $refreshTtl): void {
            $grant = new OtpGrant(
                $this->app->make(CommandBus::class),
                $this->app->make(RefreshTokenRepository::class),
            );
            $grant->setRefreshTokenTTL($refreshTtl);

            $server->enableGrantType($grant, $accessTtl);
        });

        // Brute-force protection for everything that checks or sends a secret.
        RateLimiter::for('auth', static fn (Request $request) => Limit::perMinute(10)->by((string) $request->ip()));
        RateLimiter::for('gateway', static fn (Request $request) => Limit::perMinute(120)->by(
            (string) ($request->user('api')?->getAuthIdentifier() ?? $request->ip()),
        ));
    }
}
