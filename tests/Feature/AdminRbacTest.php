<?php

namespace Tests\Feature;

use App\Models\AdminAuditTrail;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionsSeeder::class);
    }

    private function makeAdmin(array $attrs = [], bool $super = false): User
    {
        $user = User::factory()->create($attrs);
        $user->assignRole('admin');
        if ($super) {
            $user->forceFill(['is_super_admin' => true])->save();
        }

        return $user;
    }

    private function makeEditor(array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->assignRole('editor');

        return $user;
    }

    public function test_editor_can_access_products_and_media(): void
    {
        $editor = $this->makeEditor();

        $this->actingAs($editor)->get(route('dashboard.vehicles.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.media.index'))->assertOk();
    }

    public function test_editor_cannot_access_restricted_admin_pages(): void
    {
        $editor = $this->makeEditor();

        $this->actingAs($editor)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.staff.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.pages.index'))->assertForbidden();
    }

    public function test_editor_can_view_customers_but_not_delete(): void
    {
        $editor = $this->makeEditor();
        $customer = User::factory()->create();
        $customer->assignRole('user');

        $this->actingAs($editor)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($editor)->delete(route('admin.users.destroy', $customer))->assertForbidden();
    }

    public function test_admin_can_delete_another_admin_but_not_super_admin(): void
    {
        $super = $this->makeAdmin(['email' => 'super@example.com'], true);
        $admin = $this->makeAdmin(['email' => 'admin2@example.com']);
        $other = $this->makeAdmin(['email' => 'admin3@example.com']);

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $other))->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $other->id]);

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $super))->assertForbidden();
    }

    public function test_login_redirects_differ_by_role(): void
    {
        $admin = $this->makeAdmin();
        $editor = $this->makeEditor();

        $this->assertSame(route('admin.dashboard', absolute: false), $admin->staffHomeRoute());
        $this->assertSame(route('dashboard.vehicles.index', absolute: false), $editor->staffHomeRoute());
        $this->assertSame(route('dashboard.vehicles.index', absolute: false), $editor->loginRedirectPath());
    }

    public function test_editor_visiting_admin_root_redirects_to_products(): void
    {
        $editor = $this->makeEditor();

        $this->actingAs($editor)
            ->get('/admin')
            ->assertRedirect(route('dashboard.vehicles.index'));
    }

    public function test_admin_can_set_staff_password_on_create_and_edit(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Editor One',
            'email' => 'editor1@example.com',
            'password' => 'Str0ng!Pass99',
            'password_confirmation' => 'Str0ng!Pass99',
            'role' => 'editor',
        ])->assertRedirect();

        $editor = User::query()->where('email', 'editor1@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Str0ng!Pass99', $editor->password));

        $this->actingAs($admin)->put(route('admin.staff.update', $editor), [
            'name' => 'Editor One',
            'email' => 'editor1@example.com',
            'role' => 'editor',
            'password' => 'NewStr0ng!Pass88',
            'password_confirmation' => 'NewStr0ng!Pass88',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('NewStr0ng!Pass88', $editor->fresh()->password));
    }

    public function test_staff_create_writes_structured_audit_log_without_password(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Audit Editor',
            'email' => 'auditeditor@example.com',
            'password' => 'Str0ng!Pass99',
            'password_confirmation' => 'Str0ng!Pass99',
            'role' => 'editor',
        ])->assertRedirect();

        $entry = AdminAuditTrail::query()
            ->where('meta->action', 'staff.created')
            ->latest('id')
            ->first();
        $this->assertNotNull($entry);
        $this->assertSame('staff.created', $entry->meta['action'] ?? null);
        $this->assertArrayNotHasKey('password', $entry->meta ?? []);

        $genericDuplicates = AdminAuditTrail::query()
            ->where('path', '/admin/staff')
            ->where('method', 'POST')
            ->whereNull('meta->action')
            ->count();
        $this->assertSame(0, $genericDuplicates);
    }

    public function test_admin_without_synced_permissions_still_sees_the_admin_menu(): void
    {
        $admin = $this->makeAdmin();
        Role::findByName('admin')->syncPermissions([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->actingAs($admin->fresh())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(__('Products'));
        $response->assertSee(__('Orders'));
        $response->assertSee(__('Settings'));
    }

    public function test_admin_dashboard_url_opens_the_admin_overview(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_super_admin_can_be_updated_without_submitting_role_field(): void
    {
        $super = $this->makeAdmin(['name' => 'Super User', 'email' => 'super@example.com'], true);

        $this->actingAs($super)->put(route('admin.staff.update', $super), [
            'name' => 'Super User Updated',
            'email' => 'super@example.com',
        ])->assertRedirect();

        $this->assertSame('Super User Updated', $super->fresh()->name);
        $this->assertTrue($super->fresh()->isSuperAdmin());
        $this->assertTrue($super->fresh()->hasRole('admin'));
    }

    public function test_cannot_demote_last_admin_when_no_super_admin_exists(): void
    {
        $admin = $this->makeAdmin(['email' => 'only-admin@example.com']);

        $this->actingAs($admin)->put(route('admin.staff.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'editor',
        ])->assertStatus(400);
    }
}
