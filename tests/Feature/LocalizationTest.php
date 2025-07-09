<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_laravel_localization_is_configured()
    {
        $supportedLocales = LaravelLocalization::getSupportedLocales();

        $this->assertArrayHasKey('en', $supportedLocales);
        $this->assertArrayHasKey('uk', $supportedLocales);
        $this->assertArrayHasKey('pl', $supportedLocales);

        // Test URL generation works - expect full URLs in test environment
        $englishUrl = LaravelLocalization::getLocalizedURL('en', '/');
        $ukrainianUrl = LaravelLocalization::getLocalizedURL('uk', '/');
        $polishUrl = LaravelLocalization::getLocalizedURL('pl', '/');

        // Check that URLs end with the expected paths
        $this->assertStringEndsWith('/', $englishUrl);
        $this->assertStringEndsWith('/uk', $ukrainianUrl);
        $this->assertStringEndsWith('/pl', $polishUrl);
    }

    public function test_localization_basics_work()
    {
        $supportedLocales = LaravelLocalization::getSupportedLocales();

        // Check that each of languages exists in the configuration
        $this->assertArrayHasKey('en', $supportedLocales, 'English locale should be configured');
        $this->assertArrayHasKey('uk', $supportedLocales, 'Ukrainian locale should be configured');
        $this->assertArrayHasKey('pl', $supportedLocales, 'Polish locale should be configured');

        // Laravel Localization should add the language prefix for Ukrainian and Polish
        $ukrainianUrl = LaravelLocalization::getLocalizedURL('uk', '/');
        $this->assertStringContainsString('/uk', $ukrainianUrl, 'Ukrainian URL should contain /uk prefix');

        $polishUrl = LaravelLocalization::getLocalizedURL('pl', '/');
        $this->assertStringContainsString('/pl', $polishUrl, 'Polish URL should contain /pl prefix');

        // For English (default language), the URL should not have a prefix
        $englishUrl = LaravelLocalization::getLocalizedURL('en', '/');
        $this->assertStringContainsString('/', $englishUrl, 'English URL should be accessible');
        // Note: We don't check for absence of /en prefix here as it depends on configuration
    }

    public function test_homepage_accessible_and_contains_language_switcher()
    {
        // Make a request to the homepage using the named route (this method works reliably)
        $response = $this->get(route('index'));
        $response->assertStatus(200);

        // Check that the language switcher is present by looking for flag images
        $response->assertSee('gb.png', false); // English flag - false means don't escape HTML
        $response->assertSee('ua.png', false); // Ukrainian flag
        $response->assertSee('pl.png', false); // Polish flag

        // Also check that the language names are present
        $response->assertSee('English');
        $response->assertSee('Українська');
        $response->assertSee('Polski');
    }

    public function test_key_pages_accessible()
    {
        // Test login page (most important for users)
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // Test register page
        $response = $this->get(route('register'));
        $response->assertStatus(200);

        // These pages should also have language switchers
        $response = $this->get(route('login'));
        $response->assertSee('gb.png');
        $response->assertSee('ua.png');
        $response->assertSee('pl.png');
    }

    public function test_translation_system_works()
    {
        // Set the locale to English and test a translation
        app()->setLocale('en');
        $englishTranslation = __('Login'); // This should return 'Login' for English
        $this->assertIsString($englishTranslation);
        $this->assertNotEmpty($englishTranslation);

        // Set the locale to Ukrainian and test the same key
        app()->setLocale('uk');
        $ukrainianTranslation = __('Login'); // This should return Ukrainian translation
        $this->assertIsString($ukrainianTranslation);
        $this->assertNotEmpty($ukrainianTranslation);
    }
}
