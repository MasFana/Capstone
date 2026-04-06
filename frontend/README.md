# Frontend TypeScript SDK

This folder contains the TypeScript SDK for the currently implemented Capstone backend API.

The SDK is a strict-typed wrapper around the CodeIgniter 4 API under `/api/v1` and is designed to be updated as backend routes and features evolve.

## What is in this folder

- `src/sdk/client.ts` — shared HTTP client, base URL handling, bearer token injection, and JSON request/response handling
- `src/sdk/errors.ts` — typed SDK error classes and HTTP error mapping
- `src/sdk/resources/` — resource-level API modules
- `src/sdk/types/` — request and response types used by the SDK
- `src/sdk/tests/` — SDK unit tests for the current exported surface
- `src/index.ts` — top-level export entry

## Current SDK resources

The SDK currently wraps these implemented backend resources:

- `auth`
- `roles`
- `items`
- `stockTransactions`
- `users`

## API reference

### Auth

| SDK method | HTTP endpoint | Actor access |
|---|---|---|
| `sdk.auth.login(payload)` | `POST /api/v1/auth/login` | public |
| `sdk.auth.me()` | `GET /api/v1/auth/me` | authenticated: `admin`, `dapur`, `gudang` |
| `sdk.auth.logout()` | `POST /api/v1/auth/logout` | authenticated: `admin`, `dapur`, `gudang` |

### Roles

| SDK method | HTTP endpoint | Actor access |
|---|---|---|
| `sdk.roles.list()` | `GET /api/v1/roles` | `admin` only |

### Items

| SDK method | HTTP endpoint | Actor access |
|---|---|---|
| `sdk.items.list(query?)` | `GET /api/v1/items` | `admin`, `gudang` |
| `sdk.items.get(id)` | `GET /api/v1/items/{id}` | `admin`, `gudang` |
| `sdk.items.create(payload)` | `POST /api/v1/items` | `admin`, `gudang` |
| `sdk.items.update(id, payload)` | `PUT /api/v1/items/{id}` | `admin`, `gudang` |
| `sdk.items.delete(id)` | `DELETE /api/v1/items/{id}` | `admin` only |

### Stock transactions

| SDK method | HTTP endpoint | Actor access |
|---|---|---|
| `sdk.stockTransactions.list(query?)` | `GET /api/v1/stock-transactions` | `admin`, `gudang` |
| `sdk.stockTransactions.get(id)` | `GET /api/v1/stock-transactions/{id}` | `admin`, `gudang` |
| `sdk.stockTransactions.details(id)` | `GET /api/v1/stock-transactions/{id}/details` | `admin`, `gudang` |
| `sdk.stockTransactions.create(payload)` | `POST /api/v1/stock-transactions` | `admin`, `gudang` |
| `sdk.stockTransactions.submitRevision(id, payload)` | `POST /api/v1/stock-transactions/{id}/submit-revision` | `admin`, `gudang` |
| `sdk.stockTransactions.approve(id)` | `POST /api/v1/stock-transactions/{id}/approve` | `admin` only |
| `sdk.stockTransactions.reject(id)` | `POST /api/v1/stock-transactions/{id}/reject` | `admin` only |

### Users

| SDK method | HTTP endpoint | Actor access |
|---|---|---|
| `sdk.users.list()` | `GET /api/v1/users` | `admin` only |
| `sdk.users.get(id)` | `GET /api/v1/users/{id}` | `admin` only |
| `sdk.users.create(payload)` | `POST /api/v1/users` | `admin` only |
| `sdk.users.update(id, payload)` | `PUT /api/v1/users/{id}` | `admin` only |
| `sdk.users.activate(id)` | `PATCH /api/v1/users/{id}/activate` | `admin` only |
| `sdk.users.deactivate(id)` | `PATCH /api/v1/users/{id}/deactivate` | `admin` only |
| `sdk.users.changePassword(id, payload)` | `PATCH /api/v1/users/{id}/password` | `admin` only |
| `sdk.users.delete(id)` | `DELETE /api/v1/users/{id}` | `admin` only |

## Available scripts

- `npm test` — run SDK tests
- `npm run build` — build TypeScript output to `dist/`
- `npm run typecheck` — run TypeScript checking without emitting files

## Basic usage

```ts
import { createCapstoneSdk } from "./src";

const sdk = createCapstoneSdk({
  baseUrl: "http://127.0.0.1:8080"
});
```

The SDK automatically prefixes requests with `/api/v1`.

## Authentication usage

```ts
import { createCapstoneSdk } from "./src";

const sdk = createCapstoneSdk({
  baseUrl: "http://127.0.0.1:8080"
});

const login = await sdk.auth.login({
  username: "admin",
  password: "password123"
});

sdk.setAccessToken(login.access_token);

const currentUser = await sdk.auth.me();
```

You can also provide token resolution at client creation time:

```ts
const sdk = createCapstoneSdk({
  baseUrl: "http://127.0.0.1:8080",
  getAccessToken: () => localStorage.getItem("access_token")
});
```

## Items example

```ts
const items = await sdk.items.list({
  page: 1,
  perPage: 10,
  q: "beras"
});

const created = await sdk.items.create({
  name: "Minyak",
  item_category_id: 3,
  unit_base: "ml",
  unit_convert: "liter",
  conversion_base: 1000,
  is_active: true
});
```

## Users example

```ts
const user = await sdk.users.create({
  name: "Gudang User",
  username: "gudang1",
  password: "password123",
  role_name: "gudang",
  is_active: true
});

await sdk.users.changePassword(user.data.id, {
  password: "newpassword123"
});
```

## Stock transaction example

```ts
const transaction = await sdk.stockTransactions.create({
  type_name: "IN",
  transaction_date: "2026-04-18",
  details: [
    {
      item_id: 1,
      qty: 5000
    }
  ]
});
```

## Error handling

The SDK throws typed errors for failed requests.

```ts
import { ValidationApiError } from "./src";

try {
  await sdk.items.create({
    name: "Broken",
    item_category_id: 1,
    unit_base: "pcs",
    unit_convert: "pcs",
    conversion_base: 1
  });
} catch (error) {
  if (error instanceof ValidationApiError) {
    console.log(error.errors);
  }
}
```

## Important SDK design rules

- request DTOs are separate from response DTOs
- backend-managed fields are not exposed as writable request fields
- lookup fields that support `id` or `name` use mutually exclusive TypeScript types
- the SDK preserves backend response envelopes instead of flattening them

## Updating the SDK when the backend changes

Do not update the SDK by guessing from one file.

Use the backend contract workflow documented in:

- `../backend/docs/typescript-sdk-maintenance-guide.md`
- `../backend/AGENTS.md`

Start from routes, then docs, then controllers, then supporting services/models/filters/helpers, then backend feature tests.
