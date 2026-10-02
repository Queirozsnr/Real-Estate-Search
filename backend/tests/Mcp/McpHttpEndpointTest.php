<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Enum\PropertyType;
use App\Tests\SeededDatabaseTrait;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * End-to-end check of the MCP server over the Streamable HTTP transport (/mcp),
 * going through the JSON-RPC handshake exactly like a real MCP client.
 */
final class McpHttpEndpointTest extends WebTestCase
{
    use SeededDatabaseTrait;

    private const string PROTOCOL_VERSION = '2025-06-18';

    private KernelBrowser $client;
    private string $sessionId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::seedDatabase();

        $initialize = $this->rpc('initialize', [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => new \stdClass(),
            'clientInfo' => ['name' => 'phpunit', 'version' => '1.0'],
        ]);
        self::assertSame('real-estate-search', $initialize['result']['serverInfo']['name']);

        $this->sessionId = (string) $this->client->getResponse()->headers->get('Mcp-Session-Id');
        self::assertNotSame('', $this->sessionId);

        $this->rpc('notifications/initialized', notification: true);
    }

    public function testListsTheTools(): void
    {
        $response = $this->rpc('tools/list');

        $names = array_column($response['result']['tools'], 'name');
        sort($names);
        self::assertSame(['get_market_overview', 'get_property', 'list_filter_options', 'search_properties'], $names);
    }

    public function testCallsSearchPropertiesWithStructuredContent(): void
    {
        $response = $this->rpc('tools/call', [
            'name' => 'search_properties',
            'arguments' => ['city' => 'Berlin', 'minBedrooms' => 3, 'maxPrice' => 500000],
        ]);

        $result = $response['result'];
        self::assertFalse($result['isError']);
        self::assertSame(4, $result['structuredContent']['total']);
        // Backwards compatible text content carries the same JSON payload.
        self::assertSame($result['structuredContent'], json_decode($result['content'][0]['text'], true));
    }

    /**
     * The challenge requirement: the same search must be available through the API and through
     * search_properties, with the same results.
     *
     * @param array<string, int|string> $filters
     */
    #[DataProvider('searches')]
    public function testSearchPropertiesReturnsTheSameResultsAsTheRestApi(array $filters): void
    {
        $this->client->request('GET', '/api/properties?'.http_build_query($filters + ['perPage' => 50]));
        $api = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $mcp = $this->rpc('tools/call', ['name' => 'search_properties', 'arguments' => $filters + ['limit' => 50]]);

        self::assertNotEmpty($api['data']);
        self::assertSame(array_column($api['data'], 'id'), array_column($mcp['result']['structuredContent']['properties'], 'id'));
        self::assertSame($api['meta']['total'], $mcp['result']['structuredContent']['total']);
    }

    /**
     * @return iterable<string, array{array<string, int|string>}>
     */
    public static function searches(): iterable
    {
        yield 'challenge example' => [['city' => 'Berlin', 'minBedrooms' => 3, 'maxPrice' => 500000]];
        yield 'type and price range, cheapest first' => [['type' => 'apartment', 'minPrice' => 400000, 'maxPrice' => 1000000, 'sort' => 'price_asc']];
        yield 'no filters, largest first' => [['sort' => 'area_desc']];
    }

    public function testOptionalFiltersAcceptNull(): void
    {
        // Clients such as the MCP Inspector send empty optional fields as null.
        $response = $this->rpc('tools/call', [
            'name' => 'search_properties',
            'arguments' => ['city' => 'Berlin', 'minPrice' => null, 'maxPrice' => 500000, 'minBedrooms' => 3, 'type' => null],
        ]);

        self::assertFalse($response['result']['isError']);
        self::assertSame(4, $response['result']['structuredContent']['total']);
    }

    public function testSearchSchemaIsPortableAndMatchesTheDomainEnum(): void
    {
        $tools = array_column($this->rpc('tools/list')['result']['tools'], null, 'name');
        $properties = $tools['search_properties']['inputSchema']['properties'];

        foreach (['city', 'minPrice', 'maxPrice', 'minBedrooms', 'type'] as $name) {
            self::assertArrayNotHasKey('type', $properties[$name], "$name should use anyOf, not a type array");
            self::assertSame(['type' => 'null'], $properties[$name]['anyOf'][1]);
        }

        self::assertSame(PropertyType::values(), $properties['type']['anyOf'][0]['enum']);
        self::assertSame(PropertyType::values(), $tools['get_market_overview']['inputSchema']['properties']['type']['anyOf'][0]['enum']);
    }

    public function testStructuredContentMatchesTheDeclaredOutputSchemas(): void
    {
        $tools = array_column($this->rpc('tools/list')['result']['tools'], null, 'name');
        $validator = new Validator();

        $calls = [
            ['search_properties', ['city' => 'Berlin', 'minBedrooms' => 3, 'maxPrice' => 500000]],
            ['search_properties', ['limit' => 2]],
            ['search_properties', ['city' => 'Paris']],
            ['get_property', ['id' => 5]],
            ['list_filter_options', []],
            ['get_market_overview', []],
            ['get_market_overview', ['type' => 'house']],
        ];

        foreach ($calls as [$name, $arguments]) {
            self::assertArrayHasKey('outputSchema', $tools[$name]);

            $this->rpc('tools/call', ['name' => $name, 'arguments' => $arguments]);
            // Decode as objects so empty JSON objects (e.g. "filters": {}) keep their type.
            $result = json_decode((string) $this->client->getResponse()->getContent(), flags: \JSON_THROW_ON_ERROR)->result;

            $validation = $validator->validate($result->structuredContent, json_decode(json_encode($tools[$name]['outputSchema'], \JSON_THROW_ON_ERROR)));
            self::assertTrue(
                $validation->isValid(),
                \sprintf('%s(%s): %s', $name, json_encode($arguments), json_encode($validation->error() ? (new ErrorFormatter())->format($validation->error()) : null)),
            );
        }
    }

    public function testUnknownPropertyIsReturnedAsToolError(): void
    {
        $response = $this->rpc('tools/call', ['name' => 'get_property', 'arguments' => ['id' => 999]]);

        self::assertTrue($response['result']['isError']);
        self::assertStringContainsString('not found', $response['result']['content'][0]['text']);
    }

    public function testArgumentsAreValidatedAgainstTheInputSchema(): void
    {
        $response = $this->rpc('tools/call', ['name' => 'search_properties', 'arguments' => ['type' => 'castle']]);

        self::assertSame(-32602, $response['error']['code']);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function rpc(string $method, array $params = [], bool $notification = false): array
    {
        static $id = 0;

        $payload = ['jsonrpc' => '2.0', 'method' => $method];
        if (!$notification) {
            $payload['id'] = ++$id;
        }
        if ([] !== $params) {
            $payload['params'] = $params;
        }

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json, text/event-stream',
            'HTTP_MCP_PROTOCOL_VERSION' => self::PROTOCOL_VERSION,
        ];
        if (isset($this->sessionId)) {
            $server['HTTP_MCP_SESSION_ID'] = $this->sessionId;
        }

        $this->client->request('POST', '/mcp', server: $server, content: json_encode($payload, \JSON_THROW_ON_ERROR));

        $content = (string) $this->client->getResponse()->getContent();

        return '' === $content ? [] : json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
    }
}
