<?php

namespace App\Filament\Resources\ManagerResource\Pages;

use App\Filament\Resources\ManagerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManagers extends ListRecords
{
    protected static string $resource = ManagerResource::class;

    protected static ?string $title = 'Managers';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New manager'),
        ];
    }
}
