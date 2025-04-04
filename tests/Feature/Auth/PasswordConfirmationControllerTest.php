<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class PasswordConfirmationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_page_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.confirm'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.confirm-password');
    }

    public function test_user_can_confirm_password_with_correct_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post(route('password.confirm'), [
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('profile.dashboard'));
        $this->assertTrue(Session::has('auth.password_confirmed_at'));
        $response->assertSessionHasNoErrors();
    }

    public function test_user_cannot_confirm_password_with_incorrect_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post(route('password.confirm'), [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertFalse(Session::has('auth.password_confirmed_at'));
    }
}
