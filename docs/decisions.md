# Technical decisions

The [README](../README.md) summarises the main decisions. This document explains them in more detail.

## 1. MCP server

### Inside the backend, on the application layer

The challenge asks whether the MCP server should read the database directly or go through the API.
I chose a third option: the MCP tools are a second adapter inside the Symfony app, next to the REST
controller. Both call the same application service (`PropertyCatalog`) and validate the same DTO
(`PropertySearchCriteria`).

- **Not the database directly:** the filtering, sorting and validation logic would exist twice and
  drift apart. One implementation is the only real guarantee that the app and `search_properties`
  return the same results (a test asserts it).
- **Not the REST API over HTTP:** a second process and a network hop, an MCP server that fails
  whenever the API is down, and the HTTP contract mapped again into tool schemas.
- **Trade-off:** the MCP server is deployed with the backend. The tools only depend on
  `PropertyCatalog`, so they could later move behind the HTTP API without touching the domain code.

It uses `symfony/mcp-bundle` with the official `mcp/sdk`. The bundle is still marked experimental,
so its version is pinned in `composer.lock`. Each tool is a class with an `#[McpTool]` attribute, and
the SDK generates the JSON Schema from the typed method signature.

### Details

- **One vocabulary:** tool arguments use the same names as the REST query parameters.
- **Two levels of validation, by design.** Wrong types or enum values are rejected by the SDK
  against the JSON Schema (JSON-RPC error `-32602`). Business rules (`maxPrice >= minPrice`, unknown
  id) come back as a tool result with `isError: true` and a readable message, so the model can read
  it and correct itself.
- **Structured results:** `structuredContent` plus the same JSON as text for older clients, as the
  specification recommends. Every tool declares an `outputSchema`, and a test validates real results
  against it.
- **Portable input schemas:** optional filters use `anyOf: [{type}, {type: "null"}]` instead of type
  arrays, because some clients (e.g. Gemini's function-calling dialect) reject type arrays and the
  MCP Inspector sends empty fields as `null`. Both cases have regression tests. Inputs carry
  `examples` to guide the model.
- **Hints for the model:** a `note` when results are truncated or empty, a `url` to the property
  page, server `instructions`, and read-only / idempotent annotations.
- **Tools for the questions an assistant gets:** `list_filter_options` lets the model check valid
  values instead of guessing (the dataset uses "Munich", not "München"), and `get_market_overview`
  plus the `market` field of `get_property` answer "Is this a good price?" or "Which city is
  cheapest per m²?". The city average is area-weighted (total price / total living area), so a small
  studio does not weigh as much as a large house.
- **Two transports from one configuration:** Streamable HTTP at `/mcp`, available while the backend
  runs, and stdio (`bin/console mcp:server`), started by the client; stdout only carries JSON-RPC.

## 2. Data

The dataset is a readable JSON file (27 fictional listings in five German cities) imported into
SQLite on startup. Filters run as real SQL through Doctrine, the schema is managed with a migration,
and switching to PostgreSQL only needs a different `DATABASE_URL`. Photos are Unsplash images,
checked to exist and to show homes; the frontend falls back to a placeholder if one fails to load.

## 3. API design

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/properties` | Search: `city`, `minPrice`, `maxPrice`, `minBedrooms`, `type`, `sort`, `page`, `perPage` (all optional) |
| `GET` | `/api/properties/{id}` | Property details, including the `market` comparison |
| `GET` | `/api/properties/facets` | Cities and types with counts, price range, max bedrooms |
| `GET` | `/api/properties/market` | Price statistics per city, optional `type` |

- **Plain Symfony controllers** with a DTO mapped by `#[MapQueryString]`, rather than API Platform,
  to keep the contract explicit and small.
- **`data` / `meta` envelope;** `meta` returns pagination, the applied filters and the sort.
- **Filter semantics:** optional and combined with AND, case-insensitive city, `minBedrooms` means
  "at least" (as in the challenge). Sorting uses `id` as a tie-breaker so pagination stays stable.
- **Helpful errors:** everything under `/api` is an RFC 9457 problem document. `type` and `sort` are
  validated with `Choice`, so a wrong value lists the allowed ones instead of a generic type error:

```jsonc
// GET /api/properties?minPrice=500000&maxPrice=100000&type=castle → 422
{
  "type": "about:blank", "title": "Unprocessable Content", "status": 422,
  "detail": "The request contains invalid parameters.",
  "violations": [
    { "field": "maxPrice", "message": "The maximum price must be greater than or equal to the minimum price." },
    { "field": "type", "message": "Unknown property type \"castle\". Allowed values: \"apartment\", \"house\", …" }
  ]
}
```

## 4. Frontend

- **The URL is the search state:** shareable links, working back/forward and server-side rendering
  of the same results. URL parsing is lenient (an invalid hand-edited value is ignored); the API stays
  strict.
- **Backend-for-frontend proxy:** the browser only talks to the Nuxt origin (no CORS), the backend
  address is runtime configuration, and an unreachable backend becomes a 502 problem document in the
  same shape as the API errors.
- **Every state is handled:** skeletons, dimmed results while refetching, errors with a retry, empty
  results, a page past the last one, a real 404 page, and image placeholders (including images that
  fail before hydration).
- **Quick search is rule-based, not an LLM:** it works offline, needs no API key and is unit-tested.
  Understanding free text is the MCP server's job.
- **Components:** pages only compose; composables hold data fetching; URL and formatting logic are
  pure, tested utilities; components receive props and emit events, so the same filter form serves
  the desktop sidebar and the mobile slide-over.

## 5. Testing

- **Backend (PHPUnit):** the REST API (including market statistics with hand-computed expected
  values), the MCP tools, and the MCP endpoint end to end over HTTP: API/MCP parity, `null`
  arguments, schema portability and output schema contracts.
- **Frontend (Vitest):** pure utilities in plain Node, components with the Nuxt runtime, and
  `nuxt typecheck` in strict mode.

## 6. Versions

- **Symfony 7.4 LTS** (supported until 2029) on PHP 8.4, served by FrankenPHP.
- **Nuxt 3.21**, as the challenge asks, and therefore **Nuxt UI 3** (Nuxt UI 4 requires Nuxt 4.1).
- **Explicit Vue imports** (`ref`, `computed`, `watch`): with this Nuxt/Vue combination the generated
  auto-import declarations type them as `any`, which would silently weaken strict type checking.

## 7. Next steps

The challenge suggested 4–6 hours, so I deliberately kept the scope small. These are the things I
left out and would add with more time, or if this became a real product:

- An OpenAPI description, with the frontend types generated from it instead of mirrored by hand.
- End-to-end tests (Playwright) and CI.
- Location search via geocoding instead of city names: districts, postcodes, a radius and alternate
  names ("München" / "Munich").
- Full-text search on title and description, and internationalization (English and German first).
- An MCP prompt, MCP resources for individual listings and an MCP App widget (results on a map inside
  the chat client).
- Authentication. I left it out on purpose: searching listings is public, and a login would only make
  the project harder to try out. Where it really matters is the MCP HTTP endpoint: before exposing it
  outside a local network I would protect it with OAuth, as the MCP specification describes.
