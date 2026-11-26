<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SystemPermission as P;
use App\Models\User;
use Illuminate\Routing\Route as RegisteredRoute;
use Illuminate\Support\Facades\Route;

final class AdminPermissionsTest extends ApiTestCase
{
    /** @return list<array{string, string, P}> method, route URI, the only permission that opens it */
    private static function endpoints(): array
    {
        return [
            ['GET', 'services', P::ServicesView],
            ['GET', 'services/{id}', P::ServicesView],
            ['POST', 'services', P::ServicesCreate],
            ['PATCH', 'services/{id}', P::ServicesUpdate],
            ['DELETE', 'services/{id}', P::ServicesDelete],
            ['POST', 'services/{id}/import-routes', P::ServicesImportRoutes],
            ['GET', 'routes', P::RoutesView],
            ['GET', 'roles', P::RolesView],
            ['GET', 'roles/{id}', P::RolesView],
            ['POST', 'roles', P::RolesCreate],
            ['PUT', 'roles/{id}', P::RolesUpdate],
            ['DELETE', 'roles/{id}', P::RolesDelete],
            ['GET', 'permissions', P::PermissionsView],
            ['GET', 'permissions/{id}', P::PermissionsView],
            ['POST', 'permissions', P::PermissionsCreate],
            ['PUT', 'permissions/{id}', P::PermissionsUpdate],
            ['DELETE', 'permissions/{id}', P::PermissionsDelete],
            ['PUT', 'users/{id}/roles', P::UsersAssignRoles],
            ['GET', 'scopes', P::ScopesView],
            ['GET', 'scopes/{id}', P::ScopesView],
            ['POST', 'scopes', P::ScopesCreate],
            ['PATCH', 'scopes/{id}', P::ScopesUpdate],
            ['DELETE', 'scopes/{id}', P::ScopesDelete],
            ['PUT', 'scopes/{id}/services/{serviceId}', P::ScopesBind],
            ['PUT', 'scopes/{id}/routes/{routeId}', P::ScopesBind],
            ['DELETE', 'scopes/{id}/services/{serviceId}', P::ScopesUnbind],
            ['DELETE', 'scopes/{id}/routes/{routeId}', P::ScopesUnbind],
            ['GET', 'clients', P::ClientsView],
            ['GET', 'clients/{id}', P::ClientsView],
            ['POST', 'clients', P::ClientsCreate],
            ['PUT', 'clients/{id}/scopes', P::ClientsAssignScopes],
            ['DELETE', 'clients/{id}', P::ClientsRevoke],
        ];
    }

    public function test_every_endpoint_opens_with_its_own_permission_only(): void
    {
        $user  = User::factory()->create();
        $token = $this->accessTokenFor($user);

        foreach (self::endpoints() as [$method, $uri, $permission]) {
            $url    = '/api/admin/' . preg_replace('/\{\w+\}/', '999999', $uri);
            $others = array_values(array_diff(P::values(), [$permission->value]));

            $user->syncPermissions($others);
            $this->freshAuth($token)->json($method, $url)->assertForbidden();

            $user->syncPermissions([$permission->value]);
            $status = $this->freshAuth($token)->json($method, $url)->getStatusCode();
            $this->assertNotContains($status, [401, 403], "{$method} {$uri} with {$permission->value}");
        }
    }

    /** A new admin route cannot be added without a permission and a line in the table above. */
    public function test_the_table_covers_every_admin_route_and_permission(): void
    {
        $admin = array_filter(
            Route::getRoutes()->getRoutes(),
            static fn(RegisteredRoute $route): bool => str_starts_with($route->uri(), 'api/admin/'),
        );
        $listed = array_map(static fn(array $row): string => "{$row[0]} api/admin/{$row[1]}", self::endpoints());

        foreach ($admin as $route) {
            $guards = array_filter(
                $route->gatherMiddleware(),
                static fn(mixed $name): bool => is_string($name) && str_starts_with($name, 'permission:'),
            );
            $this->assertCount(1, $guards, $route->uri());
            $this->assertNotEmpty(array_filter(
                $route->methods(),
                static fn(string $method): bool => in_array("{$method} {$route->uri()}", $listed, true),
            ), implode('|', $route->methods()) . ' ' . $route->uri());
        }

        $used = array_unique(array_map(static fn(array $row): string => $row[2]->value, self::endpoints()));
        $this->assertEqualsCanonicalizing(P::values(), $used);
    }

    public function test_creating_a_client_with_scopes_needs_the_right_to_assign_them(): void
    {
        $scope = $this->grantScope('web');
        $user  = User::factory()->create();
        $user->givePermissionTo(P::ClientsCreate->value);
        $token   = $this->accessTokenFor($user);
        $payload = ['name' => 'mobile', 'scope_ids' => [$scope->id]];

        $this->freshAuth($token)->postJson('/api/admin/clients', $payload)->assertForbidden();
        $this->freshAuth($token)->postJson('/api/admin/clients', ['name' => 'bare'])->assertCreated();

        $user->givePermissionTo(P::ClientsAssignScopes->value);
        $this->freshAuth($token)->postJson('/api/admin/clients', $payload)->assertCreated();
    }
}
