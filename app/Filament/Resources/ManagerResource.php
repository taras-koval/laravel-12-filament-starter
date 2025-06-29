<?php

namespace App\Filament\Resources;

use App\Enums\UserRoleEnum;
use App\Filament\Resources\ManagerResource\Pages;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Tests\Feature\Filament\ManagerResourceTest;

/**
 * Tests @see ManagerResourceTest
 */
class ManagerResource extends UserResource
{
    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Managers';

    protected static ?string $slug = 'managers';

    public static function getEloquentQuery(): Builder
    {
        /** @var Builder $usersQuery */
        $usersQuery = User::whereHas('roles', static function ($query) {
            $query->whereNot('name', UserRoleEnum::USER);
        });

        return $usersQuery;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('viewManagers', User::class);
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('viewManagers', User::class);
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('createManagers', User::class);
    }

    public static function canEdit($record): bool
    {
        return (bool) auth()->user()?->can('updateManagers', $record);
    }

    public static function canDelete($record): bool
    {
        return (bool) auth()->user()?->can('deleteManagers', $record);
    }

    public static function getModelLabel(): string
    {
        return 'Manager';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManagers::route('/'),
            'create' => Pages\CreateManager::route('/create'),
            'edit' => Pages\EditManager::route('/{record}/edit'),
        ];
    }
}
