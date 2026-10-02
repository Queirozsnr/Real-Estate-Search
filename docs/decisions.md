# Technical decisions

The [README](../README.md) summarises the main decisions. This document explains each one in more detail.

- [1. MCP server](#1-mcp-server)
- [2. Data](#2-data)
- [3. API design](#3-api-design)
- [4. Frontend](#4-frontend)
- [5. Testing](#5-testing)
- [6. Versions and tooling](#6-versions-and-tooling)
- [7. Project structure](#7-project-structure)
- [8. Next steps](#8-next-steps)

## 1. MCP server

### Where it lives: inside the backend, on the application layer

The challenge asks whether the MCP server should read the database directly or go through the API.
I chose a third option: the MCP tools are a second adapter inside the Symfony app, next to the REST
controller. Both call the same application service (`PropertyCatalog`) and validate the same DTO
(`PropertySearchCriteria`).

- **Why not direct database access?** The filtering, sorting and validation logic would exist twice,
  and the two copies would drift apart. The requirement that the same search works in the app and in
  `search_properties` is only guaranteed if both use one implementation.
- **Why not call the REST API over HTTP?** It would need a second process and a network hop, and the
  MCP server would fail whenever the API is down. The HTTP contract would also have to be mapped again
  into tool schemas and types.
- **Trade-offs:** the MCP server scales and deploys together with the backend. If it ever needs to be
  deployed on its own, the tools only depend on `PropertyCatalog`, so they could be moved behind the
  HTTP API without touching the domain code.

It is built with `symfony/mcp-bundle` and the official `mcp/sdk`, maintained by the Symfony team. The
bundle is still marked experimental, so its version is pinned in `composer.lock`. Tools are plain
classes with an `#[McpTool]` attribute, and the SDK generates their JSON Schema from the typed method
signature.

### Tools

| Tool | Purpose |
|---|---|
| `search_properties` | Same filters as `GET /api/properties`, plus `limit`. Returns the total count and property summaries with a link to the frontend. |
| `get_property` | Full details of one property, including `market`: its price per m² compared with the city average. |
| `list_filter_options` | Valid cities and types (with counts), the price range and the sort options, so the model checks values instead of guessing (the dataset uses "Munich", not "München"). |
| `get_market_overview` | Price statistics per city (listings, average price per m², average price, price range), optionally for one type. |

Every tool has a REST counterpart, and a test asserts that `search_properties` and
`GET /api/properties` return the same properties in the same order for the same filters.

### Details

- **One vocabulary.** Tool arguments use the same names as the REST query parameters (`minPrice`,
  `minBedrooms`, …).
- **Validation on two levels, by design.** The SDK validates arguments against the JSON Schema
  (types, enums, ranges) and answers with JSON-RPC error `-32602`. Domain rules (for example
  `maxPrice >= minPrice`, or an unknown id) come back as a tool result with `isError: true` and a
  readable message, so the model can read it and correct itself.
- **Structured results.** Results are sent as `structuredContent`, plus the same JSON as text content
  for older clients, as the MCP specification recommends. Every tool declares an `outputSchema`, and a
  test validates real tool results against it, so the contract cannot drift.
- **Portable input schemas.** Optional filters use `anyOf: [{type}, {type: "null"}]` instead of type
  arrays (`["null", "string"]`), because some clients (e.g. Gemini's function-calling dialect) reject
  type arrays, and the MCP Inspector sends empty fields as `null`. Both cases have regression tests.
  The main inputs carry `examples` (`"Berlin"`, `500000`, `3`) to guide the model.
- **Hints for the model.** A `note` when results are truncated ("Showing the first 10 of 27…") or
  empty (pointing to `list_filter_options`), a `url` to the property page, server `instructions`
  explaining when to use each tool, and read-only / idempotent tool annotations.
- **Tools designed around the questions an assistant gets.** `get_market_overview` and the `market`
  comparison let a model answer "Is this a good price?" or "Which city is cheapest per m²?". The city
  average is an area-weighted price per m² (total price / total living area), so a small studio does
  not weigh as much as a large house. The same numbers are shown on the property page ("14% below the
  Berlin average").
- **Two transports from one configuration.** Streamable HTTP at `/mcp` is always available while the
  backend runs (it is just another route). With stdio, the client starts `bin/console mcp:server`
  itself and talks to it over stdin/stdout; stdout only carries JSON-RPC, never logs.

## 2. Data

The dataset is a readable JSON file (`backend/data/properties.json`, 27 fictional listings in five
German cities) imported into SQLite on startup (`app:properties:import --if-empty`). Filters then run
as real SQL through Doctrine (pagination, sorting, aggregates for facets and market statistics), and
the data stays easy to review in a pull request. The schema is managed with a Doctrine migration;
switching to PostgreSQL only needs a different `DATABASE_URL`.

Photos are Unsplash images, checked to exist and to show homes. The frontend falls back to a
placeholder if one fails to load.

## 3. API design

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/properties` | Search. Optional query params: `city`, `minPrice`, `maxPrice`, `minBedrooms`, `type`, `sort` (`newest`, `price_asc`, `price_desc`, `area_desc`), `page`, `perPage` (1–50). |
| `GET` | `/api/properties/{id}` | Property details, including the `market` comparison. |
| `GET` | `/api/properties/facets` | Cities and types with counts, price range, max bedrooms (used by the filter form). |
| `GET` | `/api/properties/market` | Price statistics per city. Optional `type`. |

- **Plain Symfony controllers** with a DTO mapped by `#[MapQueryString]`, rather than API Platform,
  to keep the contract explicit and small.
- **`data` / `meta` envelope.** `meta` returns pagination, the applied filters and the sort, which
  makes debugging a client easier.
- **Filter semantics.** All filters are optional and combined with AND. City matching is
  case-insensitive. `minBedrooms` means "at least", as in the challenge ("pelo menos 3 quartos").
- **Helpful validation messages.** `type` and `sort` are validated with `Choice` instead of being
  deserialized straight into enums, so a wrong value produces "Allowed values: …" instead of a
  generic type error.
- **Stable pagination.** Sorting uses `id` as a tie-breaker.
- **Errors as RFC 9457 problem details** (`application/problem+json`) for everything under `/api`:
  422 with per-field violations, 404 for unknown properties, and no internal details in production.

```jsonc
// GET /api/properties?city=Berlin&minBedrooms=3&maxPrice=500000
{
  "data": [{ "id": 9, "title": "…", "type": "apartment", "city": "Berlin", "price": 329000, … }],
  "meta": { "total": 4, "page": 1, "perPage": 12, "totalPages": 1,
            "filters": { "city": "Berlin", "maxPrice": 500000, "minBedrooms": 3 }, "sort": "newest" }
}

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

- **The URL is the source of truth for the search state.** Filters, sort and page live in the query
  string, and every change is a navigation. Links can be shared, back/forward works and a reload
  server-side renders the same results. URL parsing is lenient (a hand-edited invalid value is
  ignored); the API stays strict.
- **Backend-for-frontend proxy** (`server/api/[...path].ts`). The browser only talks to the Nuxt
  origin, so there is no CORS setup, and the backend address is runtime configuration
  (`NUXT_API_BASE_URL`). If the backend is unreachable, the proxy answers with a 502 problem document
  in the same shape as the API errors.
- **All states are handled:** skeleton cards on first load; dimmed results while refetching; inline
  errors with the problem detail and a retry button; an empty state that offers to clear the filters;
  a message for a page past the last one; a real 404 page (correct HTTP status) for unknown
  properties; and a placeholder for broken images, including ones that fail before hydration.
- **Quick search.** A free-text bar turns sentences like "apartments in Berlin, at least 3 bedrooms,
  max €500k" into filters. It recognizes cities (including German names such as "München"), property
  types, bedrooms, and price limits and ranges. It is deterministic and rule-based rather than an LLM:
  it works offline, needs no API key to evaluate the project and is fully unit-tested.
- **Filters.** The city field lists the available cities on click and filters them as you type (any
  other city can still be searched). City, type and bedroom filters apply immediately; price inputs
  are debounced and validated on the client (min ≤ max). Filters sit in a sidebar on desktop and in a
  slide-over on mobile.
- **Property page.** Photo mosaic with a "Show all photos" view, key facts, description, amenities, a
  sticky price card with the city comparison, and an OpenStreetMap embed (no API key or extra library).
- **Components.** Pages only compose. Data fetching lives in composables, URL and formatting logic in
  pure, unit-tested utilities, and presentation in small components that receive props and emit
  events, so the same filter form is reused in the desktop sidebar and the mobile slide-over.
- **Extras:** light/dark mode, SEO meta per property, and accessibility details (`role="search"`,
  `aria-busy`, `aria-live`, labelled radio groups).

## 5. Testing

- **Backend (PHPUnit):** the REST API (filters, the challenge example, sorting, pagination, 404/422,
  facets, market statistics with hand-computed expected values), the MCP tools called directly, and
  the MCP endpoint end to end over HTTP (handshake → `tools/list` → `tools/call`), including API/MCP
  parity, `null` arguments, schema portability and output schema contracts.
- **Frontend (Vitest):** pure utilities (URL parsing, quick search parser, error descriptions) in
  plain Node, and components (property card, filters, price comparison) with the Nuxt runtime, plus
  `nuxt typecheck` in strict mode.

## 6. Versions and tooling

- **Symfony 7.4 LTS** (supported until 2029) on **PHP 8.4**, served by **FrankenPHP** in Docker.
- **Nuxt 3.21**, because the challenge asks for Vue 3 / Nuxt 3. This is also why the project uses
  **Nuxt UI 3**: Nuxt UI 4 requires Nuxt ≥ 4.1. The code already follows the patterns Nuxt 4
  expects, so an upgrade would mostly mean moving files into `app/` and bumping Nuxt UI.
- **Explicit Vue imports.** `ref`, `computed` and `watch` are imported from `vue`: with this Nuxt/Vue
  combination the generated auto-import declarations type them as `any`, which would silently weaken
  `nuxt typecheck` in strict mode. Nuxt composables and project utilities stay auto-imported.

## 7. Project structure

```
backend/src/
├── Controller/PropertyController.php       REST endpoints (map request → catalog → view)
├── Mcp/                                    MCP tools (map arguments → catalog → view) and output schemas
├── Search/
│   ├── PropertySearchCriteria.php          Filters + validation constraints (shared by REST and MCP)
│   ├── PropertyCatalog.php                 Application service, single entry point for reads
│   ├── CityMarketStats.php, MarketComparison.php
│   └── PropertySearchResult.php, SearchFacets.php, PropertyNotFoundException.php
├── Repository/PropertyRepository.php       Queries: filters, sorting, pagination, facets, market stats
├── Entity/Property.php, Enum/              Domain model (PropertyType, PropertySort)
├── View/                                   Output representations shared by REST and MCP
├── EventSubscriber/ApiExceptionSubscriber  RFC 9457 error responses under /api
└── Import/, Command/                       Dataset import (app:properties:import)

frontend/
├── pages/                                  index.vue (search), properties/[id].vue (details)
├── components/search/                      QuickSearch, SearchFilters, SearchResultsHeader
├── components/property/                    PropertyCard, PropertyGrid, PropertyGallery, PropertyFacts,
│                                           PropertyFeatures, PriceComparison, PropertyLocationMap, …
├── components/common/                      EmptyState, ErrorState
├── composables/                            usePropertySearch (URL ⇄ filters ⇄ API), useSearchFacets
├── utils/                                  search-query, quick-search, format, api-error
├── server/api/[...path].ts                 Proxy /api/** → Symfony
└── types/property.ts                       API contract types
```

## 8. Next steps

- Generate an OpenAPI description (e.g. NelmioApiDocBundle) and derive the frontend types from it,
  instead of mirroring them by hand in `types/property.ts`.
- Add end-to-end tests with Playwright and run everything in CI (GitHub Actions).
- Full-text search on title and description.
- Location search via geocoding instead of matching city names: districts, postcodes, a radius
  ("within 5 km of…") and alternate names ("München" / "Munich", "Köln" / "Cologne").
- Internationalization (English and German first) with `@nuxtjs/i18n`; price and date formatting is
  already centralized in `utils/format.ts`.
- An MCP prompt (for example "find a home for a family of four") and MCP resources for individual
  listings, and an MCP App widget (results on a map inside the chat client).
- OAuth on the MCP HTTP endpoint before exposing it publicly.
