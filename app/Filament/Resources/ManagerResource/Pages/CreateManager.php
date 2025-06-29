<?php

namespace App\Filament\Resources\ManagerResource\Pages;

use App\Filament\Resources\ManagerResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;

class CreateManager extends CreateUser
{
    protected static string $resource = ManagerResource::class;

    protected static ?string $title = 'Create manager';
}
