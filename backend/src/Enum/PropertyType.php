<?php

declare(strict_types=1);

namespace App\Enum;

enum PropertyType: string
{
    case Apartment = 'apartment';
    case House = 'house';
    case Studio = 'studio';
    case Penthouse = 'penthouse';
    case Townhouse = 'townhouse';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
