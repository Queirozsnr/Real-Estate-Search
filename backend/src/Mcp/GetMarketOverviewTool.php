<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Enum\PropertyType;
use App\Search\PropertyCatalog;
use App\View\CityMarketOverview;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

/**
 * Answers market questions the listing tools cannot ("Which city is cheapest per m²?",
 * "Is €5,300/m² a good price in Berlin?") from the same data as the search.
 */
final readonly class GetMarketOverviewTool
{
    public function __construct(
        private PropertyCatalog $catalog,
    ) {
    }

    /**
     * @return array{type: string|null, cities: list<array<string, int|string>>}
     */
    #[McpTool(
        name: 'get_market_overview',
        title: 'Get market overview',
        description: <<<'TXT'
            Price statistics per city (purchase prices in EUR): number of listings, average price per m²,
            average price and price range. Optionally restricted to one property type.
            Use it to compare cities or to judge whether a property's price is high or low for its city
            (get_property also returns that comparison for a single property).
            TXT,
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
        outputSchema: OutputSchemas::GET_MARKET_OVERVIEW,
    )]
    public function __invoke(
        #[Schema(definition: [
            'description' => 'Only include properties of this type. Omit for all types.',
            'anyOf' => [
                ['type' => 'string', 'enum' => [PropertyType::Apartment->value, PropertyType::House->value, PropertyType::Studio->value, PropertyType::Penthouse->value, PropertyType::Townhouse->value]],
                ['type' => 'null'],
            ],
        ])]
        ?PropertyType $type = null,
    ): array {
        return [
            'type' => $type?->value,
            'cities' => array_map(
                static fn ($stats): array => get_object_vars(CityMarketOverview::fromStats($stats)),
                $this->catalog->marketOverview($type),
            ),
        ];
    }
}
