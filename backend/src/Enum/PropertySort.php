<?php

declare(strict_types=1);

namespace App\Enum;

enum PropertySort: string
{
    case Newest = 'newest';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';
    case AreaDesc = 'area_desc';

    /**
     * @return array{0: string, 1: 'ASC'|'DESC'} Entity field and direction
     */
    public function orderBy(): array
    {
        return match ($this) {
            self::Newest => ['listedAt', 'DESC'],
            self::PriceAsc => ['price', 'ASC'],
            self::PriceDesc => ['price', 'DESC'],
            self::AreaDesc => ['livingArea', 'DESC'],
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
