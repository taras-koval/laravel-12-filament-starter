<?php

namespace App\Models;

use App\Enums\UserPermissionEnum;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Tests\Unit\Models\UserTest;

/**
 * Tests @see UserTest
 */
class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'email_verified_at',
        'provider_id',
        'provider_type',
        'provider_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function name(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::title($value),
        );
    }

    public function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (blank($this->avatar)) {
                    return asset('images/avatar-default.svg');
                }

                if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                    return $this->avatar;
                }

                return Storage::disk('public')->url($this->avatar);
            }
        );
    }

    protected static function booted(): void
    {
        // Automatically clean up avatar field if file doesn't exist
        static::retrieved(function ($user) {
            if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                if (!Storage::disk('public')->exists($user->avatar)) {
                    $user->updateQuietly(['avatar' => null]);
                }
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasPermissionTo(UserPermissionEnum::ADMIN_PANEL_ACCESS);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }
}
