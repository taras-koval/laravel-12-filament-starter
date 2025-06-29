<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Resources\ManagerResource;
use App\Filament\Resources\ManagerResource\Pages\CreateManager;
use App\Filament\Resources\ManagerResource\Pages\EditManager;
use App\Filament\Resources\ManagerResource\Pages\ListManagers;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerResourceTest extends TestCase
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

    // TODO: Can fail randomly
    public function test_only_managers_and_admins_appear_in_manager_resource()
    {
        // Create additional users with different roles
        $anotherManager = User::factory()->create();
        $anotherManager->syncRoles([UserRoleEnum::MANAGER]);

        $anotherAdmin = User::factory()->create();
        $anotherAdmin->syncRoles([UserRoleEnum::ADMINISTRATOR]);

        $regularUser = User::factory()->create();
        $regularUser->syncRoles([UserRoleEnum::USER]);

        $this->actingAs($this->admin)
            ->get(ManagerResource::getUrl())
            ->assertSuccessful()
            ->assertSeeText($this->manager->name)
            ->assertSeeText($this->manager->email);

        // Get the filtered query from ManagerResource
        $query = ManagerResource::getEloquentQuery();
        $users = $query->get();

        // Should contain managers and admins (4 total)
        $this->assertCount(4, $users);
        $this->assertTrue($users->contains($this->manager));
        $this->assertTrue($users->contains($this->admin));
        $this->assertTrue($users->contains($anotherManager));
        $this->assertTrue($users->contains($anotherAdmin));
        $this->assertFalse($users->contains($regularUser));
        $this->assertFalse($users->contains($this->regularUser));

        // Test through UI
        Livewire::actingAs($this->admin)
            ->test(ListManagers::class)
            ->assertCanSeeTableRecords([$this->manager, $this->admin, $anotherManager, $anotherAdmin]);
    }

    public function test_administrator_can_create_manager()
    {
        $managerData = [
            'name' => 'John Manager',
            'email' => 'manager@example.com',
            'phone' => '+48123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'email_verified_at' => true,
        ];

        Livewire::actingAs($this->admin)
            ->test(CreateManager::class)
            ->fillForm($managerData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify manager was created
        $this->assertDatabaseHas('users', [
            'name' => 'John Manager',
            'email' => 'manager@example.com',
            'phone' => '+48123456789',
        ]);

        // Verify manager was assigned MANAGER role
        $createdManager = User::where('email', 'manager@example.com')->first();
        $this->assertTrue($createdManager->hasRole(UserRoleEnum::MANAGER));
    }

    public function test_administrator_can_edit_manager()
    {
        $manager = User::factory()->create();
        $manager->syncRoles([UserRoleEnum::MANAGER]);

        Livewire::actingAs($this->admin)
            ->test(EditManager::class, ['record' => $manager->id])
            ->fillForm([
                'name' => 'Updated Manager Name',
                'email' => $manager->email,
                'phone' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $manager->refresh();
        $this->assertEquals('Updated Manager Name', $manager->name);
    }

    // ========== PERMISSION TESTS ==========

    public function test_admin_can_access_manager_resource()
    {
        $manager = User::factory()->create();
        $manager->syncRoles([UserRoleEnum::MANAGER]);

        $this->actingAs($this->admin)->get(ManagerResource::getUrl())->assertSuccessful();

        Livewire::actingAs($this->admin)->test(ListManagers::class)->assertSuccessful();
        Livewire::actingAs($this->admin)->test(CreateManager::class)->assertSuccessful();
        Livewire::actingAs($this->admin)->test(EditManager::class, ['record' => $manager->id])->assertSuccessful();

        // Test delete action
        Livewire::actingAs($this->admin)
            ->test(EditManager::class, ['record' => $manager->id])
            ->callAction('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $manager->id]);
    }

    public function test_manager_cannot_access_manager_resource()
    {
        $anotherManager = User::factory()->create();
        $anotherManager->syncRoles([UserRoleEnum::MANAGER]);

        // Managers don't have permissions to manage other managers
        $this->actingAs($this->manager)->get(ManagerResource::getUrl())->assertForbidden();

        Livewire::actingAs($this->manager)->test(ListManagers::class)->assertForbidden();
        Livewire::actingAs($this->manager)->test(CreateManager::class)->assertForbidden();
        Livewire::actingAs($this->manager)->test(EditManager::class, ['record' => $anotherManager->id])->assertForbidden();
    }

    public function test_regular_user_cannot_access_manager_resource()
    {
        $manager = User::factory()->create();
        $manager->syncRoles([UserRoleEnum::MANAGER]);

        $this->actingAs($this->regularUser)->get(ManagerResource::getUrl())->assertForbidden();

        Livewire::actingAs($this->regularUser)->test(ListManagers::class)->assertForbidden();
        Livewire::actingAs($this->regularUser)->test(CreateManager::class)->assertForbidden();
        Livewire::actingAs($this->regularUser)->test(EditManager::class, ['record' => $manager->id])->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_manager_resource()
    {
        $manager = User::factory()->create();
        $manager->syncRoles([UserRoleEnum::MANAGER]);

        Livewire::test(ListManagers::class)->assertForbidden();
        Livewire::test(CreateManager::class)->assertForbidden();
        Livewire::test(EditManager::class, ['record' => $manager->id])->assertForbidden();
    }

    public function test_user_cannot_delete_themselves_in_manager_resource()
    {
        // Test that admin cannot delete themselves through manager resource
        Livewire::actingAs($this->admin)
            ->test(EditManager::class, ['record' => $this->admin->id])
            ->assertActionHidden('delete');
    }

    public function test_manager_creation_requires_valid_data()
    {
        // Test duplicate email
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        Livewire::actingAs($this->admin)
            ->test(CreateManager::class)
            ->fillForm([
                'name' => 'Test Manager',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);

        // Test password confirmation mismatch
        Livewire::actingAs($this->admin)
            ->test(CreateManager::class)
            ->fillForm([
                'name' => 'Test Manager',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different',
            ])
            ->call('create')
            ->assertHasFormErrors(['password' => 'confirmed']);
    }

    public function test_can_upload_manager_avatar()
    {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->image('avatar.jpg');

        Livewire::test(EditManager::class, ['record' => $this->manager->getKey()])
            ->fillForm([
                'name' => $this->manager->name,
                'email' => $this->manager->email,
                'avatar' => $file,
            ])
            ->call('save');

        $this->manager = User::find($this->manager->id); // Force refresh from DB
        Storage::disk('public')->assertExists($this->manager->avatar);
    }

    // Email verification tests

    public function test_creates_unverified_manager_and_sends_verification_email()
    {
        Event::fake();

        $this->actingAs($this->admin);

        Livewire::test(CreateManager::class)
            ->fillForm([
                'name' => 'Test Manager',
                'email' => 'testmanager@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'email_verified_at' => false, // Toggle is off
            ])
            ->call('create');

        // Check manager was created as unverified
        $manager = User::where('email', 'testmanager@example.com')->first();
        $this->assertNull($manager->email_verified_at);
        $this->assertTrue($manager->hasRole(UserRoleEnum::MANAGER));

        // Check that verification event was dispatched
        Event::assertDispatched(Registered::class, function ($event) use ($manager) {
            return $event->user->id === $manager->id;
        });
    }

    public function test_creates_verified_manager_without_sending_email()
    {
        Event::fake();

        $this->actingAs($this->admin);

        Livewire::test(CreateManager::class)
            ->fillForm([
                'name' => 'Test Manager',
                'email' => 'testmanager@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'email_verified_at' => true, // Toggle is on
            ])
            ->call('create');

        // Check manager was created as verified
        $manager = User::where('email', 'testmanager@example.com')->first();
        $this->assertNotNull($manager->email_verified_at);
        $this->assertTrue($manager->hasRole(UserRoleEnum::MANAGER));

        // Check that no verification event was dispatched
        Event::assertNotDispatched(Registered::class);
    }

    public function test_changing_manager_email_sends_verification_when_unverified()
    {
        Event::fake();

        $this->actingAs($this->admin);

        $manager = User::factory()->create([
            'email' => 'oldmanager@example.com',
            'phone' => '+48111222334', // Unique phone
            'email_verified_at' => null,
        ]);
        $manager->assignRole(UserRoleEnum::MANAGER);

        $newEmail = 'newmanager@example.com';

        Livewire::test(EditManager::class, ['record' => $manager->getKey()])
            ->fillForm([
                'name' => $manager->name,
                'email' => $newEmail,
                'phone' => $manager->phone,
                'email_verified_at' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Check database directly
        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'email' => $newEmail,
            'email_verified_at' => null,
        ]);

        Event::assertDispatched(Registered::class, function ($event) use ($manager) {
            return $event->user->id === $manager->id;
        });
    }

    public function test_changing_manager_verification_status_to_unverified_sends_email()
    {
        Event::fake();

        $this->actingAs($this->admin);

        $manager = User::factory()->create([
            'email' => 'testmanager@example.com',
            'phone' => '+48111222333', // Unique phone
            'email_verified_at' => now(), // Manager is verified
        ]);
        $manager->assignRole(UserRoleEnum::MANAGER);

        Livewire::test(EditManager::class, ['record' => $manager->getKey()])
            ->fillForm([
                'name' => $manager->name,
                'email' => $manager->email,
                'phone' => $manager->phone,
                'email_verified_at' => false, // Change to unverified
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Check database directly
        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'email_verified_at' => null,
        ]);

        Event::assertDispatched(Registered::class, function ($event) use ($manager) {
            return $event->user->id === $manager->id;
        });
    }

    public function test_changing_manager_email_when_verified_does_not_send_email()
    {
        Event::fake();

        $this->actingAs($this->admin);

        $manager = User::factory()->create([
            'email' => 'oldmanager@example.com',
            'phone' => '+48111222335', // Unique phone
            'email_verified_at' => now(), // Manager is verified
        ]);
        $manager->assignRole(UserRoleEnum::MANAGER);

        $newEmail = 'newmanager@example.com';

        Livewire::test(EditManager::class, ['record' => $manager->getKey()])
            ->fillForm([
                'name' => $manager->name,
                'email' => $newEmail,
                'phone' => $manager->phone,
                'email_verified_at' => true, // Keep verified
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Check database directly
        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'email' => $newEmail,
        ]);

        // Check that verification status is still present (not null)
        $this->assertDatabaseMissing('users', [
            'id' => $manager->id,
            'email_verified_at' => null,
        ]);

        Event::assertNotDispatched(Registered::class);
    }

    public function test_no_verification_email_sent_when_no_relevant_manager_changes()
    {
        Event::fake();

        $this->actingAs($this->admin);

        $manager = User::factory()->create([
            'email' => 'testmanager@example.com',
            'email_verified_at' => now(),
        ]);
        $manager->assignRole(UserRoleEnum::MANAGER);

        Livewire::test(EditManager::class, ['record' => $manager->getKey()])
            ->fillForm([
                'name' => 'Updated Manager Name', // Only name changed
                'email' => $manager->email,
                'email_verified_at' => true,
            ])
            ->call('save');

        // Check that no verification event was dispatched
        Event::assertNotDispatched(Registered::class);
    }
}
