<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SystemPermission as P;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Roles that show how the one-permission-per-action model splits administration. */
final class DemoRoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::roles() as $name => $permissions) {
            $role = Role::findOrCreate($name, 'api');

            // Only for a new role: what an administrator changed later stays changed.
            if ($role->wasRecentlyCreated) {
                $role->givePermissionTo(array_map(static fn(P $permission): string => $permission->value, $permissions));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return array<string, list<P>> */
    private static function roles(): array
    {
        return [
            'service-manager' => [
                P::ServicesView, P::ServicesCreate, P::ServicesUpdate, P::ServicesDelete, P::ServicesImportRoutes,
                P::RoutesView, P::ScopesView, P::ScopesBind, P::ScopesUnbind,
            ],
            'access-manager' => [
                P::RolesView, P::RolesCreate, P::RolesUpdate, P::RolesDelete,
                P::PermissionsView, P::PermissionsCreate, P::PermissionsUpdate, P::PermissionsDelete,
                P::UsersAssignRoles,
            ],
            'auditor' => [
                P::ServicesView, P::RoutesView, P::RolesView, P::PermissionsView, P::ScopesView, P::ClientsView,
            ],
        ];
    }
}
