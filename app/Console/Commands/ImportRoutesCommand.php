<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Commands\Gateway\ImportServiceRoutesCommand;
use App\Contracts\CommandBus;
use App\Contracts\ServiceRepository;
use Illuminate\Console\Command;
use Throwable;

final class ImportRoutesCommand extends Command
{
    protected $signature = 'gateway:import-routes {service? : Service name; all services when omitted}';

    protected $description = 'Import routes from the OpenAPI documents of registered services';

    public function handle(ServiceRepository $services, CommandBus $bus): int
    {
        $name = $this->argument('service');
        $targets = $services->all()->filter(static fn ($s): bool => $name === null || $s->name === $name);

        if ($targets->isEmpty()) {
            $this->error('No matching services.');

            return self::FAILURE;
        }

        $failed = false;
        foreach ($targets as $service) {
            try {
                $count = $bus->dispatch(new ImportServiceRoutesCommand($service));
                $this->info("{$service->name}: {$count} routes");
            } catch (Throwable $e) {
                $failed = true;
                $this->error("{$service->name}: {$e->getMessage()}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
