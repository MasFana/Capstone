# TypeScript SDK Maintenance Guide

## Purpose

This guide explains how to create and update the frontend TypeScript SDK from the currently implemented CodeIgniter 4 API in this repository.

Use this guide whenever the backend API changes or when a new SDK resource module needs to be added.

## Contract sources of truth

Do not treat the SDK contract as a fixed list of files. Treat it as a discovery workflow that must be repeated whenever routes, controllers, services, or tests change.

Always resolve the SDK contract in this order:

1. **Live route inventory**
   - Start from `backend/app/Config/Routes.php`.
   - Build the current implemented endpoint list from active routes, not from memory.
   - If new route groups, versions, or modules are added later, include them here first.

2. **Project API documentation**
   - Read `backend/docs/api-design.md` for documented request/response examples and implemented-vs-planned status.
   - If the docs lag behind the code, treat that as a docs issue to update after verifying runtime behavior.

3. **Matching controllers for each active endpoint**
   - Read the controller files that actually implement the active routes under `backend/app/Controllers/Api/`.
   - Do not assume the current set is limited to `Auth`, `Roles`, `Items`, `StockTransactions`, and `Users`; that is only the current snapshot.

4. **Supporting services, models, filters, and helpers**
   - Read the matching files in `backend/app/Services/`, `backend/app/Models/`, `backend/app/Filters/`, and any helper or library layer used by the controller.
   - This is where forbidden fields, lookup-by-name behavior, auth rules, and response formatting often live.

5. **Feature tests and other executable contract checks**
   - Read the matching backend tests in `backend/tests/feature/Api/`.
   - If tests cover a contract edge case, prefer that runtime-verified behavior over assumptions.

6. **Config files that affect client behavior**
   - Read auth, filter, and API-related config when they change request handling.
   - Common examples: `backend/app/Config/Auth.php`, `backend/app/Config/AuthToken.php`, and `backend/app/Config/Filters.php`.

If the docs and runtime code disagree, prefer the implemented route/controller/service/test behavior and then update the docs.

### Flexible contract-discovery checklist

When a new backend feature is added, discover its SDK impact using this checklist:

1. Which new routes were added?
2. Which controller methods implement them?
3. Which services/models/filters shape request validation or responses?
4. Which request fields are writable, derived, forbidden, or mutually exclusive?
5. Which response envelopes are used for success and failure?
6. Which backend tests already prove the contract?
7. Which new SDK files, exports, and tests are required?

## Verified API conventions

- API base path: `/api/v1`
- Auth header: `Authorization: Bearer <token>`
- Implemented resources: `auth`, `roles`, `item-categories`, `transaction-types`, `approval-statuses`, `item-units`, `items`, `stock-transactions`, `users`
- List envelope: `{ data, meta, links }`
- Lookup list endpoints may also accept `paginate=false`, but they still return the same `{ data, meta, links }` envelope.
- Single-resource envelope: `{ data }`
- Mutation success envelope: `{ message, data }` or `{ message }`
- Auth login success envelope: `{ message, access_token, token_type, user }`
- Validation failures: `{ message, errors }`

Current caveat: not every failing response with an `errors` field is a keyed validation object. Some stock-transaction failures currently return `errors: []`, so SDK error handling must only treat keyed error objects as validation-field errors.

## Verified actor access matrix

Use these access notes when documenting SDK methods or deciding whether a frontend surface should expose an action to a given actor.

### Auth

- `POST /auth/login` — public, no authenticated actor required
- `GET /auth/me` — any authenticated actor: `admin`, `dapur`, `gudang`
- `POST /auth/logout` — any authenticated actor: `admin`, `dapur`, `gudang`

### Roles

- `GET /roles` — `admin` only

### Lookup resources

- `GET /item-categories` — `admin`, `gudang`
- `GET /item-categories/{id}` — `admin`, `gudang`
- `POST /item-categories` — `admin` only
- `PUT /item-categories/{id}` — `admin` only
- `DELETE /item-categories/{id}` — `admin` only
- `PATCH /item-categories/{id}/restore` — `admin` only
- `GET /transaction-types` — `admin`, `gudang`
- `GET /approval-statuses` — `admin`, `gudang`
- `GET /item-units` — `admin`, `gudang`
- `GET /item-units/{id}` — `admin`, `gudang`
- `POST /item-units` — `admin` only
- `PUT /item-units/{id}` — `admin` only
- `DELETE /item-units/{id}` — `admin` only
- `PATCH /item-units/{id}/restore` — `admin` only

### Users

- all current `/users` endpoints — `admin` only

### Items

- `GET /items` — `admin`, `gudang`
- `GET /items/{id}` — `admin`, `gudang`
- `POST /items` — `admin`, `gudang`
- `PUT /items/{id}` — `admin`, `gudang`
- `DELETE /items/{id}` — `admin` only

### Stock transactions

- `GET /stock-transactions` — `admin`, `gudang`
- `GET /stock-transactions/{id}` — `admin`, `gudang`
- `GET /stock-transactions/{id}/details` — `admin`, `gudang`
- `POST /stock-transactions` — `admin`, `gudang`
- `POST /stock-transactions/{id}/submit-revision` — `admin`, `gudang`
- `POST /stock-transactions/{id}/approve` — `admin` only
- `POST /stock-transactions/{id}/reject` — `admin` only

### Documentation rule

When adding a new SDK method or README entry, include its actor access note if the route is not public.

Derive actor access from the route filters in `backend/app/Config/Routes.php`, then cross-check with `backend/docs/api-design.md`.

## SDK file layout

The frontend SDK lives in `frontend/src/sdk/`.

```
frontend/src/sdk/
  client.ts
  errors.ts
  index.ts
  resources/
    approvalStatuses.ts
    auth.ts
    itemCategories.ts
    items.ts
    itemUnits.ts
    roles.ts
    stockTransactions.ts
    transactionTypes.ts
    users.ts
  tests/
  types/
    auth.ts
    common.ts
    index.ts
    items.ts
    lookups.ts
    roles.ts
    stockTransactions.ts
    users.ts
```

## Design rules

### 1. Separate read models from write models

Never reuse response DTOs as request DTOs.

Examples:

- item responses include `qty`, `created_at`, and `updated_at`
- item write requests must not include those backend-managed fields
- stock transaction responses include approval and revision fields
- stock transaction write requests must not include those backend-managed fields

### 2. Preserve mutually exclusive lookup inputs

Several endpoints accept either an ID or a lookup name.

Represent that in TypeScript using mutually exclusive unions:

- users: `role_id` or `role_name`
- items: `item_category_id` or `item_category_name`
- stock transactions: `type_id` or `type_name`

Never allow both variants in the same request type.

### 3. Keep auth in the shared client

Bearer token handling belongs in `client.ts`, not in each resource file.

The shared client is responsible for:

- base URL handling
- `/api/v1` prefix handling
- JSON request serialization
- JSON response parsing
- Authorization header injection
- typed error mapping

### 4. Preserve server envelopes

Do not flatten server responses into ad hoc shapes.

Keep the SDK return types aligned to backend contracts:

- `ApiDataResponse<T>`
- `ApiListResponse<T>`
- `ApiMessageResponse`
- `ApiMessageDataResponse<T>`

## Current endpoint inventory

### Auth

- `POST /auth/login`
- `GET /auth/me`
- `POST /auth/logout`

### Roles

- `GET /roles`

### Lookup resources

- `GET /item-categories`
- `GET /item-categories/{id}`
- `POST /item-categories`
- `PUT /item-categories/{id}`
- `DELETE /item-categories/{id}`
- `PATCH /item-categories/{id}/restore`
- `GET /transaction-types`
- `GET /approval-statuses`
- `GET /item-units`
- `GET /item-units/{id}`
- `POST /item-units`
- `PUT /item-units/{id}`
- `DELETE /item-units/{id}`
- `PATCH /item-units/{id}/restore`

Uniqueness policy notes:

- `users.username`, `roles.name`, `transaction_types.name`, and `approval_statuses.name` remain globally unique even after soft delete.
- `item_categories.name` and `item_units.name` are unique only among active rows.
- If a create request matches a deleted item category or item unit, the API returns `400` with a restore-focused validation error and `restore_id`; SDK/UI should call restore explicitly instead of retrying create.
- Lookup list endpoints also support `paginate=false` for dropdown usage. SDK callers should still read the result through `response.data`; do not introduce a bare-array return type.

### Items

- `GET /items`
- `POST /items`
- `GET /items/{id}`
- `PUT /items/{id}`
- `DELETE /items/{id}`

### Stock transactions

- `GET /stock-transactions`
- `POST /stock-transactions`
- `GET /stock-transactions/{id}`
- `GET /stock-transactions/{id}/details`
- `POST /stock-transactions/{id}/submit-revision`
- `POST /stock-transactions/{id}/approve`
- `POST /stock-transactions/{id}/reject`

### Users

- `GET /users`
- `POST /users`
- `GET /users/{id}`
- `PUT /users/{id}`
- `PATCH /users/{id}/activate`
- `PATCH /users/{id}/deactivate`
- `PATCH /users/{id}/password`
- `DELETE /users/{id}`

## Forbidden-field reminders

### Items

Do not expose these as writable request fields:

- `qty`
- `id`
- `created_at`
- `updated_at`
- `deleted_at`

### Stock transactions

Do not expose these as writable request fields:

- `user_id`
- `approved_by`
- `approval_status_id`
- `is_revision`
- `parent_transaction_id`
- `created_at`
- `updated_at`
- `deleted_at`

## Update workflow

When the backend API changes, update the SDK in this order:

1. Read `Routes.php` and confirm the live endpoint surface.
2. Read `api-design.md` and the matching controllers/services.
3. Update shared types in `frontend/src/sdk/types/`.
4. Update or add resource methods in `frontend/src/sdk/resources/`.
5. Update tests in `frontend/src/sdk/tests/`.
6. Run `npm test` and `npm run build` inside `frontend/`.

## Checklist for adding a new endpoint

1. Confirm the endpoint is implemented, not only planned.
2. Add or update request/response types.
3. Add the resource method with JSDoc.
4. Add or update resource tests.
5. Re-export the new API surface from `frontend/src/sdk/index.ts` and `frontend/src/index.ts`.
6. Re-run verification.

## Anti-patterns

Do not:

- infer request shapes from response payloads
- include backend-managed fields in writable request DTOs
- hardcode token storage in resource modules
- implement planned endpoints that do not exist in `Routes.php`
- assume OpenAPI generation exists in this project today

## Migration path if OpenAPI is added later

If the backend later ships an OpenAPI specification, generated code should be placed in a separate generated area and wrapped by the current stable SDK surface instead of replacing the public imports directly.

Keep the current handwritten SDK as the contract-stable layer until generated output proves equivalent.
