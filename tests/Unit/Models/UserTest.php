<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_is_titled_when_set()
    {
        $user = User::create([
            'name' => 'john doe',
            'email' => 'john@example.com',
            'password' => 'secret',
        ]);

        $this->assertEquals('John Doe', $user->name);
    }

    public function test_avatar_url_returns_external_url()
    {
        $user = User::factory()->make([
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        $this->assertEquals('https://example.com/avatar.jpg', $user->avatar_url);
    }

    public function test_avatar_url_returns_storage_url_for_local_path()
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/test.jpg', 'fake');

        $user = User::factory()->make([
            'avatar' => 'avatars/test.jpg',
        ]);

        $this->assertEquals(Storage::disk('public')->url('avatars/test.jpg'), $user->avatar_url);
    }

    public function test_avatar_url_returns_default_when_blank()
    {
        $user = User::factory()->make([
            'avatar' => null,
        ]);

        $this->assertEquals(asset('images/avatar-default.svg'), $user->avatar_url);
    }

    public function test_avatar_is_cleared_if_local_file_missing_on_retrieved()
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'avatar' => 'avatars/missing.jpg',
        ]);

        $this->assertDatabaseHas('users', ['avatar' => 'avatars/missing.jpg']);

        // trigger retrieved
        $freshUser = User::find($user->id);

        $this->assertNull($freshUser->avatar);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'avatar' => null]);
    }
}
