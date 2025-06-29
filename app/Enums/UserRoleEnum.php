<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;

enum UserRoleEnum: string implements HasColor
{
    case ADMINISTRATOR = 'administrator';
    case MANAGER = 'manager';
    case USER = 'user';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ADMINISTRATOR => 'danger',
            self::MANAGER => 'primary',
            self::USER => 'gray',
        };
    }

    public static function getColorForRole(string $roleName): string
    {
        $enum = self::tryFrom($roleName);

        return $enum?->getColor() ?? 'info';
    }
}
