<?php

namespace Auth\OAuth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class ProviderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_google_oauth()
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getEmail')->andReturn('user@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Google User');
        $googleUser->shouldReceive('getId')->andReturn('google-id-123');
        $googleUser->token = 'fake-google-token';

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('profile.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'provider_id' => 'google-id-123',
            'provider_type' => 'google',
            'provider_token' => 'fake-google-token',
        ]);
    }

    public function test_social_login_fails_without_email()
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getEmail')->andReturn(null);
        $googleUser->shouldReceive('getName')->andReturn('No Email');
        $googleUser->shouldReceive('getId')->andReturn('google-id-999');
        $googleUser->token = 'fake-google-token';

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_existing_email_user_can_login_via_oauth()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('secret123'),
            'provider_id' => null,
            'provider_type' => null,
            'provider_token' => null,
        ]);

        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getEmail')->andReturn('user@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Google User');
        $googleUser->shouldReceive('getId')->andReturn('google-id-999');
        $googleUser->token = 'oauth-token-abc';

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('profile.dashboard'));

        $this->assertAuthenticatedAs($user);

        $user->refresh();

        $this->assertEquals('google-id-999', $user->provider_id);
        $this->assertEquals('google', $user->provider_type);
        $this->assertEquals('oauth-token-abc', $user->provider_token);
    }

    public function test_oauth_login_with_different_email_creates_new_user()
    {
        User::factory()->create([
            'email' => 'user1@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getEmail')->andReturn('user2@example.com');
        $googleUser->shouldReceive('getName')->andReturn('New Google User');
        $googleUser->shouldReceive('getId')->andReturn('google-id-456');
        $googleUser->token = 'new-oauth-token';

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('profile.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'user2@example.com',
            'provider_id' => 'google-id-456',
            'provider_type' => 'google',
        ]);

        $this->assertEquals(2, User::count());

        $this->assertEquals('user2@example.com', auth()->user()->email);
    }
}
