<?php

declare(strict_types=1);

namespace App\Search;

use App\Entity\Property;

/**
 * How the price per m² of a property compares with the average of its city.
 */
final readonly class MarketComparison
{
    public function __construct(
        public string $city,
        public int $listings,
        public float $cityAveragePricePerSquareMetre,
        public float $propertyPricePerSquareMetre,
    ) {
    }

    public static function of(Property $property, CityMarketStats $cityStats): self
    {
        return new self(
            city: $cityStats->city,
            listings: $cityStats->listings,
            cityAveragePricePerSquareMetre: $cityStats->averagePricePerSquareMetre(),
            propertyPricePerSquareMetre: $property->getPrice() / max(1, $property->getLivingArea()),
        );
    }

    /**
     * Negative when the property is cheaper per m² than the city average.
     */
    public function differencePercent(): int
    {
        return (int) round(($this->propertyPricePerSquareMetre - $this->cityAveragePricePerSquareMetre) / $this->cityAveragePricePerSquareMetre * 100);
    }
}
