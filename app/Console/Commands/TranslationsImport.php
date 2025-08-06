<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslationsImport extends Command
{
    protected $signature = 'translations:import {--force : Overwrite existing translations}';
    protected $description = 'Import translations from language files to database';

    public function handle(): int
    {
		$localesConfig = config('translation-manager.available_locales', [
			['code' => 'en', 'name' => 'English', 'flag' => 'gb'],
		]);
		$locales = collect($localesConfig)->pluck('code')->all();
        $force = $this->option('force');
        $count = 0;

        $this->info('📥 Importing translations...');

        foreach ($locales as $locale) {
            $imported = $this->importLocale($locale, $force);
            $count += $imported;
            $this->line("Locale $locale: $imported keys");
        }

        $this->info("✅ Total imported: $count translation keys");
        return self::SUCCESS;
    }

    private function importLocale(string $locale, bool $force): int
    {
        $count = 0;

        // Import JSON translations
        $jsonPath = base_path("lang/$locale.json");
        if (File::exists($jsonPath)) {
            $translations = json_decode(File::get($jsonPath), true, 512, JSON_THROW_ON_ERROR);
            foreach ($translations as $key => $value) {
                $this->saveTranslation($key, 'general', $locale, $value, $force);
                $count++;
            }
        }

        // Import PHP files
        $langPath = base_path("lang/{$locale}");
        if (File::isDirectory($langPath)) {
            foreach (File::files($langPath) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $group = $file->getFilenameWithoutExtension();
                $translations = require $file->getPathname();
                $count += $this->importArray($translations, $group, $locale, '', $force);
            }
        }

        return $count;
    }

    private function importArray(array $array, string $group, string $locale, string $prefix, bool $force): int
    {
        $count = 0;

        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "$prefix.$key" : $key;

            if (is_array($value)) {
                $count += $this->importArray($value, $group, $locale, $fullKey, $force);
            } else {
                $this->saveTranslation($fullKey, $group, $locale, $value, $force);
                $count++;
            }
        }

        return $count;
    }

    private function saveTranslation(string $key, ?string $group, string $locale, $value, bool $force): void
    {
        $translation = Translation::where('key', $key)
            ->where('group', $group)
            ->first();

        if (!$translation) {
            Translation::create([
                'key' => $key,
                'group' => $group,
                'values' => [$locale => $value],
            ]);
        } else {
            $values = $translation->values;

            if ($force || !isset($values[$locale])) {
                $values[$locale] = $value;
                $translation->update(['values' => $values]);
            }
        }
    }
}
