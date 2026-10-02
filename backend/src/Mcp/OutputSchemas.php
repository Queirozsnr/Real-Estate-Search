<?php

declare(strict_types=1);

namespace App\Mcp;

/**
 * JSON Schemas of the tools' structuredContent (MCP "outputSchema").
 *
 * They mirror App\View\PropertySummary / PropertyDetails plus the frontend `url`;
 * McpHttpEndpointTest validates real tool results against them so they cannot drift.
 */
final class OutputSchemas
{
    private const array NULLABLE_STRING = ['anyOf' => [['type' => 'string'], ['type' => 'null']]];
    private const array NULLABLE_INTEGER = ['anyOf' => [['type' => 'integer'], ['type' => 'null']]];

    private const array PROPERTY_SUMMARY_FIELDS = [
        'id' => ['type' => 'integer'],
        'title' => ['type' => 'string'],
        'type' => ['type' => 'string', 'description' => 'apartment, house, studio, penthouse or townhouse'],
        'city' => ['type' => 'string'],
        'district' => ['type' => 'string'],
        'price' => ['type' => 'integer', 'description' => 'Purchase price in EUR'],
        'pricePerSquareMetre' => ['type' => 'integer', 'description' => 'EUR per m² of living area'],
        'bedrooms' => ['type' => 'integer', 'description' => '0 for studios'],
        'bathrooms' => ['type' => 'integer'],
        'livingArea' => ['type' => 'integer', 'description' => 'Living area in m²'],
        'listedAt' => ['type' => 'string', 'format' => 'date'],
        'url' => ['type' => 'string', 'description' => 'Link to the property page in the web app'],
    ];

    public const array SEARCH_PROPERTIES = [
        'type' => 'object',
        'properties' => [
            'total' => ['type' => 'integer', 'description' => 'Number of properties matching the filters'],
            'returned' => ['type' => 'integer', 'description' => 'Number of properties included in this response (at most "limit")'],
            'filters' => [
                'type' => 'object',
                'description' => 'The filters that were applied',
                'properties' => [
                    'city' => ['type' => 'string'],
                    'minPrice' => ['type' => 'integer'],
                    'maxPrice' => ['type' => 'integer'],
                    'minBedrooms' => ['type' => 'integer'],
                    'type' => ['type' => 'string'],
                ],
                'additionalProperties' => false,
            ],
            'sort' => ['type' => 'string'],
            'properties' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => self::PROPERTY_SUMMARY_FIELDS + ['imageUrl' => self::NULLABLE_STRING],
                    'required' => ['id', 'title', 'type', 'city', 'district', 'price', 'pricePerSquareMetre', 'bedrooms', 'bathrooms', 'livingArea', 'imageUrl', 'listedAt', 'url'],
                ],
            ],
            'note' => ['type' => 'string', 'description' => 'Hint when results are truncated or empty'],
        ],
        'required' => ['total', 'returned', 'filters', 'sort', 'properties'],
    ];

    public const array LIST_FILTER_OPTIONS = [
        'type' => 'object',
        'properties' => [
            'cities' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => ['name' => ['type' => 'string'], 'count' => ['type' => 'integer']],
                    'required' => ['name', 'count'],
                ],
            ],
            'types' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => ['value' => ['type' => 'string'], 'label' => ['type' => 'string'], 'count' => ['type' => 'integer']],
                    'required' => ['value', 'label', 'count'],
                ],
            ],
            'minPrice' => ['type' => 'integer', 'description' => 'Lowest listing price in EUR'],
            'maxPrice' => ['type' => 'integer', 'description' => 'Highest listing price in EUR'],
            'maxBedrooms' => ['type' => 'integer'],
            'sortOptions' => ['type' => 'array', 'items' => ['type' => 'string']],
        ],
        'required' => ['cities', 'types', 'minPrice', 'maxPrice', 'maxBedrooms', 'sortOptions'],
    ];

    public const array GET_PROPERTY = [
        'type' => 'object',
        'properties' => self::PROPERTY_SUMMARY_FIELDS + [
            'description' => ['type' => 'string'],
            'address' => ['type' => 'string'],
            'yearBuilt' => self::NULLABLE_INTEGER,
            'features' => ['type' => 'array', 'items' => ['type' => 'string']],
            'images' => ['type' => 'array', 'items' => ['type' => 'string']],
            'location' => [
                'type' => 'object',
                'properties' => ['latitude' => ['type' => 'number'], 'longitude' => ['type' => 'number']],
                'required' => ['latitude', 'longitude'],
            ],
            'market' => [
                'type' => 'object',
                'description' => 'Price per m² compared with the average of the city',
                'properties' => [
                    'city' => ['type' => 'string'],
                    'listings' => ['type' => 'integer', 'description' => 'Listings in the city used for the average'],
                    'averagePricePerSquareMetre' => ['type' => 'integer', 'description' => 'City average in EUR per m² (total price / total living area)'],
                    'differencePercent' => ['type' => 'integer', 'description' => 'Negative: cheaper per m² than the city average'],
                ],
                'required' => ['city', 'listings', 'averagePricePerSquareMetre', 'differencePercent'],
            ],
        ],
        'required' => ['id', 'title', 'description', 'type', 'city', 'district', 'address', 'price', 'pricePerSquareMetre', 'bedrooms', 'bathrooms', 'livingArea', 'yearBuilt', 'features', 'images', 'location', 'market', 'listedAt', 'url'],
    ];

    public const array GET_MARKET_OVERVIEW = [
        'type' => 'object',
        'properties' => [
            'type' => self::NULLABLE_STRING + ['description' => 'Property type the statistics are restricted to, null for all types'],
            'cities' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'city' => ['type' => 'string'],
                        'listings' => ['type' => 'integer'],
                        'averagePricePerSquareMetre' => ['type' => 'integer', 'description' => 'EUR per m² (total price / total living area)'],
                        'averagePrice' => ['type' => 'integer', 'description' => 'EUR'],
                        'minPrice' => ['type' => 'integer', 'description' => 'EUR'],
                        'maxPrice' => ['type' => 'integer', 'description' => 'EUR'],
                    ],
                    'required' => ['city', 'listings', 'averagePricePerSquareMetre', 'averagePrice', 'minPrice', 'maxPrice'],
                ],
            ],
        ],
        'required' => ['type', 'cities'],
    ];
}
