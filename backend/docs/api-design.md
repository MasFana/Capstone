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
    "pageCount": 0
  },
  "links": {
    "self": "/api/v1/items?page=1&perPage=10",
    "next": null,
    "prev": null
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

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/items` | List items |
| POST | `/api/v1/items` | Create item |
| GET | `/api/v1/items/{id}` | Get item detail |
| PUT | `/api/v1/items/{id}` | Update item |
| DELETE | `/api/v1/items/{id}` | Soft delete item |
| GET | `/api/v1/items/{id}/stock-summary` | Get current stock summary |

## 8. Inventory Transaction Endpoints

### 8.1 Transactions

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/stock-transactions` | List stock transactions |
| POST | `/api/v1/stock-transactions` | Create stock transaction header + details |
| GET | `/api/v1/stock-transactions/{id}` | Get transaction detail |
| GET | `/api/v1/stock-transactions/{id}/details` | Get transaction item lines |

### 8.2 Workflow Actions

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/stock-transactions/{id}/submit-revision` | Submit revision against parent transaction |
| POST | `/api/v1/stock-transactions/{id}/approve` | Approve revision transaction |
| POST | `/api/v1/stock-transactions/{id}/reject` | Reject revision transaction |

### 8.3 Monthly Snapshot Endpoints

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
    "name": "Beras",
    "qty": 0
  }
}
```

### 13.2 Create Stock Transaction

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

> Catatan: `user_id` idealnya diambil dari authenticated session/token context, bukan dikirim bebas oleh client.

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

### 13.3 Submit Revision

#### Request

```json
{
  "parent_transaction_id": 10,
  "transaction_date": "2026-04-02",
  "details": [
    {
      "item_id": 1,
      "qty": 4500
    }
  ]
}
```

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

### 13.4 Generate SPK

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
