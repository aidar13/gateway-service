<?php

declare(strict_types=1);

namespace App\Enums;

/** Permissions guarding the admin API: one per action, so a role gets exactly what it needs. */
enum SystemPermission: string
{
    case ServicesView = 'services.view';
    case ServicesCreate = 'services.create';
    case ServicesUpdate = 'services.update';
    case ServicesDelete = 'services.delete';
    case ServicesImportRoutes = 'services.import-routes';

    case RoutesView = 'routes.view';

    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';

    case PermissionsView = 'permissions.view';
    case PermissionsCreate = 'permissions.create';
    case PermissionsUpdate = 'permissions.update';
    case PermissionsDelete = 'permissions.delete';

    case UsersAssignRoles = 'users.assign-roles';

    case ScopesView = 'scopes.view';
    case ScopesCreate = 'scopes.create';
    case ScopesUpdate = 'scopes.update';
    case ScopesDelete = 'scopes.delete';
    case ScopesBind = 'scopes.bind';
    case ScopesUnbind = 'scopes.unbind';

    case ClientsView = 'clients.view';
    case ClientsCreate = 'clients.create';
    case ClientsAssignScopes = 'clients.assign-scopes';
    case ClientsRevoke = 'clients.revoke';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
