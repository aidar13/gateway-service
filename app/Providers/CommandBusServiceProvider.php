<?php

declare(strict_types=1);

namespace App\Providers;

use App\Bus\LaravelCommandBus;
use App\Commands\Access\AssignRolesCommand;
use App\Commands\Access\CreatePermissionCommand;
use App\Commands\Access\CreateRoleCommand;
use App\Commands\Access\DeletePermissionCommand;
use App\Commands\Access\DeleteRoleCommand;
use App\Commands\Access\RenamePermissionCommand;
use App\Commands\Access\UpdateRoleCommand;
use App\Commands\Auth\IssueOtpCommand;
use App\Commands\Auth\LogoutCommand;
use App\Commands\Auth\RegisterUserCommand;
use App\Commands\Auth\ResendOtpCommand;
use App\Commands\Auth\VerifyOtpCommand;
use App\Commands\Client\CreateClientCommand;
use App\Commands\Client\RevokeClientCommand;
use App\Commands\Client\SyncClientScopesCommand;
use App\Commands\Gateway\DeleteServiceCommand;
use App\Commands\Gateway\ImportServiceRoutesCommand;
use App\Commands\Gateway\ProxyRequestCommand;
use App\Commands\Gateway\RegisterServiceCommand;
use App\Commands\Gateway\UpdateServiceCommand;
use App\Commands\Scope\BindRouteCommand;
use App\Commands\Scope\BindServiceCommand;
use App\Commands\Scope\CreateScopeCommand;
use App\Commands\Scope\DeleteScopeCommand;
use App\Commands\Scope\UnbindRouteCommand;
use App\Commands\Scope\UnbindServiceCommand;
use App\Commands\Scope\UpdateScopeCommand;
use App\Contracts\CommandBus;
use App\Handlers\Access\AssignRolesHandler;
use App\Handlers\Access\CreatePermissionHandler;
use App\Handlers\Access\CreateRoleHandler;
use App\Handlers\Access\DeletePermissionHandler;
use App\Handlers\Access\DeleteRoleHandler;
use App\Handlers\Access\RenamePermissionHandler;
use App\Handlers\Access\UpdateRoleHandler;
use App\Handlers\Auth\IssueOtpHandler;
use App\Handlers\Auth\LogoutHandler;
use App\Handlers\Auth\RegisterUserHandler;
use App\Handlers\Auth\ResendOtpHandler;
use App\Handlers\Auth\VerifyOtpHandler;
use App\Handlers\Client\CreateClientHandler;
use App\Handlers\Client\RevokeClientHandler;
use App\Handlers\Client\SyncClientScopesHandler;
use App\Handlers\Gateway\DeleteServiceHandler;
use App\Handlers\Gateway\ImportServiceRoutesHandler;
use App\Handlers\Gateway\ProxyRequestHandler;
use App\Handlers\Gateway\RegisterServiceHandler;
use App\Handlers\Gateway\UpdateServiceHandler;
use App\Handlers\Scope\BindRouteHandler;
use App\Handlers\Scope\BindServiceHandler;
use App\Handlers\Scope\CreateScopeHandler;
use App\Handlers\Scope\DeleteScopeHandler;
use App\Handlers\Scope\UnbindRouteHandler;
use App\Handlers\Scope\UnbindServiceHandler;
use App\Handlers\Scope\UpdateScopeHandler;
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
            RegisterServiceCommand::class => RegisterServiceHandler::class,
            ImportServiceRoutesCommand::class => ImportServiceRoutesHandler::class,
            ProxyRequestCommand::class => ProxyRequestHandler::class,
            CreateRoleCommand::class => CreateRoleHandler::class,
            UpdateRoleCommand::class => UpdateRoleHandler::class,
            DeleteRoleCommand::class => DeleteRoleHandler::class,
            CreatePermissionCommand::class => CreatePermissionHandler::class,
            RenamePermissionCommand::class => RenamePermissionHandler::class,
            DeletePermissionCommand::class => DeletePermissionHandler::class,
            AssignRolesCommand::class => AssignRolesHandler::class,
            UpdateServiceCommand::class => UpdateServiceHandler::class,
            DeleteServiceCommand::class => DeleteServiceHandler::class,
            CreateScopeCommand::class => CreateScopeHandler::class,
            UpdateScopeCommand::class => UpdateScopeHandler::class,
            DeleteScopeCommand::class => DeleteScopeHandler::class,
            BindServiceCommand::class => BindServiceHandler::class,
            UnbindServiceCommand::class => UnbindServiceHandler::class,
            BindRouteCommand::class => BindRouteHandler::class,
            UnbindRouteCommand::class => UnbindRouteHandler::class,
            CreateClientCommand::class => CreateClientHandler::class,
            SyncClientScopesCommand::class => SyncClientScopesHandler::class,
            RevokeClientCommand::class => RevokeClientHandler::class,
        ]);
    }
}
