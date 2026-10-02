<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Enum\PropertySort;
use App\Enum\PropertyType;
use App\Mcp\GetMarketOverviewTool;
use App\Mcp\GetPropertyTool;
use App\Mcp\ListFilterOptionsTool;
use App\Mcp\SearchPropertiesTool;
use App\Tests\SeededDatabaseTrait;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class McpToolsTest extends KernelTestCase
{
    use SeededDatabaseTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        self::seedDatabase();
    }

    public function testSearchPropertiesAnswersTheChallengeExample(): void
    {
        $result = $this->searchTool()(city: 'berlin', maxPrice: 500000, minBedrooms: 3, sort: PropertySort::PriceAsc);

        self::assertSame(4, $result['total']);
        self::assertSame(4, $result['returned']);
        self::assertSame([329000, 459000, 489000, 495000], array_column($result['properties'], 'price'));
        self::assertStringEndsWith('/properties/'.$result['properties'][0]['id'], $result['properties'][0]['url']);
        self::assertArrayNotHasKey('note', $result);
    }

    public function testSearchPropertiesHonoursLimitAndExplainsTruncation(): void
    {
        $result = $this->searchTool()(limit: 3);

        self::assertSame(27, $result['total']);
        self::assertSame(3, $result['returned']);
        self::assertStringContainsString('first 3 of 27', $result['note']);
    }

    public function testSearchPropertiesExplainsEmptyResults(): void
    {
        $result = $this->searchTool()(city: 'Paris');

        self::assertSame(0, $result['total']);
        self::assertStringContainsString('list_filter_options', $result['note']);
    }

    public function testSearchPropertiesReportsInvalidFiltersAsToolError(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('maxPrice: The maximum price must be greater than or equal to the minimum price.');

        $this->searchTool()(minPrice: 900000, maxPrice: 100000);
    }

    public function testGetPropertyReturnsDetails(): void
    {
        $result = self::getContainer()->get(GetPropertyTool::class)(id: 2);

        self::assertSame(2, $result['id']);
        self::assertSame('house', $result['type']);
        self::assertNotEmpty($result['description']);
        self::assertStringEndsWith('/properties/2', $result['url']);
        // 1,250,000 EUR / 180 m² = 6,944 EUR/m², 13% above the Berlin average of 6,147 EUR/m².
        self::assertSame(13, $result['market']['differencePercent']);
    }

    public function testGetMarketOverviewForAllTypes(): void
    {
        $result = self::getContainer()->get(GetMarketOverviewTool::class)();

        self::assertNull($result['type']);
        self::assertSame(['Berlin', 'Cologne', 'Frankfurt', 'Hamburg', 'Munich'], array_column($result['cities'], 'city'));
        self::assertSame(6147, $result['cities'][0]['averagePricePerSquareMetre']);
    }

    public function testGetMarketOverviewForOneType(): void
    {
        $result = self::getContainer()->get(GetMarketOverviewTool::class)(type: PropertyType::House);

        // Only cities with at least one house; Berlin's only house is 1,250,000 EUR / 180 m².
        self::assertSame('house', $result['type']);
        self::assertSame(['Berlin', 'Cologne', 'Hamburg', 'Munich'], array_column($result['cities'], 'city'));
        self::assertSame(6944, $result['cities'][0]['averagePricePerSquareMetre']);
    }

    public function testGetPropertyReportsUnknownIdAsToolError(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Property with id 999 was not found.');

        self::getContainer()->get(GetPropertyTool::class)(id: 999);
    }

    public function testListFilterOptions(): void
    {
        $result = self::getContainer()->get(ListFilterOptionsTool::class)();

        self::assertContains('Berlin', array_column($result['cities'], 'name'));
        self::assertSame(PropertySort::values(), $result['sortOptions']);
    }

    private function searchTool(): SearchPropertiesTool
    {
        return self::getContainer()->get(SearchPropertiesTool::class);
    }
}
