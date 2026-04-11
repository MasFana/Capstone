# API Design — Sistem Informasi Manajemen Gudang dan SPK Instalasi Gizi RSD Balung

## Quick Router

- **Canonical for:** active runtime API contract, implemented-vs-planned endpoint inventory, and request/response behavior.
- **Read this when:** you are implementing or consuming a live backend endpoint or SDK surface.
- **Read next:** `docs/project-flow-alignment.md` for the compact module/status index and `docs/data-dictionary.md` for schema-backed rules.
- **Not canonical for:** target architecture decisions for modules that are still planned.

## 1. Overview

Dokumen ini mendefinisikan rancangan API untuk backend **CodeIgniter 4 + MySQL** yang sudah diselaraskan dengan DB diagram terbaru.

Dokumen ini sekarang dipisahkan menjadi dua status yang jelas:

- **Implemented** = endpoint yang benar-benar sudah ada di `app/Config/Routes.php` dan sudah didukung kode backend saat ini.
- **Planned** = endpoint yang masih merupakan target desain dan belum tersedia sebagai route aktif.

Source of truth untuk endpoint yang sudah berjalan adalah:

- `app/Config/Routes.php`
- controller di `app/Controllers/Api/V1/`
- feature tests di `tests/feature/Api/V1/`

Untuk indeks ringkas lintas modul yang merangkum status runtime, surface API, flow utama, ringkasan query/request, dan akses per modul, lihat `docs/project-flow-alignment.md` bagian **4.2 Compact Runtime Cross-Reference Matrix**.

## 2. API Principles

- semua endpoint menggunakan prefix `/api/v1`;
- gunakan plural resource names;
- gunakan endpoint workflow untuk proses approval, revision, dan generate SPK;
- response JSON konsisten;
- tabel yang memiliki `deleted_at` diperlakukan sebagai soft-delete resources.

## 3. Standard Response Shape

### 3.1 Success — single resource

```json
{
  "data": {
    "id": 1
  }
}
```

### 3.2 Success — list resource

```json
{
  "data": [],
  "meta": {
    "page": 1,
    "perPage": 10,
    "total": 0,
    "totalPages": 0
  },
  "links": {
    "self": "/api/v1/items?page=1&perPage=10",
    "first": "/api/v1/items?page=1&perPage=10",
    "last": "/api/v1/items?page=1&perPage=10",
    "next": null,
    "previous": null
  }
}
```

### 3.3 Validation error

```json
{
  "message": "Validation failed",
  "errors": {
    "field": "The field is invalid."
  }
}
```

## 4. Authentication Notes

Authentication runtime contract is documented in the implemented API surface under **5.1 Authentication & Access Endpoints**. Bagian ini hanya menyimpan contoh login dan format auth header yang dipakai lintas endpoint.

### 4.1 Login Example

#### Request

```json
{
  "username": "admin",
  "password": "password123"
}
```

#### Response

```json
{
  "message": "Login successful.",
  "access_token": "<token>",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "role_id": 1,
    "name": "Admin User",
    "username": "admin",
    "is_active": true,
    "role": {
      "id": 1,
      "name": "admin"
    }
  }
}
```

### 4.2 Auth Header

Protected endpoints require:

```http
Authorization: Bearer <access_token>
```

## 5. Implemented API Surface

Bagian ini hanya berisi endpoint yang saat ini benar-benar tersedia sebagai route aktif.

### 5.1 Authentication & Access Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/auth/login` | Login user with `username` and `password`, returns Bearer token |
| POST | `/api/v1/auth/logout` | Logout current Bearer token |
| GET | `/api/v1/auth/me` | Get current user profile from Bearer token |
| PATCH | `/api/v1/auth/password` | Self-service password change (requires valid token and current password, revokes all tokens) |
| GET | `/api/v1/roles` | List roles (paginated), restricted to `admin` via role filter |

#### 5.1.1 Self-Service Password Change

Authenticated users can change their own password. This endpoint requires the user's current password for verification and a new password. All access tokens are revoked after a successful password change.

**Access:** Requires valid Bearer token (any authenticated user)

##### Request

```json
{
  "current_password": "password123",
  "password": "newpassword123"
}
```

##### Response (Success)

```json
{
  "message": "Password changed successfully. All access tokens have been revoked."
}
```

##### Response (Wrong Current Password)

```json
{
  "message": "Current password is incorrect."
}
```

##### Response (Validation Failure)

```json
{
  "message": "Validation failed.",
  "errors": {
    "current_password": "The current_password field is required.",
    "password": "The password field must be at least 8 characters in length."
  }
}
```

### 5.2 Inventory Lookup Endpoints

These endpoints provide reference data for creating and filtering inventory operations. All lookup list endpoints are restricted to users with `admin` or `gudang` roles. Write operations on `item-units` and `item-categories` are restricted to `admin` only.

All lookup list endpoints support pagination by default and return the standard `data/meta/links` envelope. Soft-deleted rows are excluded from all list and show responses.

Supported query parameters for all lookup list endpoints:

- `paginate` — optional boolean; default `true`. Use `paginate=false` for dropdown-style reads that should return all matching rows while keeping the same `data/meta/links` envelope.
- `page` — page number (positive integer, default `1`)
- `perPage` — results per page (integer 1–100, default `10`)
- `q` / `search` — partial name match (case-insensitive); `q` takes priority if both are sent
- `sortBy`, `sortDir` — allowlisted sorting fields per resource
- `created_at_from`, `created_at_to` — created-at date/datetime range
- `updated_at_from`, `updated_at_to` — updated-at date/datetime range
- Unknown query parameters return `400` validation errors.
- If `paginate=false`, the endpoint still returns `data/meta/links`; `meta.paginated=false`, `page=1`, `totalPages=1` (or `0` for empty results), and `next/previous=null`.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/item-categories` | List item categories (paginated) |
| GET | `/api/v1/item-categories/{id}` | Get item category detail |
| POST | `/api/v1/item-categories` | Create item category |
| PUT | `/api/v1/item-categories/{id}` | Update item category |
| DELETE | `/api/v1/item-categories/{id}` | Soft delete item category |
| PATCH | `/api/v1/item-categories/{id}/restore` | Restore soft-deleted item category |
| GET | `/api/v1/transaction-types` | List transaction types (paginated) |
| GET | `/api/v1/approval-statuses` | List approval statuses (paginated) |
| GET | `/api/v1/item-units` | List item units (paginated) |
| GET | `/api/v1/item-units/{id}` | Get item unit detail |
| POST | `/api/v1/item-units` | Create item unit |
| PUT | `/api/v1/item-units/{id}` | Update item unit |
| DELETE | `/api/v1/item-units/{id}` | Soft delete item unit |
| PATCH | `/api/v1/item-units/{id}/restore` | Restore soft-deleted item unit |

#### 5.2.1 Item Categories

**Access:** `admin`, `gudang`

##### Response

```json
{
  "data": [
    {
      "id": 1,
      "name": "BASAH",
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00"
    },
    {
      "id": 2,
      "name": "KERING",
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00"
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 10,
    "total": 3,
    "totalPages": 1
  },
  "links": {
    "self": "/api/v1/item-categories?page=1&perPage=10",
    "first": "/api/v1/item-categories?page=1&perPage=10",
    "last": "/api/v1/item-categories?page=1&perPage=10",
    "next": null,
    "previous": null
  }
}
```

##### Response with `paginate=false`

```json
{
  "data": [
    {
      "id": 1,
      "name": "BASAH",
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00"
    },
    {
      "id": 2,
      "name": "KERING",
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00"
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 2,
    "total": 2,
    "totalPages": 1,
    "paginated": false
  },
  "links": {
    "self": "/api/v1/item-categories?paginate=false",
    "first": "/api/v1/item-categories?paginate=false",
    "last": "/api/v1/item-categories?paginate=false",
    "next": null,
    "previous": null
  }
}
```

#### 5.2.2 Transaction Types

**Access:** `admin`, `gudang`

##### Response

```json
{
  "data": [
    {
      "id": 1,
      "name": "IN",
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00"
    },
    {
      "id": 2,
      "name": "OUT",
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00"
    },
    {
      "id": 3,
      "name": "RETURN_IN",
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00"
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 10,
    "total": 3,
    "totalPages": 1
  },
  "links": {
    "self": "/api/v1/transaction-types?page=1&perPage=10",
    "first": "/api/v1/transaction-types?page=1&perPage=10",
    "last": "/api/v1/transaction-types?page=1&perPage=10",
    "next": null,
    "previous": null
  }
}
```

#### 5.2.3 Approval Statuses

**Access:** `admin`, `gudang`

##### Response

```json
{
  "data": [
    {
      "id": 1,
      "name": "APPROVED",
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00"
    },
    {
      "id": 2,
      "name": "PENDING",
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00"
    },
    {
      "id": 3,
      "name": "REJECTED",
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00"
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 10,
    "total": 3,
    "totalPages": 1
  },
  "links": {
    "self": "/api/v1/approval-statuses?page=1&perPage=10",
    "first": "/api/v1/approval-statuses?page=1&perPage=10",
    "last": "/api/v1/approval-statuses?page=1&perPage=10",
    "next": null,
    "previous": null
  }
}
```

#### 5.2.4 Item Units

**List / Show access:** `admin`, `gudang`
**Create / Update / Delete access:** `admin` only

`item_units` is a soft-deletable lookup table used as FK backing for item units. Soft-deleted item units are excluded from list and show responses, cannot be assigned to items, and delete is blocked while active items still reference the unit.

##### List Response

```json
{
  "data": [
    {
      "id": 1,
      "name": "gram",
      "created_at": "2026-04-10 08:00:00",
      "updated_at": "2026-04-10 08:00:00"
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 10,
    "total": 6,
    "totalPages": 1
  },
  "links": {
    "self": "/api/v1/item-units?page=1&perPage=10",
    "first": "/api/v1/item-units?page=1&perPage=10",
    "last": "/api/v1/item-units?page=1&perPage=10",
    "next": null,
    "previous": null
  }
}
```

`paginate=false` is supported here as well for dropdown-style consumers; the response keeps the same envelope and sets `meta.paginated=false`.

##### Show Response

```json
{
  "data": {
    "id": 1,
      "name": "gram",
    "created_at": "2026-04-10 08:00:00",
    "updated_at": "2026-04-10 08:00:00"
  }
}
```

##### Create Request

```json
{
  "name": "gram"
}
```

Name is stored as provided. Case-insensitive duplicate detection applies (e.g. `gram` and `GRAM` are considered the same normalized name).

##### Create Response (`201`)

```json
{
  "message": "Item unit created successfully.",
  "data": {
    "id": 7,
      "name": "gram",
    "created_at": "2026-04-10 08:00:00",
    "updated_at": "2026-04-10 08:00:00"
  }
}
```

##### Update Request

```json
{
  "name": "kilogram"
}
```

##### Update Response

```json
{
  "message": "Item unit updated successfully.",
  "data": {
    "id": 2,
      "name": "kilogram",
    "created_at": "2026-04-10 08:00:00",
    "updated_at": "2026-04-10 09:00:00"
  }
}
```

##### Delete Response

```json
{
  "message": "Item unit deleted successfully."
}
```

Item units are soft-deleted only. The row remains in the database with `deleted_at` set. `item_units.name` is unique only among active rows; if a matching deleted row exists, create returns `400` and the client must call the restore endpoint instead of recreating the name.

### 5.3 User Management Endpoints

All user management endpoints are restricted to users with the `admin` role.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/users` | List all users (excludes soft-deleted) |
| POST | `/api/v1/users` | Create new user |
| GET | `/api/v1/users/{id}` | Get user detail |
| PUT | `/api/v1/users/{id}` | Update user profile and role |
| PATCH | `/api/v1/users/{id}/activate` | Activate user account |
| PATCH | `/api/v1/users/{id}/deactivate` | Deactivate user account (blocks login) |
| PATCH | `/api/v1/users/{id}/password` | Change user password (revokes all tokens) |
| DELETE | `/api/v1/users/{id}` | Soft delete user (revokes all tokens) |
| PATCH | `/api/v1/users/{id}/restore` | Restore soft-deleted user |

#### 5.3.1 List Users

Supported query parameters:

- `page`, `perPage`
- `q` / `search` — partial name, username, or email match
- `sortBy`, `sortDir`
- `role_id` — filter by role ID
- `is_active` — filter by active status (`true` / `false` / `1` / `0`)
- `created_at_from`, `created_at_to`
- `updated_at_from`, `updated_at_to`
- Unknown query parameters return `400` validation errors.

#### Response

```json
{
  "data": [
    {
      "id": 1,
      "role_id": 1,
      "name": "Admin User",
      "username": "admin",
      "email": "admin@example.com",
      "is_active": true,
      "created_at": "2026-04-02 10:00:00",
      "updated_at": "2026-04-02 10:00:00",
      "role": {
        "id": 1,
        "name": "admin"
      }
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 10,
    "total": 3,
    "totalPages": 1
  },
  "links": {
    "self": "/api/v1/users?page=1&perPage=10",
    "first": "/api/v1/users?page=1&perPage=10",
    "last": "/api/v1/users?page=1&perPage=10",
    "next": null,
    "previous": null
  }
}
```

#### 5.3.2 Create User

Lookup contract:

- client may send either `role_id` or `role_name`;
- `role_name` matching is trimmed and case-insensitive;
- sending both `role_id` and `role_name` in the same request returns `400` validation errors.

#### Request

```json
{
  "role_id": 2,
  "name": "New User",
  "username": "newuser",
  "email": "newuser@example.com",
  "password": "password123",
  "is_active": true
}
```

#### Response

```json
{
  "message": "User created successfully.",
  "data": {
    "id": 4,
    "role_id": 2,
    "name": "New User",
    "username": "newuser",
    "email": "newuser@example.com",
    "is_active": true,
    "created_at": "2026-04-02 12:00:00",
    "updated_at": "2026-04-02 12:00:00",
    "role": {
      "id": 2,
      "name": "dapur"
    }
  }
}
```

#### 5.3.3 Update User

Lookup contract:

- update supports either `role_id` or `role_name` when changing the assigned role;
- `role_name` matching is trimmed and case-insensitive;
- sending both `role_id` and `role_name` in the same request returns `400` validation errors.

#### Request

```json
{
  "name": "Updated Name",
  "email": "updated@example.com",
  "role_id": 3
}
```

#### Response

```json
{
  "message": "User updated successfully.",
  "data": {
    "id": 4,
    "role_id": 3,
    "name": "Updated Name",
    "username": "newuser",
    "email": "updated@example.com",
    "is_active": true,
    "created_at": "2026-04-02 12:00:00",
    "updated_at": "2026-04-02 12:30:00",
    "role": {
      "id": 3,
      "name": "gudang"
    }
  }
}
```

#### 5.3.4 Deactivate User

Deactivated users cannot log in. Both `is_active` and `active` fields are set to `false`.

Soft-deleted users are treated as not found for all update, activate, deactivate, password-change, and delete operations.

#### Response

```json
{
  "message": "User deactivated successfully.",
  "data": {
    "id": 4,
    "is_active": false,
    ...
  }
}
```

#### 5.3.5 Admin Change Password

This endpoint is for administrators to change another user's password. Changing a user's password revokes all their access tokens. The user must log in again with the new password. Password updates use the Shield user entity flow so credential identity data stays synchronized.

#### Request

```json
{
  "password": "newpassword123"
}
```

#### Response

```json
{
  "message": "Password changed successfully. All access tokens have been revoked."
}
```

#### 5.3.6 Delete User

Soft deletes the user and revokes all their access tokens. The user cannot log in and will not appear in user lists.

#### Response

```json
{
  "message": "User deleted successfully."
}
```

#### 5.3.7 Restore User

Restores a soft-deleted user. Only `admin` can call this endpoint.

- If the user is already active, returns `200` with current data (idempotent).
- If an active user with the same username already exists, returns `400` with a `username` error.
- If the user's assigned role is no longer active, returns `400` with a `role_id` error.
- If the user does not exist at all, returns `404`.

Creating a new user with the username of a deleted user returns `400` with `errors.restore_id` pointing to the deleted user's ID.

#### Response

```json
{
  "message": "User restored successfully.",
  "data": {
    "id": 4,
    "username": "newuser",
    ...
  }
}
```

### 5.4 Item Endpoints

Phase 1 item management covers item master CRUD only. `qty` is read-only in this module and stock-related behavior stays in inventory transaction flows.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/items` | List items with pagination, filtering, and search |
| POST | `/api/v1/items` | Create item |
| GET | `/api/v1/items/{id}` | Get item detail |
| PUT | `/api/v1/items/{id}` | Partial update item |
| DELETE | `/api/v1/items/{id}` | Soft delete item |
| PATCH | `/api/v1/items/{id}/restore` | Restore soft-deleted item |

#### 5.4.1 Access Rules

- `admin` and `gudang` can list, create, view, and update items.
- `admin` only can soft delete or restore items.
- `dapur` has no access to item master management.

#### 5.4.2 List Items

Supported query parameters:

- `page`, `perPage`
- `item_category_id` — filter by category ID
- `is_active` — filter by active status (`true` / `false` / `1` / `0`)
- `q` / `search` — partial name match; `q` takes priority if both sent
- `sortBy` — column to sort by (allowed values: `name`, `created_at`, `updated_at`; default `name`)
- `sortDir` — sort direction: `ASC` or `DESC` (default `ASC`)
- `created_at_from`, `created_at_to` — date range filter on `created_at`
- `updated_at_from`, `updated_at_to` — date range filter on `updated_at`

Rules:

- unknown query parameters return `400` validation errors;
- default order is `name ASC`;
- soft-deleted items are excluded.

#### Response

```json
{
  "data": [
    {
      "id": 1,
      "item_category_id": 2,
      "name": "Beras",
      "unit_base": "gram",
      "unit_convert": "kg",
      "item_unit_base_id": 1,
      "item_unit_convert_id": 2,
      "conversion_base": 1000,
      "qty": "1500.00",
      "is_active": true,
      "created_at": "2026-04-03 10:00:00",
      "updated_at": "2026-04-03 10:00:00",
      "category": {
        "id": 2,
        "name": "KERING"
      },
      "item_unit_base": {
        "id": 1,
        "name": "gram"
      },
      "item_unit_convert": {
        "id": 2,
        "name": "kg"
      }
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 10,
    "total": 1,
    "totalPages": 1
  },
  "links": {
    "self": "/api/v1/items?page=1&perPage=10",
    "first": "/api/v1/items?page=1&perPage=10",
    "last": "/api/v1/items?page=1&perPage=10",
    "next": null,
    "previous": null
  }
}
```

#### 5.4.3 Create Item

Writable fields:

- `name`
- `item_category_id`
- `item_category_name`
- `unit_base`
- `unit_convert`
- `conversion_base`
- `is_active`

Lookup contract:

- client may send either `item_category_id` or `item_category_name`;
- `item_category_name` matching is trimmed and case-insensitive;
- sending both `item_category_id` and `item_category_name` in the same request returns `400` validation errors.

Unit resolution contract:

- `unit_base` and `unit_convert` are string values that are resolved to FK-backed `item_units` rows;
- resolution is case-insensitive;
- if the provided unit name cannot be matched to an active (non-deleted) item unit row, the request returns `400` with a `unit_base` or `unit_convert` error;
- the string values are still stored in `items.unit_base` / `items.unit_convert` for backward compatibility alongside the FK columns `item_unit_base_id` / `item_unit_convert_id`.

Forbidden write fields:

- `qty`
- `id`
- `created_at`
- `updated_at`
- `deleted_at`

#### Request

```json
{
  "name": "Minyak",
  "item_category_id": 3,
  "unit_base": "ml",
  "unit_convert": "liter",
  "conversion_base": 1000,
  "is_active": true
}
```

#### Response

```json
{
  "message": "Item created successfully.",
  "data": {
    "id": 3,
    "item_category_id": 3,
    "name": "Minyak",
    "unit_base": "ml",
    "unit_convert": "liter",
    "item_unit_base_id": 3,
    "item_unit_convert_id": 4,
    "conversion_base": 1000,
    "qty": "0.00",
    "is_active": true,
    "created_at": "2026-04-03 11:00:00",
    "updated_at": "2026-04-03 11:00:00",
    "category": {
      "id": 3,
      "name": "PENGEMAS"
    },
    "item_unit_base": {
      "id": 3,
      "name": "ml"
    },
    "item_unit_convert": {
      "id": 4,
      "name": "liter"
    }
  }
}
```

#### 5.4.4 Get Item Detail

#### Response

```json
{
  "data": {
    "id": 1,
    "item_category_id": 2,
    "name": "Beras",
    "unit_base": "gram",
    "unit_convert": "kg",
    "item_unit_base_id": 1,
    "item_unit_convert_id": 2,
    "conversion_base": 1000,
    "qty": "1500.00",
    "is_active": true,
    "created_at": "2026-04-03 10:00:00",
    "updated_at": "2026-04-03 10:00:00",
    "category": {
      "id": 2,
      "name": "KERING"
    },
    "item_unit_base": {
      "id": 1,
      "name": "gram"
    },
    "item_unit_convert": {
      "id": 2,
      "name": "kg"
    }
  }
}
```

#### 5.4.5 Update Item

`PUT /api/v1/items/{id}`` uses partial-update semantics in Phase 1. Only fields present in the request are validated and updated.

Lookup contract:

- update supports either `item_category_id` or `item_category_name` when changing category;
- `item_category_name` matching is trimmed and case-insensitive;
- sending both `item_category_id` and `item_category_name` in the same request returns `400` validation errors.

Unit resolution contract:

- if `unit_base` or `unit_convert` is present in the request, each is resolved to an active item unit row (same rules as create);
- if the unit name cannot be resolved to an active item unit, the request returns `400`.

#### Request

```json
{
  "name": "Beras Premium",
  "is_active": false
}
```

#### Response

```json
{
  "message": "Item updated successfully.",
  "data": {
    "id": 1,
    "item_category_id": 2,
    "name": "Beras Premium",
    "unit_base": "gram",
    "unit_convert": "kg",
    "item_unit_base_id": 1,
    "item_unit_convert_id": 2,
    "conversion_base": 1000,
    "qty": "1500.00",
    "is_active": false,
    "created_at": "2026-04-03 10:00:00",
    "updated_at": "2026-04-03 11:30:00",
    "category": {
      "id": 2,
      "name": "KERING"
    },
    "item_unit_base": {
      "id": 1,
      "name": "gram"
    },
    "item_unit_convert": {
      "id": 2,
      "name": "kg"
    }
  }
}
```

#### 5.4.6 Delete Item

#### Response

```json
{
  "message": "Item deleted successfully."
}
```

#### 5.4.7 Validation Notes

- `qty` cannot be created or updated directly through the item master endpoints.
- `item_category_id` must reference an existing category.
- `item_category_name` may be used instead of `item_category_id` and resolves to the same lookup table.
- `unit_base` and `unit_convert` must resolve to existing, active (non-deleted) `item_units` rows; soft-deleted item units are rejected with a `400` error.
- `name` must be globally unique across both active and deleted rows; if an active row owns the name, create/update returns `400` with a `name` error.
- if a deleted row already owns the same name, create returns `400` with `errors.restore_id` pointing to the deleted item's ID and a restore-guidance message.
- Missing or soft-deleted items return `404` for all show/update/delete operations.

#### 5.4.8 Restore Item

Restores a soft-deleted item. Only `admin` can call this endpoint.

- If the item is already active, returns `200` with current data (idempotent).
- If an active item with the same name already exists, returns `400` with a `name` error.
- If the referenced category is no longer active, returns `400` with an `item_category_id` error.
- If the referenced base or converted unit is no longer active, returns `400` with a `unit_base` or `unit_convert` error.
- If the item does not exist at all, returns `404`.

Creating a new item with the name of a deleted item returns `400` with `errors.restore_id` pointing to the deleted item's ID.

#### Response (restored)

```json
{
  "message": "Item restored successfully.",
  "data": {
    "id": 3,
    "item_category_id": 3,
    "name": "Minyak",
    "unit_base": "ml",
    "unit_convert": "liter",
    "item_unit_base_id": 3,
    "item_unit_convert_id": 4,
    "conversion_base": 1000,
    "qty": "0.00",
    "is_active": true,
    "created_at": "2026-04-03 11:00:00",
    "updated_at": "2026-04-03 12:00:00",
    "category": {
      "id": 3,
      "name": "PENGEMAS"
    },
    "item_unit_base": {
      "id": 3,
      "name": "ml"
    },
    "item_unit_convert": {
      "id": 4,
      "name": "liter"
    }
  }
}
```

#### Response (already active — idempotent 200)

```json
{
  "message": "Item restored successfully.",
  "data": { ... }
}
```

#### Response (active duplicate name — 400)

```json
{
  "message": "Validation failed.",
  "errors": {
    "name": "Cannot restore: an active item with this name already exists."
  }
}
```

#### 5.4.9 Deferred From Item Module

- `GET /api/v1/items/{id}/stock-summary`
- stock usage locking rules
- stock transaction integration

### 5.5 Inventory Transaction Endpoints

#### 5.5.1 Transactions

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/stock-transactions` | List stock transactions with pagination |
| POST | `/api/v1/stock-transactions` | Create stock transaction header + details |
| GET | `/api/v1/stock-transactions/{id}` | Get stock transaction header only |
| GET | `/api/v1/stock-transactions/{id}/details` | Get stock transaction item lines only |

#### 5.5.2 Access Rules

- `admin` dan `gudang` dapat mengakses endpoint transaksi stok Milestone 1.
- `dapur` tidak memiliki akses ke endpoint transaksi stok.
- Stock transactions intentionally have **no DELETE route**. Transactions are permanent audit records and cannot be soft-deleted or hard-deleted through the API. Any DELETE request to `/api/v1/stock-transactions/{id}` returns `404`.

#### 5.5.3 List Stock Transactions

Supported query parameters:

- `page`, `perPage`
- `q` / `search` — partial match on `spk_id`; `q` takes priority if both sent
- `sortBy` — column to sort by (allowed: `id`, `transaction_date`, `type_id`, `approval_status_id`, `created_at`, `updated_at`; default `transaction_date`)
- `sortDir` — sort direction: `ASC` or `DESC` (default `DESC`)
- `type_id` — filter by transaction type ID
- `status_id` — filter by approval status ID
- `transaction_date_from`, `transaction_date_to` — date range filter on `transaction_date`
- `created_at_from`, `created_at_to` — date range filter on `created_at`
- `updated_at_from`, `updated_at_to` — date range filter on `updated_at`

Rules:

- unknown query parameters return `400` validation errors;
- default order is `transaction_date DESC`, then transaction `id DESC`;
- soft-deleted transactions are excluded.

#### Response

```json
{
  "data": [
    {
      "id": 10,
      "type_id": 1,
      "transaction_date": "2026-04-18",
      "is_revision": false,
      "parent_transaction_id": null,
      "approval_status_id": 1,
      "approved_by": null,
      "user_id": 2,
      "spk_id": null,
      "created_at": "2026-04-18 08:00:00",
      "updated_at": "2026-04-18 08:00:00"
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 10,
    "total": 1,
    "totalPages": 1
  },
  "links": {
    "self": "/api/v1/stock-transactions?page=1&perPage=10",
    "first": "/api/v1/stock-transactions?page=1&perPage=10",
    "last": "/api/v1/stock-transactions?page=1&perPage=10",
    "next": null,
    "previous": null
  }
}
```

#### 5.5.4 Create Stock Transaction

Allowed request fields:

- `type_id`
- `type_name`
- `transaction_date`
- `spk_id`
- `details`

Lookup contract:

- client may send either `type_id` or `type_name`;
- `type_name` matching is trimmed and case-insensitive;
- sending both `type_id` and `type_name` in the same request returns `400` validation errors.

Allowed detail fields:

- `item_id`
- `qty`
- `input_unit` _(optional)_

**Unit conversion contract for detail rows:**

- `input_unit` is optional. Omitting it defaults to `"base"`.
- Allowed values: `"base"` or `"convert"`. Any other value returns `400`.
- `input_unit = "base"`: stored `qty` = request `qty` (no conversion).
- `input_unit = "convert"`: stored `qty` = request `qty` × `items.conversion_base`.
- `input_qty` (original request qty before normalization) is always persisted but is **not** a client-writable field.
- `stock_transaction_details.qty` always stores normalized base-unit qty.

Forbidden client-controlled fields:

- `user_id`
- `approved_by`
- `approval_status_id`
- `is_revision`
- `parent_transaction_id`
- `created_at`
- `updated_at`
- `deleted_at`

Rules:

- `user_id` diambil dari authenticated Bearer token context;
- transaksi normal dibuat dengan status approval bernama `APPROVED` dan `is_revision = false`;
- `spk_id` bersifat opsional / nullable;
- item yang sama tidak boleh muncul dua kali dalam satu request `details`;
- transaksi `OUT` ditolak jika akan membuat `items.qty` menjadi negatif;
- perubahan header, detail, qty, dan audit log ditulis dalam satu transaksi database.

#### Request

```json
{
  "type_id": 1,
  "transaction_date": "2026-04-02",
  "details": [
    {
      "item_id": 1,
      "qty": 5000
    }
  ]
}
```

Legacy request (no `input_unit`) — backward-compatible, qty stored as-is:

```json
{
  "type_name": "IN",
  "transaction_date": "2026-04-02",
  "details": [
    { "item_id": 1, "qty": 5000 }
  ]
}
```

Request with unit conversion — `qty` is in convert units (e.g. kg), stored as base units (g):

```json
{
  "type_name": "IN",
  "transaction_date": "2026-04-02",
  "details": [
    { "item_id": 1, "qty": 5, "input_unit": "convert" }
  ]
}
```

> Catatan: `user_id` diambil dari authenticated session/token context, bukan dikirim bebas oleh client.

#### Response

```json
{
  "message": "Stock transaction created successfully.",
  "data": {
    "id": 10,
    "approval_status_id": 1,
    "is_revision": false
  }
}
```

> Catatan: contoh `approval_status_id` di dokumen ini mengikuti seeded development baseline saat ini. Runtime code menetapkan status berdasarkan lookup nama `APPROVED`, bukan literal angka yang di-hardcode.

#### 5.5.5 Get Stock Transaction Header

`GET /api/v1/stock-transactions/{id}` hanya mengembalikan resource header transaksi, bukan item lines.

#### Response

```json
{
  "data": {
    "id": 10,
    "type_id": 1,
    "transaction_date": "2026-04-18",
    "is_revision": false,
    "parent_transaction_id": null,
    "approval_status_id": 1,
    "approved_by": null,
    "user_id": 2,
    "spk_id": null,
    "created_at": "2026-04-18 08:00:00",
    "updated_at": "2026-04-18 08:00:00"
  }
}
```

#### 5.5.6 Get Stock Transaction Details

`GET /api/v1/stock-transactions/{id}/details` hanya mengembalikan item lines transaksi.

#### Response

```json
{
  "data": [
    {
      "id": 1,
      "transaction_id": 10,
      "item_id": 1,
      "qty": "5000.00",
      "input_qty": "5.00",
      "input_unit": "convert"
    }
  ]
}
```

- `qty` — normalized base-unit quantity that was stored and used for stock mutation.
- `input_qty` — original quantity as submitted in the request.
- `input_unit` — `"base"` (default) or `"convert"` as submitted/defaulted.

#### 5.5.7 Revision Workflow Actions

Workflow revisi transaksi stok berikut sudah diimplementasikan setelah Milestone 1.

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/stock-transactions/{id}/submit-revision` | Submit revision against parent transaction |
| POST | `/api/v1/stock-transactions/{id}/approve` | Approve revision transaction |
| POST | `/api/v1/stock-transactions/{id}/reject` | Reject revision transaction |

#### Revision workflow rules

- submit revision dapat dilakukan oleh `admin` dan `gudang`;
- approve/reject revision hanya dapat dilakukan oleh `admin`;
- submit revision membuat child transaction dengan `is_revision = true` dan `approval_status_id = PENDING`;
- submit revision **tidak** mengubah `items.qty`;
- `items.qty` baru berubah ketika revision di-approve;
- reject revision tidak mengubah `items.qty`;
- parent transaction tetap dipertahankan sebagai histori asal.

## 6. Planned API Surface

Bagian ini berisi endpoint yang masih merupakan target desain dan **belum tersedia sebagai route aktif**.

### 6.1 Planned Lookup Endpoints

Endpoint berikut saat ini **belum tersedia** di `app/Config/Routes.php`, walaupun tabel lookup-nya sudah ada di schema:

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/meal-times` | List meal times |

### 6.2 Monthly Snapshot Endpoints

Endpoint berikut masih planned dan belum tersedia sebagai route aktif.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/monthly-stock-snapshots` | List monthly stock snapshots |
| POST | `/api/v1/monthly-stock-snapshots` | Create monthly stock snapshot |

### 6.3 Menu & Nutrition Endpoints

Endpoint berikut masih planned dan belum tersedia sebagai route aktif.

#### 6.3.1 Menus

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/menus` | List menus |
| POST | `/api/v1/menus` | Create menu |
| GET | `/api/v1/menus/{id}` | Get menu detail |
| PUT | `/api/v1/menus/{id}` | Update menu |

#### 6.3.2 Menu Schedules

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/menu-schedules` | List menu schedules |
| POST | `/api/v1/menu-schedules` | Create menu schedule by day of month |
| GET | `/api/v1/menu-schedules/{id}` | Get schedule detail |
| PUT | `/api/v1/menu-schedules/{id}` | Update schedule |

#### 6.3.3 Dishes

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/dishes` | List dishes |
| POST | `/api/v1/dishes` | Create dish |
| GET | `/api/v1/dishes/{id}` | Get dish detail |
| PUT | `/api/v1/dishes/{id}` | Update dish |

#### 6.3.4 Menu Dishes

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/menu-dishes` | List menu to dish assignments |
| POST | `/api/v1/menu-dishes` | Assign dish to menu and meal time |
| DELETE | `/api/v1/menu-dishes/{id}` | Remove assignment |

#### 6.3.5 Dish Compositions

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/dish-compositions` | List dish compositions |
| POST | `/api/v1/dish-compositions` | Add item composition to dish |
| PUT | `/api/v1/dish-compositions/{id}` | Update composition |
| DELETE | `/api/v1/dish-compositions/{id}` | Delete composition |

### 6.4 Daily Patient Endpoints

Endpoint berikut masih planned dan belum tersedia sebagai route aktif.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/daily-patients` | List daily patient inputs |
| POST | `/api/v1/daily-patients` | Create daily patient input |
| GET | `/api/v1/daily-patients/{id}` | Get daily patient detail |

### 6.5 SPK Endpoints

Endpoint berikut masih planned dan belum tersedia sebagai route aktif.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/spk-calculations` | List SPK calculations |
| POST | `/api/v1/spk-calculations` | Generate SPK calculation |
| GET | `/api/v1/spk-calculations/{id}` | Get SPK calculation detail |
| POST | `/api/v1/spk-calculations/{id}/finish` | Mark SPK as finished/validated |
| GET | `/api/v1/spk-calculations/{id}/recommendations` | Get SPK recommendations |

### 6.6 Audit & Reporting Endpoints

Endpoint berikut masih planned dan belum tersedia sebagai route aktif.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/audit-logs` | List audit logs |
| GET | `/api/v1/dashboard` | Get role-based dashboard summary |
| GET | `/api/v1/reports/transactions` | Transaction report |
| GET | `/api/v1/reports/stocks` | Stock report |
| GET | `/api/v1/reports/spk-history` | SPK history report |
| POST | `/api/v1/reports/export-pdf` | Export report as PDF |

## 7. Example Request/Response Contracts

### 7.1 Create Item

Kontrak request/response untuk create item mengikuti bagian **5.4.3 Create Item** di atas.

### 7.2 Create Stock Transaction

Kontrak request/response untuk create stock transaction mengikuti bagian **5.5.4 Create Stock Transaction** di atas.

### 7.3 Submit Revision

Kontrak detail row untuk submit revision sama dengan create stock transaction:

- client tetap mengirim `qty`;
- client boleh menambahkan `input_unit` opsional dengan nilai `base` atau `convert`;
- jika `input_unit` dihilangkan, runtime memperlakukan request sebagai `base`;
- detail yang disimpan tetap menormalisasi `qty` ke satuan dasar dan mencatat `input_qty`/`input_unit` untuk audit.

#### Request

```json
{
  "transaction_date": "2026-04-02",
  "details": [
    {
      "item_id": 1,
      "qty": 4500
    }
  ]
}
```

Request dengan konversi satuan:

```json
{
  "transaction_date": "2026-04-02",
  "details": [
    {
      "item_id": 1,
      "qty": 4,
      "input_unit": "convert"
    }
  ]
}
```

> Catatan: `parent_transaction_id` diambil dari path parameter endpoint `/api/v1/stock-transactions/{id}/submit-revision`, bukan dari body request.

#### Response

```json
{
  "message": "Revision submitted successfully.",
  "data": {
    "id": 11,
    "parent_transaction_id": 10,
    "approval_status_id": 2,
    "is_revision": true
  }
}
```

Submit revision hanya membuat child revision pending dan tidak langsung mengubah stok.

### 7.4 Approve Revision

#### Request

```json
{}
```

#### Response

```json
{
  "message": "Revision approved successfully.",
  "data": {
    "id": 11,
    "approval_status_id": 1,
    "approved_by": 1
  }
}
```

Saat approve berhasil, sistem menerapkan mutasi qty dari detail revision ke `items.qty` secara atomik.

### 7.5 Reject Revision

#### Request

```json
{}
```

#### Response

```json
{
  "message": "Revision rejected successfully.",
  "data": {
    "id": 11,
    "approval_status_id": 3,
    "approved_by": 1
  }
}
```

Reject revision hanya mengubah status approval dan tidak mengubah stok.

### 7.6 Generate SPK

Contoh berikut masih bersifat planned karena endpoint SPK belum tersedia sebagai route aktif.

#### Request

```json
{
  "calculation_date": "2026-04-02",
  "target_date_start": "2026-04-03",
  "target_date_end": "2026-04-04",
  "daily_patient_id": 8,
  "category_id": 1,
  "estimated_patients": 120
}
```

#### Response

```json
{
  "message": "SPK calculation generated successfully.",
  "data": {
    "id": 6,
    "category_id": 1,
    "is_finish": false
  }
}
```

## 8. CodeIgniter 4 Notes

- gunakan plural resources dan route grouping di `/api/v1`;
- tabel dengan `deleted_at` cocok dengan soft delete convention CI4;
- approval/revision lebih tepat dipresentasikan sebagai command endpoint daripada CRUD murni;
- audit log dapat dicatat melalui callback model atau service khusus.
