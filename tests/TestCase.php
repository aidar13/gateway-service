<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    /** Creates throw-away signing keys, roles and an OAuth client. */
    protected function setUpAuth(): Client
    {
        if (!file_exists(Passport::keyPath('oauth-private.key'))) {
            Artisan::call('passport:keys', ['--force' => true]);
        }

        $this->seed(RoleSeeder::class);

        return app(ClientRepository::class)->createPasswordGrantClient(null, 'tests', 'http://localhost');
    }
}
