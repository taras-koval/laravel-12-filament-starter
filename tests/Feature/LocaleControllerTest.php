<?php

namespace Tests\Feature;

use App\Http\Controllers\LocaleController;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

class LocaleControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.available_locales' => [
                'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
                'uk' => ['name' => 'Ukrainian', 'native' => 'Українська', 'flag' => '🇺🇦'],
                'pl' => ['name' => 'Polish', 'native' => 'Polski', 'flag' => '🇵🇱'],
            ],
        ]);
    }

    public function test_switches_to_valid_locale()
    {
        $this->get('/locale/uk')
            ->assertRedirect();

        $this->assertEquals('uk', session('locale'));
    }

    public function test_ignores_invalid_locale()
    {
        session(['locale' => 'en']);

        $this->get('/locale/invalid')
            ->assertRedirect();

        $this->assertEquals('en', session('locale'));
    }

    public function test_redirects_back_to_previous_page()
    {
        $this->get('/');

        $this->get('/locale/uk', ['HTTP_REFERER' => url('/')])->assertRedirect('/');
    }

    public function test_locale_persists_across_requests()
    {
        $this->get('/locale/pl');
        $this->get('/');

        $this->assertEquals('pl', session('locale'));
    }

    public function test_multiple_locale_switches()
    {
        $locales = ['en', 'uk', 'pl', 'en'];

        foreach ($locales as $locale) {
            $this->get("/locale/{$locale}");
            $this->assertEquals($locale, session('locale'));
        }
    }

    public function test_sets_locale_on_fresh_session()
    {
        session()->flush();

        $this->get('/locale/uk')
            ->assertRedirect();

        $this->assertEquals('uk', session('locale'));
    }

    public function test_handles_edge_cases()
    {
        session(['locale' => 'en']);

        $edgeCases = ['invalid', 'EN', '123', 'en-US'];

        foreach ($edgeCases as $locale) {
            $this->get("/locale/{$locale}")
                ->assertRedirect();

            $this->assertEquals('en', session('locale'));
        }
    }

    public function test_controller_direct_security()
    {
        // Use minimal config for security testing
        config([
            'app.available_locales' => [
                'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
            ],
        ]);
        session(['locale' => 'en']);

        $controller = new LocaleController;
        $dangerousInputs = [
            "<script>alert('xss')</script>",
            "'; DROP TABLE users; --",
            str_repeat('a', 10000),
        ];

        foreach ($dangerousInputs as $input) {
            $response = $controller->switch($input);

            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertEquals('en', session('locale'));
        }
    }

    public function test_url_safe_security_scenarios()
    {
        session(['locale' => 'en']);

        $scenarios = [
            str_repeat('x', 100),
            'en-US-FAKE-123',
            'ен', // Cyrillic that looks like 'en'
            'EN',
            '123-456',
        ];

        foreach ($scenarios as $scenario) {
            $response = $this->get("/locale/{$scenario}");

            $response->assertRedirect();
            $this->assertEquals('en', session('locale'));
        }
    }
}
