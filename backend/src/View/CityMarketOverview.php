<?php

declare(strict_types=1);

namespace App\View;

use App\Search\CityMarketStats;

final readonly class CityMarketOverview
{
    public function __construct(
        public string $city,
        public int $listings,
        public int $averagePricePerSquareMetre,
        public int $averagePrice,
        public int $minPrice,
        public int $maxPrice,
    ) {
    }

    public static function fromStats(CityMarketStats $stats): self
    {
        return new self(
            city: $stats->city,
            listings: $stats->listings,
            averagePricePerSquareMetre: (int) round($stats->averagePricePerSquareMetre()),
            averagePrice: (int) round($stats->averagePrice()),
            minPrice: $stats->minPrice,
            maxPrice: $stats->maxPrice,
        );
    }
}
