# Frontend TypeScript SDK

This folder contains the TypeScript SDK for the currently implemented Capstone backend API.

It is a typed wrapper over the CodeIgniter 4 backend under `/api/v1`, with resource modules, request/response types, typed API errors, and a small shared HTTP client.

## Canonical Backend References

- **Use this file for:** SDK usage, SDK resource surface, SDK request/response typing, and frontend-facing examples.
- **Do not use this file as the canonical source for:** backend implementation status, backend schema rules, or route discovery workflow.
- **Read next before changing SDK contracts:** `../backend/AGENTS.md`, `../backend/docs/README.md`, `../backend/docs/architecture/runtime-status.md`, `../backend/docs/reference/api-contract.md`.

## Scope

The SDK only covers the backend routes that are implemented and verified now. It does not expose planned backend modules that do not yet exist as active API routes.

If you need a compact backend-side index of implemented vs planned modules, route groups, key flow rules, and permission notes before wiring new SDK surfaces, see `../backend/docs/architecture/runtime-status.md` (Canonical).

Implemented SDK resources:

- `auth`
- `roles`
- `users`
- `items`
- `stockTransactions`
- `itemCategories`
- `itemUnits`
- `mealTimes`
- `transactionTypes`
- `approvalStatuses`
- `dailyPatients`
- `spk`
- `menus`
- `dishes`
- `dishCompositions`
- `menuSchedules`
- `dashboard`
- `reports`
- `stockOpnames`

## Folder structure

- `src/sdk/client.ts` — shared HTTP client, URL building, bearer token injection, JSON parsing
- `src/sdk/errors.ts` — typed API error classes and status-code mapping
- `src/sdk/resources/` — resource-specific API modules
- `src/sdk/types/` — request and response types
- `src/sdk/tests/` — SDK unit tests
- `src/index.ts` — top-level export entry
- `dist/` — generated build output

## Available scripts

- `npm test` — run SDK tests with Vitest
- `npm run typecheck` — run TypeScript checking without emitting files
- `npm run build` — regenerate `dist/`

## Quick start

```ts
import { createCapstoneSdk } from "./src";

const sdk = createCapstoneSdk({
  baseUrl: "http://127.0.0.1:8080"
});
```

By default, the client prefixes all requests with `/api/v1`, so the example above calls `http://127.0.0.1:8080/api/v1/...`.

## Client configuration

`createCapstoneSdk()` and `new CapstoneSdk()` both accept the same `ApiClientOptions`.

### Supported options

- `baseUrl` — backend origin; default: `http://127.0.0.1:8080`
- `apiBasePath` — API base path; default: `/api/v1`
- `accessToken` — initial bearer token stored in memory
- `getAccessToken` — sync or async token resolver called before each request
- `defaultHeaders` — shared request headers
- `fetchImplementation` — custom `fetch`, useful for SSR, tests, or non-browser environments

### Example: dynamic token lookup

```ts
const sdk = createCapstoneSdk({
  baseUrl: "http://127.0.0.1:8080",
  getAccessToken: () => localStorage.getItem("access_token")
});
```

### Example: in-memory token management

```ts
const login = await sdk.auth.login({
  username: "admin",
  password: "password123"
});

sdk.setAccessToken(login.access_token);

const me = await sdk.auth.me();

sdk.clearAccessToken();
```

## Authentication model

The SDK does not manage refresh tokens or persistent storage for you. It only injects a bearer token if one is available from:

1. `getAccessToken()`, if provided
2. the in-memory token set by `accessToken` or `setAccessToken()`

Protected endpoints send:

```http
Authorization: Bearer <token>
```

## Response shapes

The SDK preserves backend response envelopes instead of flattening them.

### Single resource

```ts
type ApiDataResponse<T> = {
  data: T;
};
```

### List resource

```ts
type ApiListResponse<T> = {
  data: T[];
  meta: {
    page: number;
    perPage: number;
    total: number;
    totalPages: number;
    paginated?: boolean;
  };
  links: {
    self: string;
    first: string;
    last: string;
    next: string | null;
    previous: string | null;
  };
};
```

### Message response

```ts
type ApiMessageResponse = {
  message: string;
};

type ApiMessageDataResponse<T> = {
  message: string;
  data: T;
};
```

## Error handling

Failed requests throw typed errors from `src/sdk/errors.ts`.

### Available error classes

- `ApiError`
- `ValidationApiError`
- `AuthenticationApiError`
- `AuthorizationApiError`
- `NotFoundApiError`

### Status mapping

- `400` with validation body → `ValidationApiError`
- `401` → `AuthenticationApiError`
- `403` → `AuthorizationApiError`
- `404` → `NotFoundApiError`
- anything else → generic `ApiError`

### Example

```ts
import {
  NotFoundApiError,
  ValidationApiError,
  createCapstoneSdk
} from "./src";

const sdk = createCapstoneSdk({ baseUrl: "http://127.0.0.1:8080" });

try {
  await sdk.itemUnits.create({ name: "gram" });
} catch (error) {
  if (error instanceof ValidationApiError) {
    console.log(error.message);
    console.log(error.errors);
  }

  if (error instanceof NotFoundApiError) {
    console.log(error.status);
  }
}
```

## Request typing rules

Several SDK request shapes intentionally mirror backend validation rules.

### Mutually exclusive lookup identifiers

Some create/update requests support either an ID field or a name field, but not both. The SDK models this with XOR-style types.

Examples:

- users: `role_id` **or** `role_name`
- items: `item_category_id` **or** `item_category_name`
- stock transactions: `type_id` **or** `type_name`

That means this is valid:

```ts
await sdk.users.create({
  name: "Gudang User",
  username: "gudang1",
  password: "password123",
  role_name: "gudang"
});
```

And this is intentionally invalid at the type level:

```ts
await sdk.users.create({
  name: "Broken",
  username: "broken",
  password: "password123",
  role_id: 3,
  role_name: "gudang"
});
```

## Resource reference

### `auth`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.auth.login(payload)` | `POST /api/v1/auth/login` | public |
| `sdk.auth.me()` | `GET /api/v1/auth/me` | authenticated |
| `sdk.auth.logout()` | `POST /api/v1/auth/logout` | authenticated |
| `sdk.auth.changePassword(payload)` | `PATCH /api/v1/auth/password` | authenticated |

#### Login request

```ts
await sdk.auth.login({
  username: "admin",
  password: "password123"
});
```

#### Login response shape

```ts
{
  message: string;
  access_token: string;
  token_type: "Bearer";
  user: User;
}
```

### `roles`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.roles.list(query?)` | `GET /api/v1/roles` | `admin` only |

`roles` is currently a read-only lookup resource in the SDK.

### `itemCategories`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.itemCategories.list(query?)` | `GET /api/v1/item-categories` | `admin`, `gudang` |
| `sdk.itemCategories.get(id)` | `GET /api/v1/item-categories/{id}` | `admin`, `gudang` |
| `sdk.itemCategories.create(payload)` | `POST /api/v1/item-categories` | `admin` only |
| `sdk.itemCategories.update(id, payload)` | `PUT /api/v1/item-categories/{id}` | `admin` only |
| `sdk.itemCategories.delete(id)` | `DELETE /api/v1/item-categories/{id}` | `admin` only |
| `sdk.itemCategories.restore(id)` | `PATCH /api/v1/item-categories/{id}/restore` | `admin` only |

#### Soft delete and restore behavior

- names are unique only among active rows
- create/update still reject active duplicates
- if a deleted row already owns the same normalized name, create returns a validation error with restore guidance and `restore_id`
- the client should call `restore()` explicitly instead of retrying create

### `itemUnits`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.itemUnits.list(query?)` | `GET /api/v1/item-units` | `admin`, `gudang` |
| `sdk.itemUnits.get(id)` | `GET /api/v1/item-units/{id}` | `admin`, `gudang` |
| `sdk.itemUnits.create(payload)` | `POST /api/v1/item-units` | `admin` only |
| `sdk.itemUnits.update(id, payload)` | `PUT /api/v1/item-units/{id}` | `admin` only |
| `sdk.itemUnits.delete(id)` | `DELETE /api/v1/item-units/{id}` | `admin` only |
| `sdk.itemUnits.restore(id)` | `PATCH /api/v1/item-units/{id}/restore` | `admin` only |

#### Soft delete and restore behavior

- names are unique only among active rows
- delete is blocked while active items still reference the unit
- if create hits a deleted-name collision, the API responds with `400`, `errors.name`, and `errors.restore_id`
- restore is explicit; the SDK now exposes it directly

### `transactionTypes`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.transactionTypes.list(query?)` | `GET /api/v1/transaction-types` | `admin`, `gudang` |

### `approvalStatuses`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.approvalStatuses.list(query?)` | `GET /api/v1/approval-statuses` | `admin`, `gudang` |

### `items`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.items.list(query?)` | `GET /api/v1/items` | `admin`, `gudang` |
| `sdk.items.get(id)` | `GET /api/v1/items/{id}` | `admin`, `gudang` |
| `sdk.items.create(payload)` | `POST /api/v1/items` | `admin`, `gudang` |
| `sdk.items.update(id, payload)` | `PUT /api/v1/items/{id}` | `admin`, `gudang` |
| `sdk.items.delete(id)` | `DELETE /api/v1/items/{id}` | `admin` only |
| `sdk.items.restore(id)` | `PATCH /api/v1/items/{id}/restore` | `admin` only |

#### Important item behavior

- `qty` is backend-controlled and is **not** a writable request field
- `unit_base` and `unit_convert` are still sent as strings on write
- item responses also include `item_unit_base_id`, `item_unit_convert_id`, and nested `item_unit_base` / `item_unit_convert`
- item names remain globally unique even after soft delete
- creating an item with the name of a deleted item returns `400` with `errors.restore_id`
- restore is explicit through `sdk.items.restore(id)` and is idempotent when the item is already active
- restore also returns `400` if the item's category or units are no longer active

#### Example item response shape

```ts
{
  id: 1,
  item_category_id: 2,
  name: "Beras",
  unit_base: "gram",
  unit_convert: "kg",
  item_unit_base_id: 1,
  item_unit_convert_id: 2,
  conversion_base: 1000,
  qty: "1500.00",
  is_active: true,
  created_at: "2026-04-01 10:00:00",
  updated_at: "2026-04-01 10:00:00",
  category: {
    id: 2,
    name: "KERING"
  },
  item_unit_base: {
    id: 1,
    name: "gram"
  },
  item_unit_convert: {
    id: 2,
    name: "kg"
  }
}
```

### `stockTransactions`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.stockTransactions.list(query?)` | `GET /api/v1/stock-transactions` | `admin`, `gudang` |
| `sdk.stockTransactions.get(id)` | `GET /api/v1/stock-transactions/{id}` | `admin`, `gudang` |
| `sdk.stockTransactions.details(id)` | `GET /api/v1/stock-transactions/{id}/details` | `admin`, `gudang` |
| `sdk.stockTransactions.create(payload)` | `POST /api/v1/stock-transactions` | `admin`, `gudang` |
| `sdk.stockTransactions.directCorrection(payload)` | `POST /api/v1/stock-transactions/direct-corrections` | `admin` only |
| `sdk.stockTransactions.submitRevision(id, payload)` | `POST /api/v1/stock-transactions/{id}/submit-revision` | `admin`, `gudang` |
| `sdk.stockTransactions.approve(id)` | `POST /api/v1/stock-transactions/{id}/approve` | `admin` only |
| `sdk.stockTransactions.reject(id)` | `POST /api/v1/stock-transactions/{id}/reject` | `admin` only |

#### Important stock transaction behavior

- list search uses `q` / `search` against `spk_id`
- list filters support `type_id`, `status_id`, `transaction_date_from/to`, `created_at_from/to`, `updated_at_from/to`
- create supports `type_id` or `type_name`
- direct correction is admin-only and requires `item_id`, `expected_current_qty`, `target_qty`, and `reason`
- direct correction is stored as a normal stock transaction (not a revision), with the server deriving whether the adjustment is `IN` or `OUT`
- submit revision only creates a pending child revision; it does not change stock immediately
- approve revision applies the revision as a **net correction** against the parent transaction's stock effect, not as a second additive stock movement
- detail rows still use `item_id`; there is no item-name write shortcut in transaction details
- there is intentionally no `sdk.stockTransactions.delete()` method because the backend exposes no delete route for stock transactions

### `users`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.users.list(query?)` | `GET /api/v1/users` | `admin` only |
| `sdk.users.get(id)` | `GET /api/v1/users/{id}` | `admin` only |
| `sdk.users.create(payload)` | `POST /api/v1/users` | `admin` only |
| `sdk.users.update(id, payload)` | `PUT /api/v1/users/{id}` | `admin` only |
| `sdk.users.activate(id)` | `PATCH /api/v1/users/{id}/activate` | `admin` only |
| `sdk.users.deactivate(id)` | `PATCH /api/v1/users/{id}/deactivate` | `admin` only |
| `sdk.users.changePassword(id, payload)` | `PATCH /api/v1/users/{id}/password` | `admin` only |
| `sdk.users.delete(id)` | `DELETE /api/v1/users/{id}` | `admin` only |
| `sdk.users.restore(id)` | `PATCH /api/v1/users/{id}/restore` | `admin` only |

#### Important user behavior

- usernames remain globally unique even after soft delete
- `is_active` controls application-level activation status
- soft delete revokes access tokens and makes the user effectively absent from active reads/mutations
- create/update accept `role_id` or `role_name`
- creating a user with the username of a deleted user returns `400` with `errors.restore_id`
- restore is explicit through `sdk.users.restore(id)` and is idempotent when the user is already active
- restore also returns `400` if the user's assigned role is no longer active

### `dailyPatients`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.dailyPatients.list()` | `GET /api/v1/daily-patients` | `admin`, `gudang` |
| `sdk.dailyPatients.get(id)` | `GET /api/v1/daily-patients/{id}` | `admin`, `gudang` |
| `sdk.dailyPatients.create(payload)` | `POST /api/v1/daily-patients` | `admin`, `dapur` |

### `spk`

| SDK method | HTTP endpoint | Access |
|---|---|---|
| `sdk.spk.generateBasah(payload)` | `POST /api/v1/spk/basah/generate` | `admin`, `dapur` |
| `sdk.spk.listBasah()` | `GET /api/v1/spk/basah/history` | `admin`, `gudang` |
| `sdk.spk.getBasah(id)` | `GET /api/v1/spk/basah/history/{id}` | `admin`, `gudang` |
| `sdk.spk.postBasahStock(id)` | `POST /api/v1/spk/basah/history/{id}/post-stock` | `admin` only |
| `sdk.spk.generateKeringPengemas(payload)` | `POST /api/v1/spk/kering-pengemas/generate` | `admin`, `dapur` |
| `sdk.spk.listKeringPengemas()` | `GET /api/v1/spk/kering-pengemas/history` | `admin`, `gudang` |
| `sdk.spk.getKeringPengemas(id)` | `GET /api/v1/spk/kering-pengemas/history/{id}` | `admin`, `gudang` |
| `sdk.spk.postKeringPengemasStock(id)` | `POST /api/v1/spk/kering-pengemas/history/{id}/post-stock` | `admin` only |

#### SPK Recommendation logic

- **Basah:** `((daily_patients * 1.05) * composition) - stock`. Covers today and day+1 within the same month.
- **Kering/Pengemas:** `(prev_month_actual_usage * 1.10) - stock`.
- Recommendations are clamped to 0 (no negative values).
- **Important:** SPK generation endpoints are calculation helpers; they do not mutate stock. Stock changes must be explicitly saved via `stockTransactions.create`.

### `menus` / `dishes` / `dishCompositions` / `menuSchedules`

These resources provide management for nutrition standards and calendar scheduling.

| Resource | Methods | Access (Write) | Access (Read) |
|---|---|---|---|
| `menus` | `list` | None (Fixed) | `admin`, `gudang` |
| `menus` (slots) | `slots`, `assignSlot`, `updateSlot`, `deleteSlot` | `admin`, `dapur` | `admin`, `gudang` |
| `dishes` | `list`, `get`, `create`, `update`, `delete` | `admin`, `dapur` | `admin`, `gudang` |
| `dishCompositions` | `list`, `get`, `create`, `update`, `delete` | `admin`, `dapur` | `admin`, `gudang` |
| `menuSchedules` | `list`, `get`, `create`, `update`, `calendarProjection` | `admin`, `dapur` | `admin`, `gudang` |

### `dashboard` / `reports` / `stockOpnames`

These resources provide analytical views and auditing tools.

| Resource | Methods | Access |
|---|---|---|
| `dashboard` | `getAggregate` | `admin`, `dapur`, `gudang` |
| `reports` | `getStocks`, `getTransactions`, `getSpkHistory`, `getEvaluation` | `admin`, `gudang` |
| `stockOpnames` | `create`, `get`, `submit`, `approve`, `reject`, `post` | `admin`, `gudang` |

## List query reference

Most collection endpoints return paginated envelopes and accept resource-specific filters.

### Shared paginated lookup query

Used by:

- `roles.list()`
- `itemCategories.list()`
- `itemUnits.list()`
- `transactionTypes.list()`
- `approvalStatuses.list()`

Supported fields:

- `paginate` — optional boolean; use `false` for dropdown-style lookup reads
- `page`
- `perPage`
- `q`
- `search`
- `sortBy`
- `sortDir`
- `created_at_from`
- `created_at_to`
- `updated_at_from`
- `updated_at_to`

Rules:

- unknown lookup query parameters return `400` validation errors
- if both `q` and `search` are sent, backend behavior gives precedence to `q`

### `items.list(query)`

Supported fields:

- `page`
- `perPage`
- `item_category_id`
- `is_active`
- `q`
- `search`
- `sortBy`
- `sortDir`
- `created_at_from`
- `created_at_to`
- `updated_at_from`
- `updated_at_to`

### `users.list(query)`

Supported fields:

- `page`
- `perPage`
- `q`
- `search`
- `sortBy`
- `sortDir`
- `role_id`
- `is_active`
- `created_at_from`
- `created_at_to`
- `updated_at_from`
- `updated_at_to`

### `stockTransactions.list(query)`

Supported fields:

- `page`
- `perPage`
- `q`
- `search`
- `sortBy`
- `sortDir`
- `type_id`
- `status_id`
- `transaction_date_from`
- `transaction_date_to`
- `created_at_from`
- `created_at_to`
- `updated_at_from`
- `updated_at_to`

## Practical examples

### Full auth flow

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
await sdk.auth.logout();
sdk.clearAccessToken();
```

### List items with filters

```ts
const items = await sdk.items.list({
  page: 1,
  perPage: 20,
  q: "beras",
  item_category_id: 2,
  is_active: true,
  sortBy: "updated_at",
  sortDir: "DESC",
  created_at_from: "2026-04-01",
  updated_at_to: "2026-04-30"
});
```

### Create an item using category-name lookup

```ts
const createdItem = await sdk.items.create({
  name: "Minyak",
  item_category_name: "PENGEMAS",
  unit_base: "ml",
  unit_convert: "liter",
  conversion_base: 1000,
  is_active: true
});
```

### Restore flow for a deleted lookup

```ts
import { ValidationApiError } from "./src";

try {
  await sdk.itemUnits.create({ name: "pack" });
} catch (error) {
  if (error instanceof ValidationApiError) {
    const restoreId = error.errors.restore_id;

    if (restoreId) {
      await sdk.itemUnits.restore(Number(restoreId));
    }
  }
}
```

### Dropdown lookup flow with `paginate=false`

```ts
const lookup = await sdk.itemUnits.list({
  paginate: false,
  sortBy: "name",
  sortDir: "ASC"
});

const options = lookup.data.map((unit) => ({
  value: unit.id,
  label: unit.name
}));

console.log(lookup.meta.paginated); // false
```

Even with `paginate=false`, lookup endpoints still return the same `data/meta/links` envelope.

### List users with admin-only filters

```ts
const users = await sdk.users.list({
  q: "gudang",
  role_id: 3,
  is_active: true,
  sortBy: "email",
  sortDir: "ASC"
});
```

### Stock transaction workflow

```ts
const created = await sdk.stockTransactions.create({
  type_name: "IN",
  transaction_date: "2026-04-18",
  spk_id: 12345,
  details: [
    {
      item_id: 1,
      qty: 5000,
      input_unit: "base"
    }
  ]
});

// Admin direct stock correction example
await sdk.stockTransactions.directCorrection({
  transaction_date: "2026-04-20",
  item_id: 1,
  expected_current_qty: 5000,
  target_qty: 4800,
  reason: "Found 200g damaged during audit"
});

const revision = await sdk.stockTransactions.submitRevision(created.data.id, {
  transaction_date: "2026-04-19",
  spk_id: 12345,
  details: [
    {
      item_id: 1,
      qty: 4500,
      input_unit: "base"
    }
  ]
});

await sdk.stockTransactions.approve(revision.data.id);
```


### Direct stock correction workflow

```ts
await sdk.stockTransactions.directCorrection({
  transaction_date: "2026-04-20",
  item_id: 1,
  expected_current_qty: 5000,
  target_qty: 4800,
  reason: "Found 200g damaged during audit"
});
```

In the workflow above, approval corrects the parent transaction's stock effect based on the difference between the parent details and the revision details. It does not replay the revision quantities as an additional standalone movement.

## End-to-end SDK flow example

A common operational flow involving patient input, SPK generation, and stock management:

### 1. Daily Setup & Patient Input
```ts
import { createCapstoneSdk } from "./src";
const sdk = createCapstoneSdk({ baseUrl: "http://127.0.0.1:8080" });

// Authenticate
const login = await sdk.auth.login({ username: "dapur1", password: "password123" });
sdk.setAccessToken(login.access_token);

// Input daily patients (canonical source for SPK Basah)
const patients = await sdk.dailyPatients.create({
  service_date: "2026-04-14",
  meal_time: "SIANG",
  total_patients: 120
});
```

### 2. SPK Generation (Calculation Helper)
```ts
// Generate SPK Basah recommendation
const spk = await sdk.spk.generateBasah({
  daily_patient_id: patients.data.id,
  target_date: "2026-04-14",
  category_id: 1 // BASAH
});

// spk.data.recommendations contains items with:
// qty = ((120 * 1.05) * composition) - stock (clamped to 0)
```

### 3. Stock Mutation (Authoritative Action)
The UI may allow overriding quantities before finalizing. Once ready, the stock mutation must be explicitly saved.

```ts
// Record stock IN from external purchase based on SPK
await sdk.stockTransactions.create({
  type_name: "IN",
  transaction_date: "2026-04-14",
  spk_id: spk.data.id,
  details: [
    {
      item_id: 1, // Rice
      qty: 5000,   // 5kg (base unit)
      input_unit: "base"
    }
  ]
});

// Record stock OUT based on menu projection (Drafted from menu helper in UI)
await sdk.stockTransactions.create({
  type_name: "OUT",
  transaction_date: "2026-04-14",
  details: [
    {
      item_id: 1,
      qty: 4800,
      input_unit: "base"
    }
  ]
});
```

### 4. History and Printing
```ts
// Fetch historical SPK for printing
const history = await sdk.spk.getBasah(spk.data.id);
// Use history.data for rendering formal print documents
```

## Design rules kept by the SDK

- request DTOs are separate from response DTOs
- backend-managed fields are not exposed as writable request fields
- resource methods mirror real backend routes instead of inventing convenience contracts
- list responses preserve pagination metadata and links
- soft-delete restore behavior is explicit where the backend requires it

## Updating the SDK when the backend changes

Do not update the SDK by guessing from one controller or one doc.

Use the canonical backend discovery workflow in `../backend/AGENTS.md`.

Minimal SDK update read order:

1. `../backend/AGENTS.md`
2. `../backend/docs/architecture/runtime-status.md` (Canonical)
3. `../backend/docs/reference/api-contract.md` (Canonical)
4. matching backend code and feature tests
5. SDK source, SDK tests, then rebuild `dist/`

Supporting references:

- `../backend/AGENTS.md`
