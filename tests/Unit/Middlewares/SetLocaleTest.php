<?php

namespace Tests\Unit\Middlewares;

use App\Http\Middlewares\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    protected SetLocale $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new SetLocale;

        Cache::forget('app.available_locales');

        config([
            'app.available_locales' => [
                'en' => ['name' => 'English'],
                'uk' => ['name' => 'Ukrainian'],
                'pl' => ['name' => 'Polish'],
            ],
            'app.fallback_locale' => 'en',
        ]);
    }

    public function test_uses_existing_session_locale()
    {
        session(['locale' => 'uk']);

        $request = Request::create('/test');
        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('uk', app()->getLocale());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_detects_browser_preferences()
    {
        $testCases = [
            ['uk-UA,uk;q=0.9,en;q=0.8', 'uk'],
            ['pl;q=0.9,en;q=0.8', 'pl'],
            ['de-DE,de;q=0.9', 'en'], // Fallback to default
            ['es-ES,es;q=0.9,pl;q=0.8,en;q=0.7', 'pl'], // Best available match
        ];

        foreach ($testCases as [$acceptLanguage, $expectedLocale]) {
            session()->flush();

            $request = Request::create('/test');
            $request->headers->set('Accept-Language', $acceptLanguage);

            $response = $this->middleware->handle($request, fn ($req) => response('OK'));

            $this->assertEquals($expectedLocale, app()->getLocale());
            $this->assertEquals($expectedLocale, session('locale'));
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function test_handles_invalid_session_locale()
    {
        session(['locale' => 'fr']); // Not in available locales

        $request = Request::create('/test');
        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', session('locale'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_caching_behavior_in_production()
    {
        app()->detectEnvironment(fn () => 'production');
        session(['locale' => 'uk']);

        $request = Request::create('/test');
        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(['en', 'uk', 'pl'], Cache::get('app.available_locales'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_no_caching_in_development()
    {
        app()->detectEnvironment(fn () => 'local');
        session(['locale' => 'en']);

        $request = Request::create('/test');
        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertNull(Cache::get('app.available_locales'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_chain_continues_properly()
    {
        session(['locale' => 'en']);

        $request = Request::create('/test');
        $response = $this->middleware->handle($request, fn ($req) => response('Custom Response', 201));

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Custom Response', $response->getContent());
    }

    public function test_handles_edge_cases()
    {
        // Test empty configuration
        config(['app.available_locales' => []]);

        $request = Request::create('/test');
        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals('en', session('locale'));
        $this->assertEquals(200, $response->getStatusCode());

        // Reset config and test session update after detection
        config([
            'app.available_locales' => [
                'en' => ['name' => 'English'],
                'uk' => ['name' => 'Ukrainian'],
            ],
        ]);
        session()->flush();
        $this->assertNull(session('locale'));

        $request = Request::create('/test');
        $request->headers->set('Accept-Language', 'uk');
        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('uk', session('locale'));
        $this->assertEquals(200, $response->getStatusCode());
    }
}
