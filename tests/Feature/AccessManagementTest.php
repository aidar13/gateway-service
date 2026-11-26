<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class AccessManagementTest extends ApiTestCase
{
    private function roleId(string $name): int
    {
        return (int) Role::findByName($name, 'api')->getKey();
    }

    private function permissionId(string $name): int
    {
        return (int) Permission::findByName($name, 'api')->getKey();
    }

    public function test_role_lifecycle_with_permissions(): void
    {
        $this->asAdmin();
        $this->postJson('/api/admin/permissions', ['name' => 'reports.view'])->assertCreated()->assertJsonPath('data.is_system', false);

        $id = (int) $this->postJson('/api/admin/roles', ['name' => 'manager', 'permissions' => ['reports.view']])
            ->assertCreated()
            ->assertJsonPath('data.permissions', ['reports.view'])
            ->json('data.id');

        $this->putJson("/api/admin/roles/{$id}", ['name' => 'lead'])
            ->assertOk()
            ->assertJsonPath('data.name', 'lead')
            ->assertJsonPath('data.permissions', ['reports.view']);
        $this->putJson("/api/admin/roles/{$id}", ['permissions' => []])->assertOk()->assertJsonPath('data.permissions', []);
        $this->getJson('/api/admin/roles')->assertOk()->assertJsonCount(3, 'data');
        $this->deleteJson("/api/admin/roles/{$id}")->assertNoContent();
        $this->getJson("/api/admin/roles/{$id}")->assertNotFound();
    }

    public function test_permission_given_through_a_role_opens_the_admin_area(): void
    {
        $user = User::factory()->create();
        $this->asAdmin();
        $this->postJson('/api/admin/roles', ['name' => 'registrar', 'permissions' => ['services.manage']])->assertCreated();
        $this->putJson("/api/admin/users/{$user->id}/roles", ['roles' => ['registrar']])->assertOk();

        $this->freshAuth($this->accessTokenFor($user));
        $this->getJson('/api/admin/services')->assertOk();
        // …and only that area.
        $this->getJson('/api/admin/roles')->assertForbidden();
        $this->getJson('/api/admin/scopes')->assertForbidden();
        $this->getJson('/api/admin/clients')->assertForbidden();
    }

    public function test_system_roles_and_permissions_are_protected(): void
    {
        $this->asAdmin();
        $admin = $this->roleId('admin');

        $this->deleteJson("/api/admin/roles/{$admin}")->assertStatus(409);
        $this->putJson("/api/admin/roles/{$admin}", ['name' => 'root'])->assertStatus(409);
        $this->putJson("/api/admin/roles/{$admin}", ['permissions' => ['services.manage']])->assertStatus(409);
        $this->deleteJson('/api/admin/roles/'.$this->roleId('client'))->assertStatus(409);

        $permission = $this->permissionId('access.manage');
        $this->deleteJson("/api/admin/permissions/{$permission}")->assertStatus(409);
        $this->putJson("/api/admin/permissions/{$permission}", ['name' => 'other'])->assertStatus(409);
    }

    public function test_custom_permission_can_be_renamed_and_deleted(): void
    {
        $this->asAdmin();
        $id = (int) $this->postJson('/api/admin/permissions', ['name' => 'reports.view'])->json('data.id');

        $this->putJson("/api/admin/permissions/{$id}", ['name' => 'reports.read'])->assertOk()->assertJsonPath('data.name', 'reports.read');
        $this->deleteJson("/api/admin/permissions/{$id}")->assertNoContent();
        $this->getJson("/api/admin/permissions/{$id}")->assertNotFound();
    }

    public function test_names_are_unique_and_permissions_must_exist(): void
    {
        $this->asAdmin();

        $this->postJson('/api/admin/roles', ['name' => 'admin'])->assertUnprocessable();
        $this->postJson('/api/admin/permissions', ['name' => 'access.manage'])->assertUnprocessable();
        $this->postJson('/api/admin/roles', ['name' => 'x1', 'permissions' => ['nope']])->assertUnprocessable();
    }

    public function test_last_administrator_cannot_lose_the_role(): void
    {
        $admin = $this->admin();
        $this->freshAuth($this->accessTokenFor($admin));

        $this->putJson("/api/admin/users/{$admin->id}/roles", ['roles' => ['client']])->assertStatus(409);

        $this->admin();
        $this->putJson("/api/admin/users/{$admin->id}/roles", ['roles' => ['client']])->assertOk();
    }
}
