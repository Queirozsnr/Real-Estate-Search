<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Search\PropertyCatalog;
use App\Search\PropertyNotFoundException;
use App\View\PropertyDetails;
use App\View\PropertyUrlGenerator;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

final readonly class GetPropertyTool
{
    public function __construct(
        private PropertyCatalog $catalog,
        private PropertyUrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'get_property',
        title: 'Get property details',
        description: 'Get the full details of a single property (description, address, features, images, location, price per m²) by its id, as returned by search_properties. Includes "market": how its price per m² compares with the average of its city (negative differencePercent = cheaper than average).',
        annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
        outputSchema: OutputSchemas::GET_PROPERTY,
    )]
    public function __invoke(
        #[Schema(description: 'The property id.', minimum: 1)]
        int $id,
    ): array {
        try {
            $property = $this->catalog->get($id);
        } catch (PropertyNotFoundException $e) {
            // Reported to the client as a tool error (isError: true), so the model can recover.
            throw new ToolCallException($e->getMessage().' Use search_properties to find valid ids.', previous: $e);
        }

        return [
            ...get_object_vars(PropertyDetails::fromEntity($property, $this->catalog->marketComparison($property))),
            'url' => $this->urlGenerator->propertyUrl($id),
        ];
    }
}
