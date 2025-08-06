<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TranslationResource\Pages;
use App\Models\Translation;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class TranslationResource extends Resource
{
    protected static ?string $model = Translation::class;
    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('key')->required()->maxLength(500)->disabled(),
                TextInput::make('group')->maxLength(255)->disabled(),
            ]),

            Section::make('Translations')->schema(
                collect(config('translation-manager.available_locales'))->map(function ($locale) {
                    return Textarea::make("values.{$locale['code']}")
                        ->label("{$locale['name']} ({$locale['code']})")
                        ->rows(2);
                })->toArray()
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Translation Key')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->formatStateUsing(fn($record) => $record->group === 'general' ? $record->key : "$record->group.$record->key")
                    ->color('gray')
                    ->icon('heroicon-m-language')
                    ->badge()
                    ->tooltip(fn ($record) => $record->key),

                TextColumn::make('group')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ...collect(config('translation-manager.available_locales'))->map(function ($locale) {
                    return TextColumn::make("values.{$locale['code']}")
                        ->label(strtoupper($locale['code']))
                        ->limit(20)
                        ->searchable()
                        ->sortable()
                        ->tooltip(fn ($record) => $record->values[$locale['code']] ?? '')
                        ->toggleable();
                }),

                TextColumn::make('updated_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(fn () => Translation::distinct()
                        ->whereNotNull('group')
                        ->pluck('group', 'group')
                        ->toArray()
                    )
                    ->placeholder('All groups'),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->defaultSort('key')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateActions([
                Action::make('import')
                    ->label('Import from Files')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Import Translations')
                    ->modalDescription('This will import all translations from language files to the database.')
                    ->modalSubmitActionLabel('Import translations')
                    ->action(function () {
                        Artisan::call('translations:import --force');
                        Notification::make()
                            ->title('Translations imported')
                            ->body('All translations have been imported from files.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTranslations::route('/'),
            'edit' => Pages\EditTranslation::route('/{record}/edit'),
        ];
    }
}
