<?php

namespace Tests\Feature\Auth\OAuth;

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

    protected function mockSocialiteUser(string $provider, array $overrides = []): void
    {
        // Preset data for different OAuth providers
        $presets = [
            'google' => [
                'getEmail' => 'user@example.com',
                'getName' => 'Google User',
                'getId' => 'google-id-123',
                'getAvatar' => 'https://example.com/google-avatar.jpg',
                'token' => 'fake-google-token',
            ],
            'github' => [
                'getEmail' => 'github@example.com',
                'getName' => 'GitHub User',
                'getId' => 'github-id-456',
                'getAvatar' => 'https://example.com/github-avatar.jpg',
                'token' => 'fake-github-token',
            ],
        ];

        // Merge default values with overrides
        $userData = array_merge($presets[$provider] ?? [], $overrides);

        $mockUser = Mockery::mock(SocialiteUser::class);

        // Configure mock methods based on user data
        foreach ($userData as $method => $value) {
            // If it's a method - use shouldReceive
            if (method_exists($mockUser, $method)) {
                $mockUser->shouldReceive($method)->andReturn($value);
            }
            // Otherwise set it as a property
            else {
                $mockUser->$method = $value;
            }
        }

        // Replace Socialite::driver()->user() with mock object
        Socialite::shouldReceive('driver->user')->andReturn($mockUser);
    }

    public function test_user_can_login_with_google_oauth()
    {
        $this->mockSocialiteUser('google');

        $response = $this->get(route('auth.callback', ['driver' => 'google']));

        $response->assertRedirect(route('profile.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'provider_id' => 'google-id-123',
            'provider_type' => 'google',
            'provider_token' => 'fake-google-token',
            'avatar' => 'https://example.com/google-avatar.jpg',
        ]);
    }

    public function test_social_login_fails_without_email()
    {
        $this->mockSocialiteUser('google', ['getEmail' => null]);

        $response = $this->get(route('auth.callback', ['driver' => 'google']));

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

        $this->mockSocialiteUser('google', [
            'getEmail' => 'user@example.com',
            'getId' => 'google-id-999',
            'token' => 'oauth-token-abc',
        ]);

        $response = $this->get(route('auth.callback', ['driver' => 'google']));
        $user->refresh();

        $response->assertRedirect(route('profile.dashboard'));

        $this->assertAuthenticatedAs($user);
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

        $this->mockSocialiteUser('google', [
            'getEmail' => 'user2@example.com',
            'getName' => 'New Google User',
            'getId' => 'google-id-456',
            'token' => 'new-oauth-token',
        ]);

        $response = $this->get(route('auth.callback', ['driver' => 'google']));

        $response->assertRedirect(route('profile.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'user2@example.com',
            'provider_id' => 'google-id-456',
            'provider_type' => 'google',
        ]);

        $this->assertEquals(2, User::count());
        $this->assertEquals('user2@example.com', auth()->user()->email);
    }

    public function test_user_can_login_with_github_oauth()
    {
        $this->mockSocialiteUser('github');

        $response = $this->get(route('auth.callback', ['driver' => 'github']));

        $response->assertRedirect(route('profile.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'github@example.com',
            'provider_id' => 'github-id-456',
            'provider_type' => 'github',
            'provider_token' => 'fake-github-token',
            'avatar' => 'https://example.com/github-avatar.jpg',
        ]);
    }

    public function test_oauth_login_does_not_update_existing_avatar()
    {
        // Create a user with an existing avatar
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'avatar' => 'https://example.com/existing-avatar.jpg',
        ]);

        // Set up OAuth mock with a different avatar
        $this->mockSocialiteUser('google', [
            'getEmail' => 'user@example.com',
            'getId' => 'google-id-999',
            'token' => 'oauth-token-abc',
            'getAvatar' => 'https://example.com/google-avatar-new.jpg',
        ]);

        $this->get(route('auth.callback', ['driver' => 'google']));
        $user->refresh();

        // Check that OAuth provider details are updated
        $this->assertAuthenticatedAs($user);
        $this->assertEquals('google-id-999', $user->provider_id);
        $this->assertEquals('google', $user->provider_type);
        $this->assertEquals('oauth-token-abc', $user->provider_token);
        // But avatar should remain unchanged
        $this->assertEquals('https://example.com/existing-avatar.jpg', $user->avatar);
    }
}
