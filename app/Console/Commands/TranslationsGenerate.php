<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslationsGenerate extends Command
{
    protected $signature = 'translations:generate';
    protected $description = 'Scan code and generate translation files for all configured locales';

    public function handle(): int
    {
        $this->info('🌐 Generating translation files...');

        $localesConfig = config('translation-manager.available_locales', [
            ['code' => 'en', 'name' => 'English', 'flag' => 'gb'],
        ]);
        $locales = collect($localesConfig)->pluck('code')->all();

        if (empty($locales)) {
            $this->error('No locales found in config/translation-manager.php available_locales');
            return self::FAILURE;
        }

        // Scan all code files for translation keys
        $this->info('🔍 Scanning code for translation keys...');
        $translationKeys = $this->scanCodeForTranslationKeys();
        $this->info('Found '.count($translationKeys).' translation keys');

        // Generate files for each configured locale
        foreach ($locales as $locale) {
            $this->generateFilesForLocale($locale, $translationKeys);
        }

        $this->info('✅ Translation files generated successfully!');
        return self::SUCCESS;
    }

    /**
     * Scan all PHP and Blade files for translation function calls
     */
    private function scanCodeForTranslationKeys(): array
    {
        $keys = [];

        // Enhanced patterns to handle escaped quotes and complex strings
        $patterns = [
            "/__\('((?:[^'\\\\]|\\\\.)*)'\s*(?:,\s*[^)]+)?\)/", // __('text') або __('text', [...])
            '/__\("((?:[^"\\\\]|\\\\.)*)"\s*(?:,\s*[^)]+)?\)/', // __("text") або __("text", [...])

            "/\{\{\s*__\('((?:[^'\\\\]|\\\\.)*)'\s*(?:,\s*[^}]+)?\)\s*\}\}/", // {{ __('text', [...]) }}
            '/\{\{\s*__\("((?:[^"\\\\]|\\\\.)*)"\s*(?:,\s*[^}]+)?\)\s*\}\}/', // {{ __("text", [...]) }}

            "/@lang\('((?:[^'\\\\]|\\\\.)*)'\s*(?:,\s*[^)]+)?\)/", // @lang('text', [...])
            '/@lang\("((?:[^"\\\\]|\\\\.)*)"\s*(?:,\s*[^)]+)?\)/', // @lang("text", [...])

            "/trans\('((?:[^'\\\\]|\\\\.)*)'\s*(?:,\s*[^)]+)?\)/", // trans('text', [...])
            '/trans\("((?:[^"\\\\]|\\\\.)*)"\s*(?:,\s*[^)]+)?\)/', // trans("text", [...])
        ];

        // Get all files to scan
        $files = $this->getFilesToScan();

        foreach ($files as $file) {
            $content = File::get($file);

            // Apply each pattern to extract translation keys
            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $content, $matches);

                if (!empty($matches[1])) {
                    foreach ($matches[1] as $key) {
                        // Clean the key by removing escape characters
                        $cleanKey = stripslashes(trim($key));
                        if (!empty($cleanKey)) {
                            $keys[] = $cleanKey;
                        }
                    }
                }
            }
        }

        // Return unique keys sorted alphabetically
        return array_unique($keys);
    }

    /**
     * Get all PHP and Blade files that should be scanned
     */
    private function getFilesToScan(): array
    {
        $directories = [
            app_path(),                // All application code
            resource_path('views'),    // Blade templates
            base_path('routes'),       // Route files
        ];

        $files = [];

        foreach ($directories as $directory) {
            if (File::isDirectory($directory)) {
                $foundFiles = File::allFiles($directory);

                foreach ($foundFiles as $file) {
                    // Include only PHP and Blade files
                    if ($file->getExtension() === 'php' ||
                        str_ends_with($file->getFilename(), '.blade.php')) {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Generate all necessary translation files for a specific locale
     */
    private function generateFilesForLocale(string $locale, array $translationKeys): void
    {
        $this->info("📝 Processing locale: {$locale}");

        // Create a locale directory in the correct Laravel location (lang/ not resources/lang/)
        $localeDir = base_path("lang/{$locale}");
        if (!File::isDirectory($localeDir)) {
            File::makeDirectory($localeDir, 0755, true);
        }

        // Copy standard Laravel translation files
        $this->copyStandardFiles($locale);

        // Create or update a JSON file with scanned keys
        $this->updateJsonTranslationFile($locale, $translationKeys);
    }

    /**
     * Copy standard Laravel translation files from English to target locale
     */
    private function copyStandardFiles(string $locale): void
    {
        // Use correct Laravel lang directory structure
        $englishDir = base_path('lang/en');
        $localeDir = base_path("lang/{$locale}");

        // Standard Laravel translation files
        $standardFiles = ['auth.php', 'pagination.php', 'passwords.php', 'validation.php'];

        foreach ($standardFiles as $file) {
            $sourcePath = "{$englishDir}/{$file}";
            $targetPath = "{$localeDir}/{$file}";

            if (File::exists($sourcePath) && !File::exists($targetPath)) {
                File::copy($sourcePath, $targetPath);
            }
        }
    }

    /**
     * Create or update a JSON translation file with new keys
     */
    private function updateJsonTranslationFile(string $locale, array $translationKeys): void
    {
        $jsonFile = base_path("lang/{$locale}.json");

        // Load existing translations
        $existingTranslations = [];
        if (File::exists($jsonFile)) {
            $content = File::get($jsonFile);
            $existingTranslations = json_decode($content, true, 512, JSON_THROW_ON_ERROR) ?: [];
        }

        $updatedTranslations = $existingTranslations;
        $newKeysAdded = 0;
        $originalLocale = app()->getLocale();

        // Temporarily switch to the target locale for translation checks
        app()->setLocale($locale);

        foreach ($translationKeys as $key) {
            // Skip if the key already exists in the JSON file
            if (array_key_exists($key, $updatedTranslations)) {
                continue;
            }

            // Check if translation already exists in standard Laravel files
            $translation = trans($key);
            $existsInStandardFiles = ($translation !== $key);

            // Only add to JSON if it doesn't exist in standard files
            if (!$existsInStandardFiles) {
                $updatedTranslations[$key] = ($locale === 'en') ? $key : '';
                $newKeysAdded++;
            }
        }

        // Restore original locale
        app()->setLocale($originalLocale);

        // Save updated translations
        ksort($updatedTranslations);
        $jsonContent = json_encode($updatedTranslations, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($jsonFile, $jsonContent);

        if ($newKeysAdded > 0) {
            $this->info("Added {$newKeysAdded} new keys to {$locale}.json");
        }
    }
}
