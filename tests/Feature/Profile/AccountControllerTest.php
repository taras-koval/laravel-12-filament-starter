<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_page_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.account.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('profile.account');
        $response->assertViewHas('user', $user);
    }

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user)->patchJson(route('profile.account.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'profile-updated',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_user_can_update_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)->putJson(route('profile.account.update-password'), [
            'current_password' => 'old-password',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'password-updated',
        ]);

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)->putJson(route('profile.account.update-password'), [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_password']);

        $this->assertFalse(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_oauth_user_can_set_password_without_current_password_ajax()
    {
        $user = User::factory()->create([
            'password' => null,
        ]);

        $response = $this->actingAs($user)->putJson(route('profile.account.update-password'), [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'password-updated',
        ]);

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_user_with_existing_password_cannot_update_without_current_password_ajax()
    {
        $user = User::factory()->create([
            'password' => Hash::make('existing-password'),
        ]);

        $response = $this->actingAs($user)->putJson(route('profile.account.update-password'), [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('existing-password', $user->fresh()->password));
    }

    public function test_user_must_confirm_password_before_deleting_account()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('profile.account.destroy'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_user_can_delete_account_after_password_confirmation()
    {
        $user = User::factory()->create();

        session(['auth.password_confirmed_at' => time()]);

        $response = $this->actingAs($user)->delete(route('profile.account.destroy'));

        $response->assertRedirect(route('login'));

        $this->assertNull(User::find($user->id));
    }
}
