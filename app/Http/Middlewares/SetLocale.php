<?php

namespace App\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        if (session()->has('locale')) {
            $locale = session('locale');
        } else {
            $locale = $this->detectPreferredLocale($request);
            session(['locale' => $locale]);
        }

        if ($this->isLocaleAvailable($locale)) {
            App::setLocale($locale);
        } else {
            $fallbackLocale = config('app.fallback_locale');
            App::setLocale($fallbackLocale);
            session(['locale' => $fallbackLocale]);
        }

        return $next($request);
    }

    private function detectPreferredLocale(Request $request): string
    {
        $availableLocaleCodes = $this->getAvailableLocales();
        $preferredLanguage = $request->getPreferredLanguage($availableLocaleCodes);

        return $preferredLanguage ?: config('app.fallback_locale');
    }

    private function isLocaleAvailable(string $locale): bool
    {
        $availableLocales = $this->getAvailableLocales();

        return in_array($locale, $availableLocales);
    }

    private function getAvailableLocales(): array
    {
        if (app()->environment('production')) {
            return Cache::remember('app.available_locales', 3600, function () {
                return array_keys(config('app.available_locales'));
            });
        }

        return array_keys(config('app.available_locales'));
    }
}
