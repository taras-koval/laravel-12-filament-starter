<?php

namespace App\Filament\Resources\TranslationResource\Pages;

use App\Filament\Resources\TranslationResource;
use App\Models\Translation;
use Artisan;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListTranslations extends ListRecords
{
    protected static string $resource = TranslationResource::class;

    public function getTitle(): string
    {
        return 'Translations Management';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->tooltip(fn () => 'Sync database translations to language files. The app uses file-based translations by default.')
                ->disabled(fn () => Translation::count() === 0)
                ->requiresConfirmation()
                ->modalDescription('This will publish all translations from the database to language files.')
                ->action(fn () => $this->publishTranslations()),

            ActionGroup::make([
                Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->tooltip('Download all translations as CSV file.')
                    ->action(fn () => $this->exportCsv()),

                Action::make('importCsv')
                    ->label('Import CSV')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->tooltip('Upload and import translations from a CSV file.')
                    ->form([
                        Toggle::make('overwrite')->label('Overwrite existing values')->default(true),
                        FileUpload::make('file')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->disk('local')
                            ->directory('tmp/translation-imports')
                            ->visibility('private')
                            ->preserveFilenames()
                            ->required(),
                    ])
                    ->action(fn (array $data) => $this->importCsv($data)),
            ])
            ->button()
            ->label('CSV')
            ->tooltip('Download/Upload all translations as CSV file.'),

            ActionGroup::make([
                Action::make('generate')
                    ->label('Generate Lang Files')
                    ->icon('heroicon-o-code-bracket')
                    ->tooltip('Scan project code and generate translation keys in language files.')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Translation Keys')
                    ->modalDescription('This will scan your project code for translation function calls (__(), trans(), @lang) and add missing keys to language files.')
                    ->modalSubmitActionLabel('Yes, generate keys')
                    ->action(fn () => $this->generateTranslations()),

                Action::make('import')
                    ->label('Import from Lang Files')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->tooltip('Import translations from language files in the project (lang/ directory) to the database.')
                    ->form([
                        Toggle::make('overwrite')
                            ->label('Overwrite existing values')
                            ->default(false)
                            ->helperText('When enabled, existing translation values will be overwritten. When disabled, only missing values will be added.'),
                    ])
                    ->action(fn (array $data) => $this->importFromLangFiles($data)),
            ])
            ->button()
            ->label('Other')
            ->tooltip('More actions'),
        ];
    }

    private function publishTranslations(): void
    {
        Artisan::call('translations:publish');
        Notification::make()
            ->title('Translations published')
            ->body('All translations have been published to language files.')
            ->success()
            ->send();
    }

    private function generateTranslations(): void
    {
        Artisan::call('translations:generate');
        Notification::make()
            ->title('Translation keys generated')
            ->body('Project code has been scanned and translation keys have been added to language files.')
            ->success()
            ->send();
    }

    private function importFromLangFiles(array $data): void
    {
        $overwrite = (bool) ($data['overwrite'] ?? false);
        $command = $overwrite ? 'translations:import --force' : 'translations:import';
        Artisan::call($command);
        Notification::make()
            ->title('Translations imported')
            ->body('All translations have been imported from language files.')
            ->success()
            ->send();
    }

    private function exportCsv(): StreamedResponse
    {
        $locales = collect(config('translation-manager.available_locales'))->pluck('code')->toArray();
        $fileName = 'translations-' . now()->format('Y_m_d-His') . '.csv';

        return response()->streamDownload(function () use ($locales) {
            $handle = fopen('php://output', 'wb');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV header
            fputcsv($handle, array_merge(['group', 'key'], $locales));

            Translation::query()
                ->orderBy('group')
                ->orderBy('key')
                ->chunk(500, function ($chunk) use ($handle, $locales) {
                    foreach ($chunk as $translation) {
                        $row = [
                            $translation->group,
                            $translation->key,
                        ];
                        foreach ($locales as $locale) {
                            $row[] = $translation->values[$locale] ?? '';
                        }
                        fputcsv($handle, $row);
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importCsv(array $data): void
    {
        $file = $data['file'] ?? null;
        $overwrite = (bool) ($data['overwrite'] ?? true);

        if (!$file || !is_string($file)) {
            Notification::make()->title('No file uploaded')->danger()->send();
            return;
        }

        $path = Storage::disk('local')->path($file);
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            Notification::make()->title('Unable to read CSV')->danger()->send();
            return;
        }

        $header = fgetcsv($handle);
        if (!$header || count($header) < 3) {
            fclose($handle);
            Notification::make()->title('Invalid CSV format')->danger()->send();
            return;
        }

        // Strip UTF-8 BOM if present
        if (isset($header[0]) && is_string($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        $locales = array_slice($header, 2);
        $rows = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $group = $row[0] !== '' ? $row[0] : 'general';
            $key = $row[1];
            $values = [];
            foreach ($locales as $i => $locale) {
                $values[$locale] = $row[$i + 2] ?? '';
            }

            $model = Translation::firstOrCreate([
                'group' => $group,
                'key' => $key,
            ], [
                'values' => [],
            ]);

            $current = $model->values ?? [];
            foreach ($values as $locale => $val) {
                if ($overwrite || !isset($current[$locale])) {
                    if ($val !== '') {
                        $current[$locale] = $val;
                    }
                }
            }
            $model->update(['values' => $current]);
            $rows++;
        }
        fclose($handle);
        Storage::disk('local')->delete($file);

        Notification::make()
            ->title('CSV imported')
            ->body("Processed {$rows} rows.")
            ->success()
            ->send();
    }
}
