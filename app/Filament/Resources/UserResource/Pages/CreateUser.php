<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserRoleEnum;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Events\Registered;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert boolean toggle to timestamp or null
        $data['email_verified_at'] = !empty($data['email_verified_at']) ? now() : null;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->record;

        if (!$user->roles()->exists()) {
            $user->assignRole(UserRoleEnum::USER);
        }

        // Send verification email only if user is not verified
        if (is_null($user->email_verified_at)) {
            event(new Registered($user));

            Notification::make()
                ->info()
                ->title('Email verification sent')
                ->body("Verification email has been sent to {$user->email}")
                ->icon('heroicon-o-envelope')
                ->duration(10000)
                ->send();
        }
    }
}
