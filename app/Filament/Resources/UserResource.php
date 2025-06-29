<?php

namespace App\Filament\Resources;

use App\Enums\UserRoleEnum;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\Feature\Filament\UserResourceTest;

/**
 * Tests @see UserResourceTest
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $slug = 'users';

    protected static ?string $navigationIcon = 'heroicon-m-users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([
                Section::make('Personal Information')->schema([
                    TextInput::make('name')->string()->minLength(2)->placeholder('John Doe')->autofocus()->required(),
                    TextInput::make('email')->email()->placeholder('example@email.com')->unique(ignoreRecord: true)->required(),
                    TextInput::make('phone')->tel()->placeholder('+48 123 456 789')->unique(ignoreRecord: true),
                ])
                ->columnSpan(2),

                Grid::make(1)->schema([
                    Section::make()->schema([
                        FileUpload::make('avatar')
                            ->image()
                            ->maxSize(2048)
                            ->directory('avatars')
                            ->disk('public')
                            ->unique()
                            ->nullable()
                            ->previewable()
                            ->deleteUploadedFileUsing(function ($file, $record) {
                                if ($record?->avatar && !filter_var($record->avatar, FILTER_VALIDATE_URL)) {
                                    Storage::disk('public')->delete($record->avatar);
                                }
                            }),
                    ]),
                ])
                ->columnSpan(1),

                Section::make('Account Status')->schema([
                    Toggle::make('email_verified_at')
                        ->label('Email Verified')
                        // ->helperText("Mark if the user's email address has been verified.")
                        ->default(fn ($record, string $context) => $context === 'create' || filled($record?->email_verified_at))
                        ->dehydrated()
                        ->dehydrateStateUsing(fn ($state) => $state),

                    // Display roles as text in view mode
                    TextInput::make('roles_display')
                        ->label('Role')
                        ->formatStateUsing(fn ($record) => $record?->roles?->pluck('name')->implode(', '))
                        ->disabled()
                        ->visible(fn (string $context) => $context === 'view'),

                    Select::make('roles')->label('Role')->required()
                        ->relationship(
                            name: 'roles',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query) {
                                return $query->whereNotIn('name', [
                                    UserRoleEnum::ADMINISTRATOR->value,
                                    UserRoleEnum::USER->value,
                                ]);
                            }
                        )
                        ->saveRelationshipsUsing(function (Model $record, $state) {
                            if (auth()->user()->can('assign-roles', $record)) {
                                $record->roles()->sync($state);
                            }
                        })
                        ->disabled(function ($record) {
                            return $record && !auth()->user()->can('assign-roles', $record);
                        })
                        ->helperText(function ($record) {
                            if ($record && !auth()->user()->can('assign-roles', $record)) {
                                return 'You do not have permission to change this user\'s role.';
                            }

                            return null;
                        })
                        ->dehydrated(false)
                        ->default(function () {
                            if (static::class === ManagerResource::class) {
                                return Role::whereName(UserRoleEnum::MANAGER)->first()->id;
                            }

                            return Role::whereName(UserRoleEnum::USER)->first()->id;
                        })
                        ->visible(function (string $context) {
                            return static::class === ManagerResource::class && $context !== 'view';
                        }),
                ])
                ->columnSpan(2)
                ->collapsible(fn (string $context) => $context !== 'create' && $context !== 'view')
                ->collapsed(fn (string $context) => $context !== 'create' && $context !== 'view'),

                Section::make('Password')->schema([
                    TextInput::make('password')
                        ->password()
                        ->minLength(4)
                        ->confirmed()
                        ->label('New Password')
                        ->required(fn (string $context) => $context === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        ->autocomplete('new-password'),

                    TextInput::make('password_confirmation')
                        ->password()
                        ->dehydrated(false)
                        ->label('Confirm Password')
                        ->required(fn (string $context) => $context === 'create')
                        ->autocomplete('new-password'),
                ])
                ->columnSpan(2)
                ->collapsible(fn (string $context) => $context !== 'create')
                ->collapsed(fn (string $context) => $context !== 'create')
                ->visible(fn (string $context) => $context !== 'view'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')->circular()->label('Avatar')
                    ->url(fn (User $record) => $record->avatar_url)->openUrlInNewTab(),

                TextColumn::make('name')->searchable()->sortable()
                    ->limit(30)->tooltip(tooltip_callback(limit: 30)),

                TextColumn::make('email')->searchable()->sortable()
                    ->limit(30)->tooltip(tooltip_callback(limit: 30)),

                TextColumn::make('phone')->searchable()->sortable()->placeholder('-')->toggleable()
                    ->limit(18)->tooltip(tooltip_callback(limit: 18)),

                IconColumn::make('email_verified_at')->boolean()->label('Verified')->sortable()
                    ->getStateUsing(fn (User $record) => filled($record->email_verified_at))
                    ->trueColor('success')->falseColor('gray')
                    ->tooltip(fn (User $record) => $record->email_verified_at),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => UserRoleEnum::getColorForRole($state))
                    ->limit(14)->tooltip(tooltip_callback(limit: 14))
                    ->limitList(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')->date()->sortable()->toggleable(),

                TextColumn::make('updated_at')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('Verified')->query(function (Builder $query): Builder {
                    return $query->whereNotNull('email_verified_at');
                }),
                Filter::make('Unverified')->query(function (Builder $query): Builder {
                    return $query->whereNull('email_verified_at');
                }),
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
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Builder $usersQuery */
        $usersQuery = User::where(function ($query) {
            $query->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', UserRoleEnum::USER);
            })
            ->orWhereDoesntHave('roles');
        });

        return $usersQuery;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
