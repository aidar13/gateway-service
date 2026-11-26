<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SystemPermission::values() as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        Role::findOrCreate(SystemRole::Admin->value, 'api')->givePermissionTo(SystemPermission::values());
        Role::findOrCreate(SystemRole::Client->value, 'api');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
