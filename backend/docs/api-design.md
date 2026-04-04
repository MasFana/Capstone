# API Design — Sistem Informasi Manajemen Gudang dan SPK Instalasi Gizi RSD Balung

## 1. Overview

Dokumen ini mendefinisikan rancangan API untuk backend **CodeIgniter 4 + MySQL** yang sudah diselaraskan dengan DB diagram terbaru.

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

## 4. Authentication & Access Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/auth/login` | Login user with `username` and `password`, returns Bearer token |
| POST | `/api/v1/auth/logout` | Logout current Bearer token |
| GET | `/api/v1/auth/me` | Get current user profile from Bearer token |
| GET | `/api/v1/roles` | List roles, restricted to `admin` via role filter |

### 4.1 Login Contract

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

## 5. Lookup Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/item-categories` | List item categories |
| GET | `/api/v1/transaction-types` | List transaction types |
| GET | `/api/v1/meal-times` | List meal times |
| GET | `/api/v1/approval-statuses` | List approval statuses |

## 6. User Management Endpoints

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

### 6.1 List Users

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
  ]
}
```

### 6.2 Create User

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

### 6.3 Update User

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

### 6.4 Deactivate User

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

### 6.5 Change Password

Changing a user's password revokes all their access tokens. The user must log in again with the new password. Password updates use the Shield user entity flow so credential identity data stays synchronized.

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

### 6.6 Delete User

Soft deletes the user and revokes all their access tokens. The user cannot log in and will not appear in user lists.

#### Response

```json
{
  "message": "User deleted successfully."
}
```

## 7. Item Endpoints

Phase 1 item management covers item master CRUD only. `qty` is read-only in this module and stock-related behavior stays in inventory transaction flows.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/items` | List items with pagination, filtering, and search |
| POST | `/api/v1/items` | Create item |
| GET | `/api/v1/items/{id}` | Get item detail |
| PUT | `/api/v1/items/{id}` | Partial update item |
| DELETE | `/api/v1/items/{id}` | Soft delete item |

### 7.1 Access Rules

- `admin` and `gudang` can list, create, view, and update items.
- `admin` only can soft delete items.
- `dapur` has no access to item master management.

### 7.2 List Items

Supported query parameters:

- `page`
- `perPage`
- `item_category_id`
- `is_active`
- `q`

Rules:

- unknown query parameters return `400` validation errors;
- default order is category ascending, then item name ascending, then item id ascending;
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
      "conversion_base": 1000,
      "qty": "1500.00",
      "is_active": true,
      "created_at": "2026-04-03 10:00:00",
      "updated_at": "2026-04-03 10:00:00",
      "category": {
        "id": 2,
        "name": "KERING"
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

### 7.3 Create Item

Writable fields:

- `name`
- `item_category_id`
- `unit_base`
- `unit_convert`
- `conversion_base`
- `is_active`

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
    "conversion_base": 1000,
    "qty": "0.00",
    "is_active": true,
    "created_at": "2026-04-03 11:00:00",
    "updated_at": "2026-04-03 11:00:00",
    "category": {
      "id": 3,
      "name": "PENGEMAS"
    }
  }
}
```

### 7.4 Get Item Detail

#### Response

```json
{
  "data": {
    "id": 1,
    "item_category_id": 2,
    "name": "Beras",
    "unit_base": "gram",
    "unit_convert": "kg",
    "conversion_base": 1000,
    "qty": "1500.00",
    "is_active": true,
    "created_at": "2026-04-03 10:00:00",
    "updated_at": "2026-04-03 10:00:00",
    "category": {
      "id": 2,
      "name": "KERING"
    }
  }
}
```

### 7.5 Update Item

`PUT /api/v1/items/{id}`` uses partial-update semantics in Phase 1. Only fields present in the request are validated and updated.

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
    "conversion_base": 1000,
    "qty": "1500.00",
    "is_active": false,
    "created_at": "2026-04-03 10:00:00",
    "updated_at": "2026-04-03 11:30:00",
    "category": {
      "id": 2,
      "name": "KERING"
    }
  }
}
```

### 7.6 Delete Item

#### Response

```json
{
  "message": "Item deleted successfully."
}
```

### 7.7 Validation Notes

- `qty` cannot be created or updated directly through the item master endpoints.
- `item_category_id` must reference an existing category.
- `name` must be globally unique.
- Missing or soft-deleted items return `404`.

### 7.8 Deferred to Later Phase

- `GET /api/v1/items/{id}/stock-summary`
- stock usage locking rules
- stock transaction integration

## 8. Inventory Transaction Endpoints

### 8.1 Transactions

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/stock-transactions` | List stock transactions with pagination |
| POST | `/api/v1/stock-transactions` | Create stock transaction header + details |
| GET | `/api/v1/stock-transactions/{id}` | Get stock transaction header only |
| GET | `/api/v1/stock-transactions/{id}/details` | Get stock transaction item lines only |

### 8.1.1 Access Rules

- `admin` dan `gudang` dapat mengakses endpoint transaksi stok Milestone 1.
- `dapur` tidak memiliki akses ke endpoint transaksi stok.

### 8.1.2 List Stock Transactions

Supported query parameters:

- `page`
- `perPage`

Rules:

- unknown query parameters return `400` validation errors;
- default order is `transaction_date` descending, then transaction id descending;
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

### 8.1.3 Create Stock Transaction

Allowed request fields:

- `type_id`
- `transaction_date`
- `spk_id`
- `details`

Allowed detail fields:

- `item_id`
- `qty`

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
- transaksi normal Milestone 1 selalu dibuat dengan `approval_status_id = 1` dan `is_revision = false`;
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

### 8.1.4 Get Stock Transaction Header

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

### 8.1.5 Get Stock Transaction Details

`GET /api/v1/stock-transactions/{id}/details` hanya mengembalikan item lines transaksi.

#### Response

```json
{
  "data": [
    {
      "id": 1,
      "transaction_id": 10,
      "item_id": 1,
      "qty": "5000.00"
    }
  ]
}
```

### 8.2 Workflow Actions

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

### 8.3 Monthly Snapshot Endpoints

Endpoint berikut **belum diimplementasikan pada Milestone 1**.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/monthly-stock-snapshots` | List monthly stock snapshots |
| POST | `/api/v1/monthly-stock-snapshots` | Create monthly stock snapshot |

## 9. Menu & Nutrition Endpoints

### 9.1 Menus

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/menus` | List menus |
| POST | `/api/v1/menus` | Create menu |
| GET | `/api/v1/menus/{id}` | Get menu detail |
| PUT | `/api/v1/menus/{id}` | Update menu |

### 9.2 Menu Schedules

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/menu-schedules` | List menu schedules |
| POST | `/api/v1/menu-schedules` | Create menu schedule by day of month |
| GET | `/api/v1/menu-schedules/{id}` | Get schedule detail |
| PUT | `/api/v1/menu-schedules/{id}` | Update schedule |

### 9.3 Dishes

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/dishes` | List dishes |
| POST | `/api/v1/dishes` | Create dish |
| GET | `/api/v1/dishes/{id}` | Get dish detail |
| PUT | `/api/v1/dishes/{id}` | Update dish |

### 9.4 Menu Dishes

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/menu-dishes` | List menu to dish assignments |
| POST | `/api/v1/menu-dishes` | Assign dish to menu and meal time |
| DELETE | `/api/v1/menu-dishes/{id}` | Remove assignment |

### 9.5 Dish Compositions

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/dish-compositions` | List dish compositions |
| POST | `/api/v1/dish-compositions` | Add item composition to dish |
| PUT | `/api/v1/dish-compositions/{id}` | Update composition |
| DELETE | `/api/v1/dish-compositions/{id}` | Delete composition |

## 10. Daily Patient Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/daily-patients` | List daily patient inputs |
| POST | `/api/v1/daily-patients` | Create daily patient input |
| GET | `/api/v1/daily-patients/{id}` | Get daily patient detail |

## 11. SPK Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/spk-calculations` | List SPK calculations |
| POST | `/api/v1/spk-calculations` | Generate SPK calculation |
| GET | `/api/v1/spk-calculations/{id}` | Get SPK calculation detail |
| POST | `/api/v1/spk-calculations/{id}/finish` | Mark SPK as finished/validated |
| GET | `/api/v1/spk-calculations/{id}/recommendations` | Get SPK recommendations |

## 12. Audit & Reporting Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/audit-logs` | List audit logs |
| GET | `/api/v1/dashboard` | Get role-based dashboard summary |
| GET | `/api/v1/reports/transactions` | Transaction report |
| GET | `/api/v1/reports/stocks` | Stock report |
| GET | `/api/v1/reports/spk-history` | SPK history report |
| POST | `/api/v1/reports/export-pdf` | Export report as PDF |

## 13. Example Request/Response Contracts

### 13.1 Create Item

#### Request

```json
{
  "name": "Beras",
  "item_category_id": 2,
  "unit_base": "gram",
  "unit_convert": "kg",
  "conversion_base": 1000,
  "is_active": true
}
```

#### Response

```json
{
  "message": "Item created successfully.",
  "data": {
    "id": 1,
    "item_category_id": 2,
    "name": "Beras",
    "unit_base": "gram",
    "unit_convert": "kg",
    "conversion_base": 1000,
    "qty": "0.00",
    "is_active": true,
    "created_at": "2026-04-03 11:00:00",
    "updated_at": "2026-04-03 11:00:00",
    "category": {
      "id": 2,
      "name": "KERING"
    }
  }
}
```

### 13.2 Create Stock Transaction

Kontrak request/response untuk create stock transaction mengikuti bagian **8.1.3 Create Stock Transaction** di atas.

### 13.3 Submit Revision

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

### 13.4 Approve Revision

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

### 13.5 Reject Revision

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

### 13.6 Generate SPK

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

## 14. CodeIgniter 4 Notes

- gunakan plural resources dan route grouping di `/api/v1`;
- tabel dengan `deleted_at` cocok dengan soft delete convention CI4;
- approval/revision lebih tepat dipresentasikan sebagai command endpoint daripada CRUD murni;
- audit log dapat dicatat melalui callback model atau service khusus.
