<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Enum\PropertySort;
use App\Enum\PropertyType;
use App\Search\PropertyCatalog;
use App\Search\PropertySearchCriteria;
use App\View\PropertySummary;
use App\View\PropertyUrlGenerator;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Arguments use the same names as the REST query parameters and go through the same
 * PropertySearchCriteria (validation) and PropertyCatalog (query) as the HTTP API.
 */
final readonly class SearchPropertiesTool
{
    public const int DEFAULT_LIMIT = 10;

    public function __construct(
        private PropertyCatalog $catalog,
        private ValidatorInterface $validator,
        private PropertyUrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @return array{total: int, returned: int, filters: object, sort: string, properties: list<array<string, mixed>>, note?: string}
     */
    #[McpTool(
        name: 'search_properties',
        title: 'Search properties',
        description: <<<'TXT'
            Search real estate listings (purchase prices in EUR). All filters are optional and combined with AND.
            Example: "properties in Berlin with at least 3 bedrooms and a maximum price of €500,000"
            → {"city": "Berlin", "minBedrooms": 3, "maxPrice": 500000}.
            Returns the total number of matches and up to `limit` matching properties (summary data and a link).
            Use get_property to fetch the full details of a result.
            TXT,
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(
        #[Schema(description: 'City name, case-insensitive (e.g. "Berlin"). Call list_filter_options to see the available cities.', maxLength: 100)]
        ?string $city = null,
        #[Schema(description: 'Minimum purchase price in EUR.', minimum: 0)]
        ?int $minPrice = null,
        #[Schema(description: 'Maximum purchase price in EUR.', minimum: 0)]
        ?int $maxPrice = null,
        #[Schema(description: 'Minimum number of bedrooms (studios have 0).', minimum: 0, maximum: 20)]
        ?int $minBedrooms = null,
        #[Schema(description: 'Property type.')]
        ?PropertyType $type = null,
        #[Schema(description: 'Sort order of the results.')]
        PropertySort $sort = PropertySort::Newest,
        #[Schema(description: 'Maximum number of properties to return.', minimum: 1, maximum: PropertySearchCriteria::MAX_PER_PAGE)]
        int $limit = self::DEFAULT_LIMIT,
    ): array {
        $criteria = new PropertySearchCriteria(
            city: $city,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            minBedrooms: $minBedrooms,
            type: $type?->value,
            sort: $sort->value,
            perPage: $limit,
        );

        $this->assertValid($criteria);

        $result = $this->catalog->search($criteria);
        $properties = array_map(
            fn ($property): array => [
                ...get_object_vars(PropertySummary::fromEntity($property)),
                'url' => $this->urlGenerator->propertyUrl((int) $property->getId()),
            ],
            $result->items,
        );

        $response = [
            'total' => $result->total,
            'returned' => \count($properties),
            'filters' => (object) $criteria->activeFilters(),
            'sort' => $criteria->sort,
            'properties' => $properties,
        ];

        if ($result->total > \count($properties)) {
            $response['note'] = \sprintf('Showing the first %d of %d matches. Narrow the filters or raise "limit" to see more.', \count($properties), $result->total);
        } elseif (0 === $result->total) {
            $response['note'] = 'No property matches these filters. Try relaxing them, or call list_filter_options to check available cities and types.';
        }

        return $response;
    }

    private function assertValid(PropertySearchCriteria $criteria): void
    {
        $violations = $this->validator->validate($criteria);

        if (0 === \count($violations)) {
            return;
        }

        $messages = [];
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $field = 'perPage' === $violation->getPropertyPath() ? 'limit' : $violation->getPropertyPath();
            $messages[] = \sprintf('%s: %s', $field, $violation->getMessage());
        }

        throw new ToolCallException('Invalid search filters. '.implode(' ', $messages));
    }
}
