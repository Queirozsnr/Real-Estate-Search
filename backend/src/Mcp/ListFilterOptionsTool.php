<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Enum\PropertySort;
use App\Search\PropertyCatalog;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;

/**
 * Lets the model discover valid filter values (e.g. that the dataset uses "Munich"
 * rather than "München") instead of guessing.
 */
final readonly class ListFilterOptionsTool
{
    public function __construct(
        private PropertyCatalog $catalog,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'list_filter_options',
        title: 'List filter options',
        description: 'List the values accepted by search_properties: available cities and property types (with number of listings), the price range in EUR, the maximum number of bedrooms and the sort options.',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(): array
    {
        return [
            ...get_object_vars($this->catalog->facets()),
            'sortOptions' => PropertySort::values(),
        ];
    }
}
