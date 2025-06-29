<?php

namespace Tests\Feature\Filament;

use App\Enums\UserPermissionEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Filament\Resources\RoleResource\Pages\ListRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(UserRoleEnum::ADMINISTRATOR);

        $this->manager = User::factory()->create();
        $this->manager->assignRole(UserRoleEnum::MANAGER);

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole(UserRoleEnum::USER);
    }

    public function test_administrator_can_view_roles_list()
    {
        Livewire::actingAs($this->admin)
            ->test(ListRoles::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Role::all());
    }

    public function test_administrator_can_create_custom_role()
    {
        $permissions = Permission::whereIn('name', [
            UserPermissionEnum::VIEW_USERS->value,
            UserPermissionEnum::CREATE_USERS->value,
        ])->get();

        $roleData = [
            'name' => 'Support Team',
            'permissions' => $permissions->pluck('id')->toArray(),
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateRole::class)
            ->fillForm($roleData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify role was created with slug format
        $this->assertDatabaseHas('roles', [
            'name' => 'support-team',
        ]);

        // Verify permissions were assigned
        $createdRole = Role::where('name', 'support-team')->first();
        $this->assertTrue($createdRole->hasPermissionTo(UserPermissionEnum::VIEW_USERS));
        $this->assertTrue($createdRole->hasPermissionTo(UserPermissionEnum::CREATE_USERS));
        $this->assertFalse($createdRole->hasPermissionTo(UserPermissionEnum::DELETE_USERS));
    }

    public function test_administrator_can_edit_custom_role()
    {
        $customRole = Role::create(['name' => 'custom-role']);
        $customRole->givePermissionTo(UserPermissionEnum::VIEW_USERS);

        $newPermissions = Permission::whereIn('name', [
            UserPermissionEnum::VIEW_USERS->value,
            UserPermissionEnum::EDIT_USERS->value,
        ])->get();

        Livewire::actingAs($this->admin)
            ->test(EditRole::class, ['record' => $customRole->id])
            ->fillForm([
                'name' => 'Updated Role Name',
                'permissions' => $newPermissions->pluck('id')->toArray(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $customRole->refresh();
        $this->assertEquals('updated-role-name', $customRole->name);
        $this->assertTrue($customRole->hasPermissionTo(UserPermissionEnum::EDIT_USERS));
    }

    public function test_administrator_can_delete_custom_role()
    {
        $customRole = Role::create(['name' => 'deletable-role']);

        Livewire::actingAs($this->admin)
            ->test(EditRole::class, ['record' => $customRole->id])
            ->callAction('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }

    public function test_system_roles_cannot_be_edited()
    {
        $adminRole = Role::where('name', UserRoleEnum::ADMINISTRATOR->value)->first();

        // Test that system roles cannot be edited at all
        Livewire::actingAs($this->admin)
            ->test(EditRole::class, ['record' => $adminRole->id])
            ->assertForbidden();
    }

    public function test_system_roles_cannot_be_deleted()
    {
        $userRole = Role::where('name', UserRoleEnum::USER->value)->first();
        $managerRole = Role::where('name', UserRoleEnum::MANAGER->value)->first();
        $adminRole = Role::where('name', UserRoleEnum::ADMINISTRATOR->value)->first();

        // Test that system roles cannot be accessed for editing/deleting
        Livewire::actingAs($this->admin)->test(EditRole::class, ['record' => $userRole->id])->assertForbidden();
        Livewire::actingAs($this->admin)->test(EditRole::class, ['record' => $managerRole->id])->assertForbidden();
        Livewire::actingAs($this->admin)->test(EditRole::class, ['record' => $adminRole->id])->assertForbidden();
    }

    public function test_role_creation_requires_valid_data()
    {
        // Test empty name
        Livewire::actingAs($this->admin)
            ->test(CreateRole::class)
            ->fillForm(['name' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);

        // Test duplicate name
        $existingRole = Role::create(['name' => 'existing-role']);

        Livewire::actingAs($this->admin)
            ->test(CreateRole::class)
            ->fillForm(['name' => 'existing-role'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }

    public function test_role_names_are_converted_to_slug_format()
    {
        Livewire::actingAs($this->admin)
            ->test(CreateRole::class)
            ->fillForm(['name' => 'Content Manager Team'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'content-manager-team',
        ]);
    }

    public function test_permissions_are_displayed_with_labels()
    {
        $customRole = Role::create(['name' => 'test-role']);
        $customRole->givePermissionTo([
            UserPermissionEnum::VIEW_USERS->value,
            UserPermissionEnum::CREATE_USERS->value,
        ]);

        // Test that permissions are shown in the table with proper labels
        Livewire::actingAs($this->admin)
            ->test(ListRoles::class)
            ->assertSuccessful()
            ->assertSee('View Users')
            ->assertSee('Create Users');
    }

    // ========== PERMISSION TESTS ==========

    public function test_admin_can_access_role_resource()
    {
        $customRole = Role::create(['name' => 'test-role']);

        $this->actingAs($this->admin)->get(RoleResource::getUrl())->assertSuccessful();

        Livewire::actingAs($this->admin)->test(ListRoles::class)->assertSuccessful();
        Livewire::actingAs($this->admin)->test(CreateRole::class)->assertSuccessful();
        Livewire::actingAs($this->admin)->test(EditRole::class, ['record' => $customRole->id])->assertSuccessful();
    }

    public function test_manager_cannot_access_role_resource()
    {
        $customRole = Role::create(['name' => 'test-role']);

        // Managers don't have role management permissions
        $this->actingAs($this->manager)->get(RoleResource::getUrl())->assertForbidden();

        Livewire::actingAs($this->manager)->test(ListRoles::class)->assertForbidden();
        Livewire::actingAs($this->manager)->test(CreateRole::class)->assertForbidden();
        Livewire::actingAs($this->manager)->test(EditRole::class, ['record' => $customRole->id])->assertForbidden();
    }

    public function test_regular_user_cannot_access_role_resource()
    {
        $customRole = Role::create(['name' => 'test-role']);

        $this->actingAs($this->regularUser)->get(RoleResource::getUrl())->assertForbidden();

        Livewire::actingAs($this->regularUser)->test(ListRoles::class)->assertForbidden();
        Livewire::actingAs($this->regularUser)->test(CreateRole::class)->assertForbidden();
        Livewire::actingAs($this->regularUser)->test(EditRole::class, ['record' => $customRole->id])->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_role_resource()
    {
        $customRole = Role::create(['name' => 'test-role']);

        Livewire::test(ListRoles::class)->assertForbidden();
        Livewire::test(CreateRole::class)->assertForbidden();
        Livewire::test(EditRole::class, ['record' => $customRole->id])->assertForbidden();
    }

    public function test_all_permissions_are_available_in_form()
    {
        $allPermissions = Permission::all();

        $response = Livewire::actingAs($this->admin)
            ->test(CreateRole::class)
            ->assertSuccessful();

        // Verify all permission labels are present in the form
        foreach (UserPermissionEnum::cases() as $permission) {
            $response->assertSee($permission->getLabel());
        }
    }
}
