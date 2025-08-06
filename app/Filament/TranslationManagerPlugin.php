<?php

namespace App\Filament;

use App\Http\Middlewares\SetLocale;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\View\View;

class TranslationManagerPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'translation-manager';
    }

    public function register(Panel $panel): void
    {
        if (config('translation-manager.language_switcher')) {
            $panel->renderHook(
                name: config('translation-manager.language_switcher_render_hook'),
                hook: function (): View {
                    $locales = config('translation-manager.available_locales');
                    $currentLocale = app()->getLocale();
                    $currentLanguage = collect($locales)->firstWhere('code', $currentLocale);
                    $otherLanguages = $locales;
                    $showFlags = config('translation-manager.show_flags');

                    return view('language-switcher', compact(
                        'otherLanguages',
                        'currentLanguage',
                        'showFlags',
                    ));
                }
            );

            $panel->middleware([
                SetLocale::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
