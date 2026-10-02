<?php

declare(strict_types=1);

namespace App\Search;

use App\Entity\Property;
use App\Enum\PropertyType;
use App\Repository\PropertyRepository;

/**
 * Application service for reading properties.
 *
 * It is the single entry point used by both the REST controller and the MCP tools,
 * which guarantees that the UI and an AI client get exactly the same search behaviour.
 */
final readonly class PropertyCatalog
{
    public function __construct(
        private PropertyRepository $repository,
    ) {
    }

    public function search(PropertySearchCriteria $criteria): PropertySearchResult
    {
        return $this->repository->search($criteria);
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function get(int $id): Property
    {
        return $this->repository->find($id) ?? throw PropertyNotFoundException::withId($id);
    }

    public function facets(): SearchFacets
    {
        return $this->repository->facets();
    }

    /**
     * @return list<CityMarketStats>
     */
    public function marketOverview(?PropertyType $type = null): array
    {
        return $this->repository->cityMarketStats($type);
    }

    public function marketComparison(Property $property): MarketComparison
    {
        // The property itself is part of its city, so the stats always exist.
        [$cityStats] = $this->repository->cityMarketStats(city: $property->getCity());

        return MarketComparison::of($property, $cityStats);
    }
}
