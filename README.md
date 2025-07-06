<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Localization
This application supports multiple languages configured in `config/app.php`. The system automatically detects user language preferences from browser settings and allows manual switching via the header language selector. Language preferences persist across sessions.

### Translation Management
The project uses Laravel's standard translation system with files in the `lang/` directory. Translations use phrases as keys (like `__('Login')`) stored in JSON files, while Laravel system messages use structured PHP files.

**Adding new translatable content:**
```
{{ __('Your translatable text here') }}
```

**Updating translation files:**
```bash
  # Scan code and update all language files with new translation keys
  php artisan translations:generate
```

The command scans codebase for translation functions, finds new keys, and adds them to all configured language files without overwriting existing translations. New keys appear with empty values for translation.
