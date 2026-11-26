<?php

declare(strict_types=1);

namespace App\Providers;

use App\Bus\LaravelCommandBus;
use App\Commands\Auth\AssignRolesCommand;
use App\Commands\Auth\IssueOtpCommand;
use App\Commands\Auth\LogoutCommand;
use App\Commands\Auth\RegisterUserCommand;
use App\Commands\Auth\ResendOtpCommand;
use App\Commands\Auth\VerifyOtpCommand;
use App\Commands\Gateway\ImportServiceRoutesCommand;
use App\Commands\Gateway\ProxyRequestCommand;
use App\Commands\Gateway\RegisterServiceCommand;
use App\Contracts\CommandBus;
use App\Handlers\Auth\AssignRolesHandler;
use App\Handlers\Auth\IssueOtpHandler;
use App\Handlers\Auth\LogoutHandler;
use App\Handlers\Auth\RegisterUserHandler;
use App\Handlers\Auth\ResendOtpHandler;
use App\Handlers\Auth\VerifyOtpHandler;
use App\Handlers\Gateway\ImportServiceRoutesHandler;
use App\Handlers\Gateway\ProxyRequestHandler;
use App\Handlers\Gateway\RegisterServiceHandler;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class CommandBusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CommandBus::class, LaravelCommandBus::class);
    }

    public function boot(): void
    {
        $this->app->make(Dispatcher::class)->map([
            RegisterUserCommand::class => RegisterUserHandler::class,
            IssueOtpCommand::class => IssueOtpHandler::class,
            ResendOtpCommand::class => ResendOtpHandler::class,
            VerifyOtpCommand::class => VerifyOtpHandler::class,
            LogoutCommand::class => LogoutHandler::class,
            AssignRolesCommand::class => AssignRolesHandler::class,
            RegisterServiceCommand::class => RegisterServiceHandler::class,
            ImportServiceRoutesCommand::class => ImportServiceRoutesHandler::class,
            ProxyRequestCommand::class => ProxyRequestHandler::class,
        ]);
    }
}
