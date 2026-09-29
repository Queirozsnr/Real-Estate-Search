<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\SeededDatabaseTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PropertyApiTest extends WebTestCase
{
    use SeededDatabaseTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::seedDatabase();
    }

    public function testListsPropertiesPaginatedAndNewestFirstByDefault(): void
    {
        $body = $this->getJson('/api/properties');

        self::assertResponseIsSuccessful();
        self::assertSame(['total' => 27, 'page' => 1, 'perPage' => 12, 'totalPages' => 3, 'filters' => [], 'sort' => 'newest'], $body['meta']);
        self::assertCount(12, $body['data']);

        $dates = array_column($body['data'], 'listedAt');
        $sorted = $dates;
        rsort($sorted);
        self::assertSame($sorted, $dates);
    }

    public function testChallengeExampleBerlinWithAtLeastThreeBedroomsUpTo500k(): void
    {
        $body = $this->getJson('/api/properties?city=Berlin&minBedrooms=3&maxPrice=500000&sort=price_asc');

        self::assertResponseIsSuccessful();
        self::assertSame(4, $body['meta']['total']);
        self::assertSame(['city' => 'Berlin', 'maxPrice' => 500000, 'minBedrooms' => 3], $body['meta']['filters']);
        self::assertSame([329000, 459000, 489000, 495000], array_column($body['data'], 'price'));

        foreach ($body['data'] as $property) {
            self::assertSame('Berlin', $property['city']);
            self::assertGreaterThanOrEqual(3, $property['bedrooms']);
            self::assertLessThanOrEqual(500000, $property['price']);
        }
    }

    public function testCityFilterIsCaseInsensitiveAndTrimmed(): void
    {
        $body = $this->getJson('/api/properties?city=%20hAmBuRg%20');

        self::assertSame(5, $body['meta']['total']);
        self::assertSame(['Hamburg'], array_values(array_unique(array_column($body['data'], 'city'))));
    }

    public function testFiltersByTypeAndPriceRangeSortedByPriceDescending(): void
    {
        $body = $this->getJson('/api/properties?type=apartment&minPrice=400000&maxPrice=700000&sort=price_desc');

        $prices = array_column($body['data'], 'price');
        self::assertNotEmpty($prices);
        self::assertSame(['apartment'], array_values(array_unique(array_column($body['data'], 'type'))));
        self::assertSame($prices, (static function (array $p): array { rsort($p); return $p; })($prices));
        self::assertGreaterThanOrEqual(400000, min($prices));
        self::assertLessThanOrEqual(700000, max($prices));
    }

    public function testPaginatesResults(): void
    {
        $body = $this->getJson('/api/properties?perPage=5&page=6');

        self::assertCount(2, $body['data']);
        self::assertSame(['total' => 27, 'page' => 6, 'perPage' => 5, 'totalPages' => 6], array_slice($body['meta'], 0, 4));
    }

    public function testReturnsAnEmptyListWhenNothingMatches(): void
    {
        $body = $this->getJson('/api/properties?city=Paris');

        self::assertResponseIsSuccessful();
        self::assertSame([], $body['data']);
        self::assertSame(0, $body['meta']['total']);
    }

    /**
     * @param list<string> $expectedFields
     */
    #[DataProvider('invalidQueries')]
    public function testRejectsInvalidFiltersWithProblemDetails(string $query, array $expectedFields): void
    {
        $body = $this->getJson('/api/properties?'.$query);

        self::assertResponseStatusCodeSame(422);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        self::assertSame(422, $body['status']);
        self::assertSame($expectedFields, array_column($body['violations'], 'field'));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function invalidQueries(): iterable
    {
        yield 'min price above max price' => ['minPrice=500000&maxPrice=100000', ['maxPrice']];
        yield 'negative price' => ['minPrice=-1', ['minPrice']];
        yield 'unknown type' => ['type=castle', ['type']];
        yield 'unknown sort' => ['sort=cheapest', ['sort']];
        yield 'page size too large' => ['perPage=500', ['perPage']];
        yield 'page zero' => ['page=0', ['page']];
        yield 'non numeric bedrooms' => ['minBedrooms=three', ['minBedrooms']];
    }

    public function testShowsPropertyDetails(): void
    {
        $body = $this->getJson('/api/properties/1');

        self::assertResponseIsSuccessful();
        self::assertSame('Bright family apartment near Mauerpark', $body['data']['title']);
        self::assertSame(5315, $body['data']['pricePerSquareMetre']); // 489000 / 92
        self::assertCount(3, $body['data']['images']);
        self::assertArrayHasKey('latitude', $body['data']['location']);
    }

    public function testUnknownPropertyReturnsNotFoundProblem(): void
    {
        $body = $this->getJson('/api/properties/999');

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
        self::assertSame('Property with id 999 was not found.', $body['detail']);
    }

    public function testExposesFacetsForBuildingSearchForms(): void
    {
        $body = $this->getJson('/api/properties/facets');

        self::assertResponseIsSuccessful();
        self::assertSame(['Berlin', 'Cologne', 'Frankfurt', 'Hamburg', 'Munich'], array_column($body['cities'], 'name'));
        self::assertSame(27, array_sum(array_column($body['types'], 'count')));
        self::assertSame(189000, $body['minPrice']);
        self::assertSame(2900000, $body['maxPrice']);
        self::assertSame(6, $body['maxBedrooms']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getJson(string $uri): array
    {
        $this->client->request('GET', $uri);

        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }
}
