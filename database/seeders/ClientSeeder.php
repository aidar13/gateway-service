<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Concerns\CreatesPasswordClient;
use Illuminate\Database\Seeder;

/** The first-party OAuth client for the password and hash_and_code grants. */
final class ClientSeeder extends Seeder
{
    use CreatesPasswordClient;

    public const NAME = 'web';

    public function run(): void
    {
        $client = $this->passwordClient(self::NAME, (string)config('gateway.seed.client_secret'));

        $this->command->info("OAuth client '" . self::NAME . "': id {$client->getKey()} (secret: SEED_CLIENT_SECRET)");
    }
}
