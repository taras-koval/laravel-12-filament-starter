<?php

namespace App\Http\Controllers;

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class TestController extends Controller
{
    public function index()
    {
        $info = [
            'current_locale' => app()->getLocale(),
            'available_locales' => LaravelLocalization::getSupportedLocales(),
            'current_url' => request()->url(),
            'localized_urls' => [],
        ];

        // Generate localized URLs for each supported locale
        foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties) {
            $info['localized_urls'][$locale] = LaravelLocalization::getLocalizedURL($locale);
        }

        return response()->json($info);
    }
}
