<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\Events\Registered;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to delete this user? This action cannot be undone.')
                ->successNotificationTitle('User deleted'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? self::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert boolean toggle to timestamp or null
        $data['email_verified_at'] = !empty($data['email_verified_at']) ? now() : null;

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->record;

        $emailChanged = $user->wasChanged('email');
        $verificationChanged = $user->wasChanged('email_verified_at');
        $isCurrentlyUnverified = is_null($user->email_verified_at);

        // Send a verification email in these cases:
        // 1. Email was changed and the user is currently unverified
        // 2. Verification status was changed to unverified
        if ($isCurrentlyUnverified && ($emailChanged || $verificationChanged)) {
            event(new Registered($user));

            $title = $emailChanged ? 'Email address was changed' : 'Email address unverified';

            Notification::make()
                ->info()
                ->title($title)
                ->body("Verification email has been sent to {$user->email}")
                ->icon('heroicon-o-envelope')
                ->duration(10000)
                ->send();
        }
    }
}
