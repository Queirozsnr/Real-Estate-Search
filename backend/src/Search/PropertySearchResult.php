<?php

declare(strict_types=1);

namespace App\Search;

use App\Entity\Property;

final readonly class PropertySearchResult
{
    /**
     * @param list<Property> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }

    public function totalPages(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }
}
