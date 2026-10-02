<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Aggregated listing data of one city (optionally restricted to one property type).
 */
final readonly class CityMarketStats
{
    public function __construct(
        public string $city,
        public int $listings,
        public int $totalPrice,
        public int $totalLivingArea,
        public int $minPrice,
        public int $maxPrice,
    ) {
    }

    /**
     * Total price divided by total living area: an area-weighted average, so a small
     * studio does not weigh as much as a large house (the usual market measure).
     */
    public function averagePricePerSquareMetre(): float
    {
        return $this->totalPrice / max(1, $this->totalLivingArea);
    }

    public function averagePrice(): float
    {
        return $this->totalPrice / max(1, $this->listings);
    }
}
