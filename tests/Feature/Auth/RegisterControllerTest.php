<?php

namespace Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);

        $response->assertRedirect(route('profile.dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }
}
