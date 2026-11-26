<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $manage = Permission::findOrCreate('gateway.manage', 'api');

        Role::findOrCreate('admin', 'api')->givePermissionTo($manage);
        Role::findOrCreate('client', 'api');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
