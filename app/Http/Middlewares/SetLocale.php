<?php

namespace App\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $request->session()->get('language') ?? config('app.locale', 'en');
        $availableLocales = collect(config('translation-manager.available_locales'))->pluck('code')->toArray();

        if (!in_array($locale, $availableLocales, true)) {
            $locale = config('app.locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
