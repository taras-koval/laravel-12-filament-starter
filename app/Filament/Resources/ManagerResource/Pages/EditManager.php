<?php

namespace App\Filament\Resources\ManagerResource\Pages;

use App\Filament\Resources\ManagerResource;
use App\Filament\Resources\UserResource\Pages\EditUser;

class EditManager extends EditUser
{
    protected static string $resource = ManagerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? self::getResource()::getUrl('index');
    }

    // Email verification logic is inherited from EditUser
    // No need to override afterSave() unless you need additional manager-specific logic
}
