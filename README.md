# Real Estate Search Assistant

A small application to search and explore real estate listings, made of three parts:

| Part | Stack | URL (Docker) |
|---|---|---|
| **Frontend** | Nuxt 3 · Vue 3 · TypeScript (strict) · Nuxt UI 3 | http://localhost:3000 |
| **Backend (REST API)** | Symfony 7.4 LTS · PHP 8.4 · Doctrine · SQLite | http://localhost:8000/api/properties |
| **MCP Server** | Official MCP PHP SDK via `symfony/mcp-bundle`, running inside the backend | `http://localhost:8000/mcp` (HTTP) or `bin/console mcp:server` (stdio) |

> Example: *"Properties in Berlin with at least 3 bedrooms and a maximum price of €500,000"*
> - UI: type the sentence in the quick search bar, or open http://localhost:3000/?city=Berlin&minBedrooms=3&maxPrice=500000
> - API: `GET /api/properties?city=Berlin&minBedrooms=3&maxPrice=500000`
> - MCP: `search_properties({ "city": "Berlin", "minBedrooms": 3, "maxPrice": 500000 })`
>
> All three return the same 4 properties, because they run through the same code.

---

## Quick start (Docker)

Requirements: Docker with Docker Compose.

```bash
docker compose up --build
```

On the first start the backend runs the database migration and imports the dataset (`backend/data/properties.json`, 27 properties) into SQLite. Nothing else needs to be installed.

- Frontend: http://localhost:3000
- API: http://localhost:8000/api/properties
- MCP (Streamable HTTP): http://localhost:8000/mcp

### Running the tests

```bash
# Backend: API, MCP tools and MCP HTTP endpoint (PHPUnit)
docker compose exec backend composer test

# Frontend: utilities and components (Vitest + @nuxt/test-utils), and strict type checking
cd frontend && npm ci && npm test && npm run typecheck
```

## Using the MCP server

The MCP server exposes three tools:

| Tool | Description |
|---|---|
| `search_properties` | Search with optional filters `city`, `minPrice`, `maxPrice`, `minBedrooms`, `type`, `sort`, `limit`. Returns the total count plus summaries with links to the frontend. |
| `get_property` | Full details of one property by `id` (description, address, features, images, location, price per m²). |
| `list_filter_options` | Available cities and types (with counts), the price range and the sort options, so the model can check valid values before searching. |

It is available over both MCP transports:

**Streamable HTTP** (backend container running):

```bash
# MCP Inspector (v2): Add Servers → Add manually → transport "streamable-http",
# URL http://localhost:8000/mcp → toggle the server on → "Tools" tab
npx @modelcontextprotocol/inspector@latest
# On Windows, if it fails with "listen EACCES" (ports reserved by Hyper-V/Docker), pick other ports:
#   CLIENT_PORT=7274 SERVER_PORT=7277 npx @modelcontextprotocol/inspector@latest

# Claude Code
claude mcp add --transport http real-estate http://localhost:8000/mcp
```

**stdio** (e.g. Claude Desktop, `claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "real-estate": {
      "command": "docker",
      "args": ["compose", "-f", "/absolute/path/to/repo/compose.yaml", "exec", "-T", "backend", "php", "bin/console", "mcp:server"]
    }
  }
}
```

Quick smoke test from a terminal without any MCP client:

```bash
curl -s http://localhost:8000/mcp -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream' \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"curl","version":"1"}}}'
```

## Running without Docker

Requirements: PHP 8.4 (with `pdo_sqlite`), Composer, Node.js 22+.

```bash
# Backend: http://localhost:8000
cd backend
composer install
php bin/console doctrine:migrations:migrate -n
php bin/console app:properties:import
php -S localhost:8000 -t public     # or: symfony serve --port=8000

# MCP over stdio (the HTTP endpoint is served by the backend above)
php bin/console mcp:server

# Frontend: http://localhost:3000 (proxies /api to http://localhost:8000 by default)
cd frontend
npm install
npm run dev
```

---

## Architecture

```
                         ┌──────────────────────────── backend (Symfony) ────────────────────────────┐
 Browser                 │                                                                           │
   │                     │   HTTP adapter                     MCP adapter                            │
   ▼                     │   PropertyController               SearchPropertiesTool / GetPropertyTool │
┌─────────────┐  /api/** │   #[MapQueryString] + 422          ListFilterOptionsTool                  │
│ Nuxt (SSR)  │─────────▶│   RFC 9457 problem+json            JSON Schema + isError results          │
│ + /api      │  proxy   │            │                                   │                          │
│   proxy     │          │            └──────────────┬────────────────────┘                          │
└─────────────┘          │                           ▼                                               │
                         │     PropertySearchCriteria (DTO + validation rules, shared)               │
 MCP clients             │     PropertyCatalog (application service: search / get / facets)          │
 (Claude, Inspector) ───▶│                           │                                               │
   /mcp (HTTP) or stdio  │                           ▼                                               │
                         │     PropertyRepository (Doctrine QueryBuilder) ──▶ SQLite                 │
                         └───────────────────────────────────────────────────────────────────────────┘
```

### Backend (`backend/`)

```
src/
├── Controller/PropertyController.php       REST endpoints (thin: map request → catalog → view)
├── Mcp/                                    MCP tools (thin: map arguments → catalog → view)
├── Search/
│   ├── PropertySearchCriteria.php          Filters + validation constraints (used by REST and MCP)
│   ├── PropertyCatalog.php                 Application service, single entry point for reads
│   ├── PropertySearchResult.php, SearchFacets.php, PropertyNotFoundException.php
├── Repository/PropertyRepository.php       Query building (filters, sorting, pagination, facets)
├── Entity/Property.php, Enum/              Domain model (PropertyType, PropertySort)
├── View/                                   Output representations shared by REST and MCP
├── EventSubscriber/ApiExceptionSubscriber  Consistent RFC 9457 error responses under /api
└── Import/, Command/                       Dataset import (app:properties:import)
```

### REST API

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/properties` | Search. Query params (all optional): `city`, `minPrice`, `maxPrice`, `minBedrooms`, `type` (`apartment`, `house`, `studio`, `penthouse`, `townhouse`), `sort` (`newest`, `price_asc`, `price_desc`, `area_desc`), `page`, `perPage` (1–50). |
| `GET` | `/api/properties/{id}` | Property details. |
| `GET` | `/api/properties/facets` | Cities and types with counts, price range, max bedrooms (used to build the filter form). |

```jsonc
// GET /api/properties?city=Berlin&minBedrooms=3&maxPrice=500000
{
  "data": [{ "id": 9, "title": "…", "type": "apartment", "city": "Berlin", "price": 329000, "bedrooms": 3, … }],
  "meta": { "total": 4, "page": 1, "perPage": 12, "totalPages": 1,
            "filters": { "city": "Berlin", "maxPrice": 500000, "minBedrooms": 3 }, "sort": "newest" }
}

// GET /api/properties?minPrice=500000&maxPrice=100000&type=castle → 422 application/problem+json
{
  "type": "about:blank", "title": "Unprocessable Content", "status": 422,
  "detail": "The request contains invalid parameters.",
  "violations": [
    { "field": "maxPrice", "message": "The maximum price must be greater than or equal to the minimum price." },
    { "field": "type", "message": "Unknown property type \"castle\". Allowed values: \"apartment\", \"house\", …" }
  ]
}
```

### Frontend (`frontend/`)

```
pages/index.vue                  Search: filters + results + pagination, all states handled
pages/properties/[id].vue        Details: gallery, key facts, features, map; real 404 for unknown ids
components/search/               QuickSearch (free text), McpCallPreview, SearchFilters (form),
                                 SearchResultsHeader (count + sort)
components/property/             PropertyCard, PropertyGrid, PropertyCardSkeleton, PropertyImage,
                                 PropertyGallery, PropertyFacts, PropertyLocationMap
components/common/               EmptyState, ErrorState
composables/usePropertySearch.ts URL query ⇄ filters ⇄ API
composables/useSearchFacets.ts   Filter options
utils/                           search-query (URL ⇄ filters), quick-search (free text → filters),
                                 mcp-call (filters → search_properties call), format, api-error
server/api/[...path].ts          Proxy /api/** → Symfony
types/property.ts                API contract types
```

---

## Technical decisions

### 1. The MCP server lives in the backend and calls the application layer

The challenge asks whether the MCP server should read the database directly or go through the API. I chose a third option: the MCP tools are a second adapter inside the Symfony app, next to the REST controller. Both call the same application service (`PropertyCatalog`) and validate the same DTO (`PropertySearchCriteria`).

- **Why not direct database access?** The filtering, sorting and validation logic would exist twice, and the two copies would drift apart. The requirement that the same search works in the app and in `search_properties` is only guaranteed if both use one implementation.
- **Why not call the REST API over HTTP?** It would need a second process and a network hop, and the MCP server would fail whenever the API is down. The HTTP contract would also have to be mapped again into tool schemas and types. The PHP SDK already gives us JSON Schema generation from typed method signatures, so the tools stay thin adapters.
- **Trade-offs:** the MCP server scales and deploys together with the backend. If it ever needs to be deployed on its own, the tools only depend on `PropertyCatalog`, so they could be moved behind the HTTP API without touching the domain code. `symfony/mcp-bundle` is still marked experimental, so its version is pinned in `composer.lock`. It is maintained by the Symfony team together with the official `mcp/sdk`.

MCP details:
- Tool arguments use the **same names as the REST query parameters** (`minPrice`, `minBedrooms`, …), so there is one vocabulary.
- Arguments are validated **twice, by design**. The SDK checks them against the JSON Schema (types, enums, ranges) and returns JSON-RPC `-32602` on failure. The domain constraints (for example `maxPrice >= minPrice`) come back as **tool errors** (`isError: true`) with a readable message, so the model can correct itself.
- Results are sent as **`structuredContent`**, plus the same JSON as text content for older clients, as the MCP spec recommends. All three tools declare an **`outputSchema`**, and a test validates real tool results against it so the contract cannot drift.
- Input schemas are written for **portability across clients**. Optional filters use `anyOf: [{type}, {type: "null"}]` instead of type arrays, because some clients (e.g. Gemini's function-calling dialect) reject type arrays and the MCP Inspector sends empty fields as `null`. They also include `examples` (e.g. `"Berlin"`, `500000`) to guide the model.
- Results include hints for the model: a `note` when results are truncated or empty, a `url` to the property page, and the tools are annotated as read-only and idempotent.
- The tools are exposed over **Streamable HTTP and stdio** from the same configuration.

### 2. Data: SQLite + Doctrine, seeded from a JSON file

The dataset is a readable JSON file (`backend/data/properties.json`) that is imported into SQLite on startup (`app:properties:import --if-empty`). Filters then run as real SQL queries through Doctrine, which provides pagination, sorting and aggregate facets, and the data is still easy to review in a pull request. The schema is managed with a Doctrine migration. Switching to PostgreSQL would only require changing `DATABASE_URL`.

### 3. API design

- Plain Symfony controllers with a DTO mapped by `#[MapQueryString]`. I chose this over API Platform to keep the contract explicit and small.
- The response has a `data`/`meta` envelope. `meta` returns pagination, the **applied filters** and the sort, which makes client-side debugging easier.
- All filters are optional and combined with AND. City matching is case-insensitive. `minBedrooms` means "at least", which matches the challenge wording ("pelo menos 3 quartos").
- `type` and `sort` are validated with `Choice` instead of being deserialized directly into enums, so a wrong value produces "Allowed values: …" instead of a generic type error.
- Sorting uses `id` as a tie-breaker, so pagination stays stable.
- Every error under `/api` is returned as **RFC 9457 problem details** (`application/problem+json`): 404 for unknown properties and 422 with a `violations` list for invalid filters. Internal errors never expose details in production.

### 4. Frontend

- **The URL is the source of truth for the search state.** Filters, sort and page live in the query string. Links can be shared, back/forward navigation works, and a reload server-side renders the same results. URL parsing is lenient: a hand-edited, invalid value is ignored instead of breaking the page. The API is still strict.
- **Backend-for-frontend proxy** (`server/api/[...path].ts`): the browser only talks to the Nuxt origin, so no CORS setup is needed and the backend address is configured at runtime (`NUXT_API_BASE_URL`). If the backend is unreachable, the proxy returns a 502 problem document with the same shape as the API errors.
- **All states are handled explicitly:**
  - Initial load shows skeleton cards.
  - Refetching dims the current results instead of replacing them with a spinner.
  - Errors are shown inline with a message from the problem details and a retry button.
  - Empty results offer to clear the filters.
  - A page past the last one gets its own message.
  - An unknown property shows a real 404 page with the correct HTTP status.
  - Broken images fall back to a placeholder.
- **Quick search:** a free-text bar turns sentences like *"apartments in Berlin, at least 3 bedrooms, max €500k"* into filters. It recognizes cities (including German names like "München"), property types, bedrooms, and price limits and ranges. Below it, the UI shows the **equivalent MCP call** (`search_properties({ city: "Berlin", … })`) with a copy button, which makes visible that the UI and the MCP tool are the same search.
  - The parser is **deterministic and rule-based**, not an LLM. It works offline, needs no API key to evaluate the project and is fully unit-tested. Free-text understanding by a model is exactly what the MCP server enables: an AI client calls `search_properties` itself.
- **Filter UX:** the city select and the type and bedroom chips apply immediately. Price inputs are debounced and validated on the client (min ≤ max), so the API is not called with a range that is known to be invalid. The filters sit in a sidebar on desktop and in a slide-over on mobile, and a "Clear all" button appears when filters are active.
- **Components:** pages only compose. Data fetching lives in composables, formatting and URL logic in pure, unit-tested utilities, and presentation in small single-purpose components.
- **Extras:** dark mode, a location map (OpenStreetMap embed, which needs no API key or extra library), SEO meta per property, and accessibility details (`role="search"`, `aria-busy`, `aria-live`, labelled controls).

### 5. Versions

- **Symfony 7.4 LTS** (supported until 2029), running on **FrankenPHP** in Docker.
- **Nuxt 3.21**, because the challenge asks for Vue 3 / Nuxt 3. This is also why I use **Nuxt UI 3**: Nuxt UI 4 requires Nuxt ≥ 4.1. The code already uses the patterns Nuxt 4 expects, so an upgrade would mostly consist of moving files into `app/` and bumping Nuxt UI.
- Vue APIs (`ref`, `computed`, `watch`) are **imported explicitly**. With this Nuxt/Vue combination, the generated auto-import declarations resolve them to `any`, which would silently weaken `nuxt typecheck` in strict mode. Nuxt composables (`useFetch`, `useRoute`, …) and utilities stay auto-imported.

## What I would do next

- Generate an OpenAPI description (e.g. NelmioApiDocBundle) and derive the frontend types from it, instead of mirroring them by hand in `types/property.ts`.
- Add end-to-end tests with Playwright and run everything in CI (GitHub Actions).
- Full-text search on title and description.
- Location search via geocoding instead of matching city names: districts, postcodes, a radius ("within 5 km of…") and alternate names ("München" / "Munich", "Köln" / "Cologne").
- Internationalization (English and German first) with `@nuxtjs/i18n`. Price and date formatting is already centralized in `utils/format.ts`, so it is mainly a matter of extracting the UI texts.
- Add an MCP prompt (for example "find a home for a family of four") and MCP resources for individual listings.
- Add authentication to the MCP HTTP endpoint (OAuth, as specified by MCP) before exposing it publicly.
