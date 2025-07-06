<?php

namespace App\Http\Controllers;

class LocaleController extends Controller
{
    public function switch(string $locale)
    {
        // Get available locale codes (e.g., 'en', 'uk', 'pl') from config
        $availableLocales = array_keys(config('app.available_locales'));

        // Validate locale against available options and save to session
        if (in_array($locale, $availableLocales)) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    }
}
