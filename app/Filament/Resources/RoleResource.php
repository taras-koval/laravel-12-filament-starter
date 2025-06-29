<?php

namespace App\Filament\Resources;

use App\Enums\UserPermissionEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use Tests\Feature\Filament\RoleResourceTest;

/**
 * Tests @see RoleResourceTest
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $slug = 'roles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('name')->required()->unique(ignoreRecord: true)->label('Role'),
            ]),
            Section::make()->schema([
                CheckboxList::make('permissions')
                    ->relationship(
                        name: 'permissions',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->orderBy('id'),
                    )
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        return UserPermissionEnum::tryFrom($record->name)?->getLabel() ?: $record->name;
                    })
                    ->columns(4),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->limit(30)->tooltip(tooltip_callback(limit: 30))
                    ->badge()->color(fn (string $state): string => UserRoleEnum::getColorForRole($state)),
                TextColumn::make('guard_name')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('permissions.name')->badge()->color('success')->searchable()->limitList(8)
                    ->formatStateUsing(fn ($state) => UserPermissionEnum::tryFrom($state)?->getLabel() ?: $state),
                TextColumn::make('created_at')->dateTime('M j, Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime('M j, Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('created_at')->form([
                    DatePicker::make('created_from'),
                    DatePicker::make('created_until')->default(now()),
                ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->defaultSort('created_at')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public static function getModelLabel(): string
    {
        return 'Roles';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
