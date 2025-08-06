<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'group',
        'values',
    ];

    protected $casts = [
        'values' => 'array',
    ];

    protected function values(): Attribute
    {
        return Attribute::make(
            get: static fn($value) => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            set: static fn($value) => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );
    }

    public static function getAllByLocale(string $locale): array
    {
        return self::all()
            ->filter(fn($t) => isset($t->values[$locale]))
            ->groupBy('group')
            ->mapWithKeys(function ($items, $group) use ($locale) {
                return [$group => $items->pluck("values.$locale", 'key')->toArray()];
            })
            ->toArray();
    }
}
