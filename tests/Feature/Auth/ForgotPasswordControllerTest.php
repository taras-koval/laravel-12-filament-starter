<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_can_be_rendered()
    {
        $response = $this->get(route('forgot-password'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.forgot-password');
    }

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson(route('forgot-password'), [
            'email' => $user->email,
        ]);

        $response->assertOk();
        $response->assertJson([
            'message' => trans(Password::RESET_LINK_SENT),
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_is_sent_for_valid_email(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('forgot-password'), [
            'email' => $user->email,
        ]);

        $response->assertOk();
        $response->assertJson([
            'message' => trans('passwords.sent'),
        ]);
    }

    public function test_error_is_returned_for_invalid_email(): void
    {
        $response = $this->postJson(route('forgot-password'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_page_can_be_rendered()
    {
        $response = $this->get(route('password.reset', ['token' => 'random_token']));

        $response->assertStatus(200);
        $response->assertViewIs('auth.reset-password');
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        Event::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson(route('password.store'), [
            'email' => $user->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
            'token' => $token,
        ]);

        $response->assertOk();
        $response->assertJson(['redirect' => route('profile.dashboard')]);

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));

        Event::assertDispatched(PasswordReset::class);
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson(route('password.store'), [
            'email' => $user->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);

        $this->assertFalse(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_reset_password_fails_if_password_confirmation_does_not_match(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson(route('password.store'), [
            'email' => $user->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'WrongPassword',
            'token' => $token,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);

        $this->assertFalse(Hash::check('NewPassword123', $user->fresh()->password));
    }
}
