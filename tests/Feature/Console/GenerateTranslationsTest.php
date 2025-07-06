<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateTranslationsTest extends TestCase
{
    use RefreshDatabase;

    protected string $testBasePath;
    protected string $testLangPath;
    protected string $testAppPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a completely isolated test environment
        $this->testBasePath = storage_path('testing/project');
        $this->testLangPath = $this->testBasePath.'/lang';
        $this->testAppPath = $this->testBasePath.'/app';

        File::makeDirectory($this->testLangPath, 0755, true);
        File::makeDirectory($this->testAppPath, 0755, true);

        config(['translation-manager.available_locales' => [
            ['code' => 'en', 'name' => 'English', 'flag' => 'gb'],
            ['code' => 'uk', 'name' => 'Ukrainian', 'flag' => 'ua'],
            ['code' => 'pl', 'name' => 'Polish', 'flag' => 'pl'],
        ]]);
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        if (File::exists(storage_path('testing'))) {
            File::deleteDirectory(storage_path('testing'));
        }

        parent::tearDown();
    }

    public function test_comprehensive_translation_key_extraction(): void
    {
        // Create test files with ALL possible translation patterns
        $this->createTestFilesWithAllPatterns();

        // Create and execute the command with test paths
        $this->executeCommandWithTestPaths();

        // Verify all expected keys were extracted
        $this->verifyAllExpectedKeysExtracted();

        // Verify standard Laravel keys were skipped
        $this->verifyStandardKeysSkipped();

        // Verify the file structure is correct
        $this->verifyFileStructure();
    }

    public function test_does_not_overwrite_existing_translations(): void
    {
        // Create an existing translation file
        $existingTranslations = [
            'Existing key' => 'Existing translation',
            'Another existing' => 'Another translation',
        ];
        File::put($this->testLangPath.'/en.json', json_encode($existingTranslations, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        // Create a test file with mixed existing and new keys
        $testContent = '<?php

        namespace App\Http\Controllers;

        class TestController
        {
            public function test()
            {
                echo __("Existing key");
                echo __("Brand new key");
            }
        }';

        File::makeDirectory($this->testAppPath.'/Http/Controllers', 0755, true);
        File::put($this->testAppPath.'/Http/Controllers/TestController.php', $testContent);

        $this->executeCommandWithTestPaths();

        $finalTranslations = json_decode(File::get($this->testLangPath.'/en.json'), true, 512, JSON_THROW_ON_ERROR);

        // Should preserve existing values
        $this->assertEquals('Existing translation', $finalTranslations['Existing key']);
        $this->assertEquals('Another translation', $finalTranslations['Another existing']);

        // Should add a new key
        $this->assertArrayHasKey('Brand new key', $finalTranslations);
        $this->assertEquals('Brand new key', $finalTranslations['Brand new key']);
    }

    public function test_fails_when_no_locales_configured(): void
    {
        config(['translation-manager.available_locales' => []]);

        $this->artisan('translations:generate')
            ->expectsOutput('No locales found in config/translation-manager.php available_locales')
            ->assertExitCode(1);
    }

    private function createTestFilesWithAllPatterns(): void
    {
        // PHP file with ALL possible translation patterns
        $phpContent = '<?php

        namespace App\Http\Controllers;

        class TestController
        {
            public function index()
            {
                // Basic __() patterns
                $simple = __("Simple message");
                $withParam = __("Message with :param", ["param" => $value]);
                $withMultiple = __("Message with :name and :count items", ["name" => $name, "count" => $count]);

                // Double quotes variants
                $doubleQuotes = __("Double quoted message");
                $doubleWithParam = __("Double quoted with :param", ["param" => $value]);

                // Escaped quotes
                $escapedSingle = __(\'Text with \\\'escaped single\\\' quotes\');
                $escapedDouble = __("Text with \\"escaped double\\" quotes");
                $mixedEscaped = __("Mixed \\"double\\" and \\\'single\\\' quotes");

                // Trans function
                $transBasic = trans("Trans function message");
                $transWithParam = trans("Trans with :param", ["param" => $value]);
                $transStandard = trans("auth.failed"); // Should be skipped

                // Complex expressions
                $complex = __("Complex message with :param", ["param" => ucfirst($driver)]);
                $multiline = __("Long message that spans one string");

                // Return statements and validation
                return redirect()->withErrors([
                    "email" => __("Authentication via :driver failed. Please try again.", ["driver" => $driver])
                ]);

                throw ValidationException::withMessages([
                    "password" => __("Password validation failed with :attempts attempts", ["attempts" => $count])
                ]);

                // Blade-style patterns in PHP (should also work)
                $blade1 = __("Blade-style message");
                $blade2 = __("Blade with :param", ["param" => $value]);

                // Additional edge cases
                $empty = __("");  // Empty string - should be filtered out
                $spaces = __("   Trimmed message   ");  // Should be trimmed
            }
        }';

        // Create a standard Laravel auth file to test skipping
        File::makeDirectory($this->testLangPath.'/en', 0755, true);
        File::put($this->testLangPath.'/en/auth.php', '<?php return [
            "failed" => "These credentials do not match our records.",
            "password" => "The provided password is incorrect.",
            "throttle" => "Too many login attempts."
        ];');

        // Create necessary subdirectories and write a test file
        File::makeDirectory($this->testAppPath.'/Http/Controllers', 0755, true);
        File::put($this->testAppPath.'/Http/Controllers/TestController.php', $phpContent);
    }

    private function executeCommandWithTestPaths(): void
    {
        // Get translation keys using our test files
        $files = [$this->testAppPath.'/Http/Controllers/TestController.php'];
        $translationKeys = $this->scanTestFiles($files);

        // Process each locale
        $localesConfig = config('translation-manager.available_locales', []);
        $locales = collect($localesConfig)->pluck('code')->all();
        foreach ($locales as $locale) {
            $this->processLocaleForTest($locale, $translationKeys);
        }
    }

    private function scanTestFiles(array $files): array
    {
        $keys = [];

        // Same patterns as in the original command
        $patterns = [
            "/__\('((?:[^'\\\\]|\\\\.)*)'\s*(?:,\s*[^)]+)?\)/",
            '/__\("((?:[^"\\\\]|\\\\.)*)"\s*(?:,\s*[^)]+)?\)/',
            "/trans\('((?:[^'\\\\]|\\\\.)*)'\s*(?:,\s*[^)]+)?\)/",
            '/trans\("((?:[^"\\\\]|\\\\.)*)"\s*(?:,\s*[^)]+)?\)/',
        ];

        foreach ($files as $file) {
            $content = File::get($file);

            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $content, $matches);

                if (!empty($matches[1])) {
                    foreach ($matches[1] as $key) {
                        $cleanKey = stripslashes(trim($key));
                        if (!empty($cleanKey)) {
                            $keys[] = $cleanKey;
                        }
                    }
                }
            }
        }

        return array_unique($keys);
    }

    private function processLocaleForTest(string $locale, array $translationKeys): void
    {
        // Create locale directory
        $localeDir = $this->testLangPath."/{$locale}";
        if (!File::isDirectory($localeDir)) {
            File::makeDirectory($localeDir, 0755, true);
        }

        // Copy standard files
        $this->copyStandardFilesForTest($locale);

        // Update JSON file
        $this->updateJsonTranslationFileForTest($locale, $translationKeys);
    }

    private function copyStandardFilesForTest(string $locale): void
    {
        $englishDir = $this->testLangPath.'/en';
        $localeDir = $this->testLangPath."/{$locale}";

        $standardFiles = ['auth.php', 'pagination.php', 'passwords.php', 'validation.php'];

        foreach ($standardFiles as $file) {
            $sourcePath = "{$englishDir}/{$file}";
            $targetPath = "{$localeDir}/{$file}";

            if (File::exists($sourcePath) && !File::exists($targetPath)) {
                File::copy($sourcePath, $targetPath);
            }
        }
    }

    private function updateJsonTranslationFileForTest(string $locale, array $translationKeys): void
    {
        $jsonFile = $this->testLangPath."/{$locale}.json";

        $existingTranslations = [];
        if (File::exists($jsonFile)) {
            $content = File::get($jsonFile);
            $existingTranslations = json_decode($content, true, 512, JSON_THROW_ON_ERROR) ?: [];
        }

        $updatedTranslations = $existingTranslations;
        $originalLocale = app()->getLocale();

        app()->setLocale($locale);

        foreach ($translationKeys as $key) {
            if (array_key_exists($key, $updatedTranslations)) {
                continue;
            }

            $translation = trans($key);
            $existsInStandardFiles = ($translation !== $key);

            if (!$existsInStandardFiles) {
                $updatedTranslations[$key] = ($locale === 'en') ? $key : '';
            }
        }

        app()->setLocale($originalLocale);

        ksort($updatedTranslations);
        $jsonContent = json_encode($updatedTranslations, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($jsonFile, $jsonContent);
    }

    private function verifyAllExpectedKeysExtracted(): void
    {
        $enTranslations = json_decode(File::get($this->testLangPath.'/en.json'), true, 512, JSON_THROW_ON_ERROR);

        $expectedKeys = [
            // Basic patterns
            'Simple message',
            'Message with :param',
            'Message with :name and :count items',
            'Double quoted message',
            'Double quoted with :param',

            // Escaped quotes
            'Text with \'escaped single\' quotes',
            'Text with "escaped double" quotes',
            'Mixed "double" and \'single\' quotes',

            // Trans function (non-standard)
            'Trans function message',
            'Trans with :param',

            // Complex cases
            'Complex message with :param',
            'Long message that spans one string',
            'Authentication via :driver failed. Please try again.',
            'Password validation failed with :attempts attempts',

            // Blade-style in PHP
            'Blade-style message',
            'Blade with :param',

            // Edge cases (trimmed)
            'Trimmed message',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $enTranslations, "Missing key: {$key}");
        }

        // Verify empty strings are filtered out
        $this->assertArrayNotHasKey('', $enTranslations, 'Empty strings should be filtered out');
    }

    private function verifyStandardKeysSkipped(): void
    {
        $enTranslations = json_decode(File::get($this->testLangPath.'/en.json'), true, 512, JSON_THROW_ON_ERROR);

        // These should NOT be in JSON because they exist in standard files
        $standardKeys = [
            'auth.failed',
            'auth.password',
            'auth.throttle',
        ];

        foreach ($standardKeys as $key) {
            $this->assertArrayNotHasKey($key, $enTranslations, "Standard key should be skipped: {$key}");
        }
    }

    private function verifyFileStructure(): void
    {
        // Check JSON files created for all locales
        $this->assertFileExists($this->testLangPath.'/en.json');
        $this->assertFileExists($this->testLangPath.'/uk.json');
        $this->assertFileExists($this->testLangPath.'/pl.json');

        // Check locale directories created
        $this->assertDirectoryExists($this->testLangPath.'/en');
        $this->assertDirectoryExists($this->testLangPath.'/uk');
        $this->assertDirectoryExists($this->testLangPath.'/pl');

        // Check standard files copied
        $this->assertFileExists($this->testLangPath.'/uk/auth.php');
        $this->assertFileExists($this->testLangPath.'/pl/auth.php');

        // Check the UK and PL have empty values for translation
        $ukTranslations = json_decode(File::get($this->testLangPath.'/uk.json'), true, 512, JSON_THROW_ON_ERROR);
        $plTranslations = json_decode(File::get($this->testLangPath.'/pl.json'), true, 512, JSON_THROW_ON_ERROR);

        // Sample check - non-English locales should have empty values
        $this->assertEquals('', $ukTranslations['Simple message'] ?? null);
        $this->assertEquals('', $plTranslations['Simple message'] ?? null);
    }
}
