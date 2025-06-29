<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests @see UserResource
 */
class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Event::fake([Registered::class]);

        $this->admin = User::factory()->create();
        $this->admin->syncRoles([UserRoleEnum::ADMINISTRATOR]);

        $this->manager = User::factory()->create();
        $this->manager->syncRoles([UserRoleEnum::MANAGER]);

        $this->regularUser = User::factory()->create();
        $this->regularUser->syncRoles([UserRoleEnum::USER]);
    }

    public function test_administrator_can_view_users_list()
    {
        // Create some additional regular users to display
        User::factory()->count(3)->create();

        // Test that admin can access the users list
        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(User::whereHas('roles', function ($query) {
                $query->where('name', UserRoleEnum::USER);
            })->get());
    }

    public function test_only_users_with_user_role_appear_in_user_resource()
    {
        // Create users with specific roles
        $regularUser = User::factory()->create();
        $regularUser->syncRoles([UserRoleEnum::USER]);

        $anotherManager = User::factory()->create();
        $anotherManager->syncRoles([UserRoleEnum::MANAGER]);

        $anotherAdmin = User::factory()->create();
        $anotherAdmin->syncRoles([UserRoleEnum::ADMINISTRATOR]);

        // Get the filtered query from UserResource to verify logic
        $query = UserResource::getEloquentQuery();
        $users = $query->get();

        // Should only contain users with USER role (2 regular users)
        $this->assertCount(2, $users);
        $this->assertTrue($users->contains($regularUser));
        $this->assertTrue($users->contains($this->regularUser));

        // Test through UI - should see regular users in the table
        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([$regularUser, $this->regularUser]);
    }

    public function test_administrator_can_create_user_with_email_verification()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+48123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'email_verified_at' => false, // User should receive verification email
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify user was created with correct data
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+48123456789',
            'email_verified_at' => null, // Should be null since verification is false
        ]);

        // Verify user was assigned USER role automatically
        $createdUser = User::where('email', 'john@example.com')->first();
        $this->assertTrue($createdUser->hasRole(UserRoleEnum::USER));

        // Verify verification email event was dispatched
        Event::assertDispatched(Registered::class, function ($event) use ($createdUser) {
            return $event->user->id === $createdUser->id;
        });
    }

    public function test_administrator_can_create_verified_user_without_sending_email()
    {
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'email_verified_at' => true, // User should be immediately verified
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify user was created and is verified
        $createdUser = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($createdUser->email_verified_at);

        // Verify no verification email was sent
        Event::assertNotDispatched(Registered::class);
    }

    public function test_administrator_can_edit_user_and_trigger_email_verification()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(), // User starts verified
        ]);

        // Edit user and change email, mark as unverified
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => 'new@example.com',
                'phone' => null,
                'email_verified_at' => false, // Unverify the user
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Refresh user from database
        $user->refresh();

        // Verify changes were saved
        $this->assertEquals('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);

        // Verify verification email was sent due to email change and unverified status
        Event::assertDispatched(Registered::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function test_changing_email_to_unverified_sends_verification_email()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        // Change only verification status (not email)
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email, // Same email
                'phone' => null,
                'email_verified_at' => false, // But unverify
            ])
            ->call('save');

        // Should send verification email because verification status changed to unverified
        Event::assertDispatched(Registered::class);
    }

    public function test_changing_verified_email_does_not_send_verification_email()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        // Change email but keep verified
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => 'new@example.com', // New email
                'email_verified_at' => true, // But keep verified
            ])
            ->call('save');

        // Should NOT send verification email because user remains verified
        Event::assertNotDispatched(Registered::class);
    }

    public function test_can_upload_and_delete_user_avatar()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        // Upload avatar
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => null,
                'avatar' => $file,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        // Verify file was stored
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);

        // Test file deletion
        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => null,
                'avatar' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        // Verify avatar was removed from user record
        $this->assertNull($user->avatar);
    }

    public function test_user_creation_requires_valid_data()
    {
        // Test empty name
        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => '',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);

        // Test invalid email
        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'John Doe',
                'email' => 'invalid-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'email']);

        // Test duplicate email
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'John Doe',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);

        // Test password confirmation mismatch
        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'John Doe',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different',
            ])
            ->call('create')
            ->assertHasFormErrors(['password' => 'confirmed']);
    }

    // ========== PERMISSION TESTS ==========

    public function test_admin_can_access_user_resource()
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->get(UserResource::getUrl())->assertSuccessful();
        $this->assertTrue($this->admin->canAccessPanel(filament()->getPanel('admin')));

        Livewire::actingAs($this->admin)->test(ListUsers::class)->assertSuccessful();
        Livewire::actingAs($this->admin)->test(CreateUser::class)->assertSuccessful();
        Livewire::actingAs($this->admin)->test(EditUser::class, ['record' => $user->id])
            ->assertSuccessful();
        Livewire::actingAs($this->admin)->test(EditUser::class, ['record' => $user->id])->callAction('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_manager_can_access_user_resource()
    {
        $user = User::factory()->create();

        $this->actingAs($this->manager)->get(UserResource::getUrl())->assertSuccessful();
        $this->assertTrue($this->manager->canAccessPanel(filament()->getPanel('admin')));

        Livewire::actingAs($this->manager)->test(ListUsers::class)->assertSuccessful();
        Livewire::actingAs($this->manager)->test(CreateUser::class)->assertSuccessful();
        Livewire::actingAs($this->manager)->test(EditUser::class, ['record' => $user->id])->assertSuccessful();
        // Test that manager cannot delete users (should not see delete action)
        Livewire::actingAs($this->manager)->test(EditUser::class, ['record' => $user->id])->assertActionHidden('delete');
    }

    public function test_regular_user_cannot_access_user_resource()
    {
        $user = User::factory()->create();

        $this->actingAs($this->regularUser)->get(UserResource::getUrl())->assertForbidden();
        $this->assertFalse($this->regularUser->canAccessPanel(filament()->getPanel('admin')));

        Livewire::actingAs($this->regularUser)->test(ListUsers::class)->assertForbidden();
        Livewire::actingAs($this->regularUser)->test(CreateUser::class)->assertForbidden();
        Livewire::actingAs($this->regularUser)->test(EditUser::class, ['record' => $user->id])->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_any_pages()
    {
        $user = User::factory()->create();

        // Test all pages without authentication
        Livewire::test(ListUsers::class)->assertForbidden();
        Livewire::test(CreateUser::class)->assertForbidden();
        Livewire::test(EditUser::class, ['record' => $user->id])->assertForbidden();
    }

    public function test_auto_cleanup_removes_missing_avatar_files()
    {
        $user = User::factory()->create([
            'avatar' => 'avatars/missing-file.jpg',
        ]);

        // Simulate model retrieval which should trigger the cleanup
        $retrievedUser = User::find($user->id);

        // The avatar should be cleaned up automatically since file doesn't exist
        $this->assertNull($retrievedUser->avatar);
    }
}
