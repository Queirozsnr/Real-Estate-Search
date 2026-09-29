<?php

declare(strict_types=1);

namespace App\Search;

final readonly class SearchFacets
{
    /**
     * @param list<array{name: string, count: int}>                  $cities
     * @param list<array{value: string, label: string, count: int}> $types
     */
    public function __construct(
        public array $cities,
        public array $types,
        public int $minPrice,
        public int $maxPrice,
        public int $maxBedrooms,
    ) {
    }
}
