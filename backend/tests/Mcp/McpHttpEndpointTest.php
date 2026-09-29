<?php

declare(strict_types=1);

namespace App\Tests\Mcp;

use App\Tests\SeededDatabaseTrait;
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
        self::assertSame(['get_property', 'list_filter_options', 'search_properties'], $names);
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
