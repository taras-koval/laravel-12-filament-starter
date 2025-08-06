<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslationsPublish extends Command
{
    protected $signature = 'translations:publish';
    protected $description = 'Publish translations from database to language files';

    public function handle(): int
    {
        $localesConfig = config('translation-manager.available_locales', [
            ['code' => 'en', 'name' => 'English', 'flag' => 'gb'],
        ]);
        $locales = collect($localesConfig)->pluck('code')->all();

        $this->info('📤 Publishing translations...');

        foreach ($locales as $locale) {
            $this->publishLocale($locale);
            $this->line("✓ Published locale: {$locale}");
        }

        $this->info('✅ All translations published!');
        return self::SUCCESS;
    }

    private function publishLocale(string $locale): void
    {
        $translations = Translation::getAllByLocale($locale);
        if (empty($translations)) {
            return;
        }

        $langPath = base_path("lang/$locale");
        if (!File::isDirectory($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        foreach ($translations as $group => $items) {
            if ($group === 'general') {
                $this->publishJson($locale, $items);
            } else {
                $this->publishPhp($locale, $group, $items);
            }
        }
    }

    private function publishJson(string $locale, array $translations): void
    {
        $jsonPath = base_path("lang/$locale.json");
        $content = json_encode(
            value: $translations,
            flags: JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
        File::put($jsonPath, $content);
    }

    private function publishPhp(string $locale, string $group, array $translations): void
    {
        $phpPath = base_path("lang/$locale/$group.php");
        $array = $this->buildNestedArray($translations);
        $content = "<?php\n\nreturn " . $this->arrayToString($array) . ";\n";
        File::put($phpPath, $content);
    }

    private function buildNestedArray(array $flat): array
    {
        $nested = [];

        foreach ($flat as $key => $value) {
            $keys = explode('.', $key);
            $current = &$nested;

            foreach ($keys as $i => $keyPart) {
                if ($i === count($keys) - 1) {
                    $current[$keyPart] = $value;
                } else {
                    $current[$keyPart] ??= [];
                    $current = &$current[$keyPart];
                }
            }
        }

        return $nested;
    }

    private function arrayToString(array $array, int $depth = 1): string
    {
        if (empty($array)) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth);
        $items = [];

        foreach ($array as $key => $value) {
            $keyStr = var_export($key, true);
            $valueStr = is_array($value)
                ? $this->arrayToString($value, $depth + 1)
                : var_export($value, true);

            $items[] = "{$indent}{$keyStr} => $valueStr";
        }

        $prevIndent = str_repeat('    ', $depth - 1);
        return "[\n" . implode(",\n", $items) . ",\n$prevIndent]";
    }
}
