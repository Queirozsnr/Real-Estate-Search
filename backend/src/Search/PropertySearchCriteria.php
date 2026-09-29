<?php

declare(strict_types=1);

namespace App\Search;

use App\Enum\PropertySort;
use App\Enum\PropertyType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Search filters shared by every entry point (REST API and MCP tools).
 *
 * The validation rules live here, so both the HTTP layer (via #[MapQueryString])
 * and the MCP layer validate input against exactly the same constraints.
 * All filters are optional and combined with AND.
 *
 * Enum-backed filters (type, sort) are kept as raw strings and validated with Choice,
 * so clients get an explicit list of allowed values instead of a type error.
 */
final readonly class PropertySearchCriteria
{
    public const int DEFAULT_PER_PAGE = 12;
    public const int MAX_PER_PAGE = 50;

    public function __construct(
        #[Assert\Length(max: 100)]
        public ?string $city = null,

        #[Assert\PositiveOrZero]
        public ?int $minPrice = null,

        #[Assert\PositiveOrZero]
        #[Assert\GreaterThanOrEqual(propertyPath: 'minPrice', message: 'The maximum price must be greater than or equal to the minimum price.')]
        public ?int $maxPrice = null,

        #[Assert\Range(min: 0, max: 20)]
        public ?int $minBedrooms = null,

        #[Assert\Choice(callback: [PropertyType::class, 'values'], message: 'Unknown property type {{ value }}. Allowed values: {{ choices }}.')]
        public ?string $type = null,

        #[Assert\Choice(callback: [PropertySort::class, 'values'], message: 'Unknown sort {{ value }}. Allowed values: {{ choices }}.')]
        public string $sort = PropertySort::Newest->value,

        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Range(min: 1, max: self::MAX_PER_PAGE)]
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {
    }

    public function normalizedCity(): ?string
    {
        $city = null === $this->city ? '' : trim($this->city);

        return '' === $city ? null : $city;
    }

    public function propertyType(): ?PropertyType
    {
        return null === $this->type ? null : PropertyType::from($this->type);
    }

    public function sortOrder(): PropertySort
    {
        return PropertySort::from($this->sort);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /**
     * Only the filters that are actually set, useful to echo back what was applied.
     *
     * @return array<string, int|string>
     */
    public function activeFilters(): array
    {
        return array_filter([
            'city' => $this->normalizedCity(),
            'minPrice' => $this->minPrice,
            'maxPrice' => $this->maxPrice,
            'minBedrooms' => $this->minBedrooms,
            'type' => $this->type,
        ], static fn (int|string|null $value): bool => null !== $value);
    }
}
