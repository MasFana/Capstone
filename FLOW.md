# End-to-End Flow: Daily Patient → SPK → Stock

> System flow for the hospital kitchen stock management — Pasien Harian → SPK (Surat Perintah Kerja) → Transaksi Masuk → Transaksi Keluar.

---

## Table of Contents

- [Overview](#overview)
- [Step 1: Input Pasien Harian](#step-1--input-pasien-harian)
- [Step 2: Generate SPK Basah](#step-2--generate-spk-basah)
  - [Reads (6 tables)](#reads-6-tables)
  - [Target Dates](#target-dates)
  - [Calculation Per Item Per Date](#calculation-per-item-per-date)
  - [Duplicate Scope Guard](#duplicate-scope-guard)
  - [Writes (2 tables)](#writes-2-tables)
- [Step 2b: Override SPK (optional)](#step-2b--override-spk-optional)
- [Step 3: Lihat Prefill (read-only)](#step-3--lihat-prefill-read-only)
- [Step 4: Transaksi Masuk (Stock IN)](#step-4--transaksi-masuk-stock-in)
  - [Path A: Automated via SPK Post](#path-a-automated-via-spk-post)
  - [Path B: Manual IN](#path-b-manual-in)
  - [Writes (3-4 tables)](#writes-3-4-tables)
- [Step 5: Transaksi Keluar (Stock OUT)](#step-5--transaksi-keluar-stock-out)
  - [Writes (3 tables)](#writes-3-tables)
- [Table States: Before / After Each Step](#table-states-before--after-each-step)
- [Table Write Summary](#table-write-summary)
- [Complete Table Reference](#complete-table-reference)

---

## Overview

```
   ┌──────────────────────────────────────────────────────────────┐
   │                       SEQUENCE DIAGRAM                       │
   └──────────────────────────────────────────────────────────────┘

Step 1     Daily Patient Input          service_date, total_patients
                                              │
Step 2     SPK Basah Generation               │
           ┌──────────────────────┐           │
           │ Reads:               │           │
           │  daily_patients      │◄──────────┘
           │  menu_schedules      │
           │  menu_dishes         │
           │  dish_compositions   │
           │  items               │
           │  item_categories     │
           └──────────┬───────────┘
                      │ writes
                      ▼
           spk_calculations  (1 header row)
           spk_recommendations (N item rows)
                      │
Step 2b              │ (optional) override
  Override            ▼ recommended_qty
           spk_recommendations (update in place)
                      │
Step 3               │ read-only prefill
  Prefill             ▼
           returns draft IN payload
                      │
                      │  Path A: POST /spk/.../post-stock
                      ├──────────────────────────────┐
                      │                              │
                      ▼                              ▼
Step 4     Stock IN (auto)               Stock IN (manual)
           ┌─────────────────────┐      ┌──────────────────────┐
           │ Writes:             │      │ Writes:              │
           │  stock_transactions │      │  stock_transactions  │
           │  stock_tx_details   │      │  stock_tx_details    │
           │  items.qty (+)      │      │  items.qty (+)       │
           │  spk_calc.is_finish │      │                      │
           └─────────────────────┘      └──────────────────────┘
                      │
                      │
Step 5                ▼
  Stock OUT (manual)
           ┌─────────────────────┐
           │ Writes:             │
           │  stock_transactions │
           │  stock_tx_details   │
           │  items.qty (-)      │
           └─────────────────────┘
```

The SPK Post-Stock creates an **IN** transaction (Transaksi Masuk / procurement). This is:

```
Pasien → SPK → Belanja bahan (IN) → Masak → Konsumsi (OUT)
```

The `spk_id` FK on `stock_transactions` only tracks the procurement origin for IN transactions. OUT (Transaksi Keluar) is an independent manual operation that does not reference the SPK.

---

## Step 1 — Input Pasien Harian

**Endpoint**: `POST /api/v1/daily-patients`  
**Service**: `DailyPatientService::createDailyPatient()`  
**Role**: `admin, gudang`  
**Controller**: `DailyPatients::create()`

| READ | WRITE → `daily_patients` |
|---|---|
| — | `service_date` (UNIQUE) |
|   | `total_patients` (INT UNSIGNED) |
|   | `notes` (TEXT, optional) |
|   | `created_at` / `updated_at` |

**Guard**: duplicate `service_date` rejected — checked via `findByServiceDate()` + unique DB index.

### daily_patients table schema

| Column | Type | Constraints |
|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT |
| `service_date` | DATE | NOT NULL, UNIQUE |
| `total_patients` | INT UNSIGNED | NOT NULL |
| `notes` | TEXT | NULL |
| `created_at` | DATETIME | NULL |
| `updated_at` | DATETIME | NULL |

---

## Step 2 — Generate SPK Basah

**Endpoint**: `POST /api/v1/spk/basah/generate`  
**Service**: `SpkBasahGenerationService::generate()` → `SpkPersistenceService::createVersionedSpk()`  
**Role**: `admin, dapur`  
**Controller**: `SpkBasah::generate()`

### Reads (6 tables)

| Table | What for |
|---|---|
| `daily_patients` | `total_patients` where `service_date = {request}` |
| `menu_schedules` | all rows where `day_of_month = {target_date.day}` (can be >1 per day) |
| `menu_dishes` | `dish_id` where `menu_id` matches each scheduled menu |
| `dish_compositions` | `item_id`, `qty_per_patient` for each dish |
| `items` | `qty` (current stock), `item_category_id`, `conversion_base` |
| `item_categories` | resolve `BASAH` category id via `getName()` |

### Target Dates

- `requestedDate` (primary)  
- `requestedDate + 1 day` (only if same calendar month)

### Calculation Per Item Per Date

```
required_qty = ceil(qty_per_patient × (patients + ceil(patients × 0.05)))
                ↑ base rate            ↑ 5% safety buffer

system_recommended = required_qty - current_stock_qty
recommended_qty    = max(0, system_recommended)    // never negative
```

**Per-assignment independent processing**: dishes from multiple menus on the same date are **not** deduplicated. Each menu assignment contributes its ingredients independently:

- Same menu assigned twice → ingredients counted ×2
- Two different menus sharing a dish → each contributes that dish's ingredients separately

### Duplicate Scope Guard

`SpkPersistenceService::createVersionedSpk()` builds a `scope_key`:

```
basah|combined_window|{target_date_start}|{target_date_end}|{category_id}
```

If an unfinished (`is_finish = false`) SPK exists for the same `scope_key`, returns **HTTP 409** unless `regenerate=true` is sent.

### Writes (2 tables)

#### 1 row → `spk_calculations`

| Column | Example |
|---|---|
| `spk_type` | `basah` |
| `calculation_scope` | `combined_window` |
| `scope_key` | `basah\|combined_window\|2026-04-15\|2026-04-16\|1` |
| `version` | auto-inc per `scope_key` |
| `is_latest` | `true` (prev version set `false`) |
| `calculation_date` | service_date |
| `target_date_start` | first target date |
| `target_date_end` | last target date |
| `daily_patient_id` | FK → `daily_patients.id` |
| `user_id` | who generated |
| `category_id` | BASAH category id |
| `estimated_patients` | patient count used |
| `is_finish` | `false` (set `true` after posting) |
| `created_at` / `updated_at` | timestamps |

#### N rows → `spk_recommendations` (one per `item_id` × `target_date`)

| Column | Description |
|---|---|
| `spk_id` | FK → `spk_calculations.id` |
| `item_id` | which ingredient |
| `target_date` | which day this requirement covers |
| `current_stock_qty` | DECIMAL(12,4), snapshot at generation |
| `required_qty` | DECIMAL(12,4), raw calculated need |
| `system_recommended_qty` | DECIMAL(12,4), before floor-to-zero |
| `recommended_qty` | DECIMAL(12,4), final = `max(0, system_recommended)` |
| `is_overridden` | BOOLEAN, default `false` |
| `override_reason` | TEXT, NULL |
| `overridden_by` | BIGINT, FK → `users.id`, NULL |
| `overridden_at` | DATETIME, NULL |

---

## Step 2b — Override SPK (optional)

**Endpoint**: `POST /api/v1/spk/basah/history/{id}/override`  
**Service**: `SpkOverrideService`  
**Role**: `admin, gudang`

**Updates `spk_recommendations`** for specific item rows:

| Column | New value |
|---|---|
| `recommended_qty` | user-specified override |
| `is_overridden` | `true` |
| `override_reason` | why |
| `overridden_by` | user id |
| `overridden_at` | timestamp |

---

## Step 3 — Lihat Prefill (read-only)

**Endpoint**: `GET /api/v1/spk/stock-in-prefill/{spkId}`  
**Service**: `SpkStockInPrefillService::buildDraftFromSpk()`  
**Role**: `admin, dapur, gudang`

**Reads** `spk_recommendations` → aggregates `recommended_qty` per `item_id` across all `target_date` rows → returns draft IN payload:

```json
{
  "type_name": "IN",
  "transaction_date": "2026-04-15",
  "spk_id": 42,
  "details": [
    { "item_id": 5,  "qty": 10500.0 },
    { "item_id": 12, "qty": 525.5 }
  ]
}
```

**Zero writes** — purely a UI convenience to feed into the Manual IN endpoint.

---

## Step 4 — Transaksi Masuk (Stock IN)

Two paths converge on the same service. Both produce identical table writes, except Path A additionally sets `is_finish`.

### Path A: Automated via SPK Post

**Endpoint**: `POST /api/v1/spk/basah/history/{id}/post-stock`  
**Service**: `SpkStockPostingService::post()` → `StockTransactionService::createTransaction()`  
**Role**: `admin, gudang`

Internal flow: reads SPK header + recommendations → aggregates `recommended_qty` per `item_id` → constructs IN payload → calls `createTransaction()` in DB transaction → sets `is_finish = true`.

### Path B: Manual IN

**Endpoint**: `POST /api/v1/stock-transactions` with `{ "type_name": "IN", ... }`  
**Service**: `StockTransactionService::createTransaction()` directly  
**Role**: `admin, gudang`

### Writes (3-4 tables)

All writes happen inside a single DB transaction. If any write fails, the entire transaction is rolled back.

#### 1 row → `stock_transactions`

| Column | Value |
|---|---|
| `type_id` | IN (resolved from `transaction_types` table, auto-approved) |
| `transaction_date` | from request or SPK's `calculation_date` |
| `is_revision` | `false` |
| `parent_transaction_id` | `null` |
| `approval_status_id` | APPROVED (auto-approved, no workflow) |
| `approved_by` | `null` |
| `user_id` | who performed the action |
| `spk_id` | SPK id (nullable, for audit trail) |
| `reason` | nullable |
| `created_at` / `updated_at` | timestamps |
| `deleted_at` | NULL (soft delete support) |

#### N rows → `stock_transaction_details` (one per item)

| Column | Value |
|---|---|
| `transaction_id` | FK → `stock_transactions.id` |
| `item_id` | which item |
| `qty` | DECIMAL(12,2), normalized to base unit |
| `input_qty` | original submitted qty (before normalization) |
| `input_unit` | `base` or `convert` |

**Normalization**: when `input_unit = "convert"`, qty is multiplied by `items.conversion_base`.

#### `items.qty` — atomic increment

```sql
UPDATE items
SET qty = qty + {qty}, updated_at = NOW()
WHERE id = {item_id}
```

Unconditional increment — no stock check for IN.

#### `spk_calculations.is_finish` (Path A only)

```sql
UPDATE spk_calculations SET is_finish = true WHERE id = {spkId}
```

Also triggers: `StockSnapshotService::ensureOpeningSnapshot()` for the transaction's month (idempotent).

---

## Step 5 — Transaksi Keluar (Stock OUT)

**Endpoint**: `POST /api/v1/stock-transactions` with `{ "type_name": "OUT", ... }`  
**Service**: `StockTransactionService::createTransaction()`  
**Role**: `admin, gudang`

### Writes (3 tables)

#### 1 row → `stock_transactions`

Same schema as IN, but `type_id = OUT`.

#### N rows → `stock_transaction_details`

Same schema as IN.

#### `items.qty` — atomic decrement with guard

```sql
UPDATE items
SET qty = qty - {qty}, updated_at = NOW()
WHERE id = {item_id} AND qty >= {qty}
```

Two stock checks:

1. **Pre-transaction validation loop**: checks `items.qty >= requested_qty` for each detail item. Returns HTTP 400 if insufficient.
2. **Atomic SQL guard**: `WHERE qty >= {qty}` ensures no negative stock even if a concurrent transaction consumed stock between the check and the write. If `affectedRows < total items`, the entire transaction is rolled back.

After decrement, triggers `queueMinStockNotificationIfNeeded()` if `items.qty < items.min_stock`.

---

## Table States: Before / After Each Step

```
                       daily_patients         spk_calculations     spk_recommendations     stock_transactions   stock_tx_details    items.qty
                       ───────────────         ────────────────     ──────────────────      ─────────────────   ─────────────────   ─────────
Step 1 (Daily Patient) → service_date=15       (empty)              (empty)                  (empty)             (empty)             5.0
                        total_patients=100

Step 3 (SPK Gen)                                    id=1                spk_id=1               (empty)             (empty)             5.0
                                                   is_finish=0          item_id=5
                                                                        target_date=15
                                                                        required_qty=10000
                                                                        recommended_qty=9995
                                                                        current_stock_qty=5

Step 4 (IN via Post)                            id=1                (unchanged)               id=1                tx_id=1              5.0 → 10000.0
                                                 is_finish=1                                  spk_id=1            item_id=5
                                                                                              type_id=IN          qty=9995

Step 5 (OUT manual)                             (unchanged)         (unchanged)               id=1, id=2          tx_id=1, tx_id=2    10000.0 → 5000.0
                                                                                                                   item_id=5
                                                                                                                   qty=5000
```

---

## Table Write Summary

| Action | Tables Written | Type of Write |
|---|---|---|
| Create Daily Patient | `daily_patients` | INSERT |
| Generate SPK | `spk_calculations` | INSERT |
|   | `spk_recommendations` | INSERT (batch) |
| Override SPK | `spk_recommendations` | UPDATE (per item) |
| Prefill (read-only) | — | none |
| Transaksi Masuk (IN) | `stock_transactions` | INSERT |
|   | `stock_transaction_details` | INSERT (batch) |
|   | `items.qty` | UPDATE (increment) |
|   | `spk_calculations.is_finish` | UPDATE (only via SPK Post path) |
| Transaksi Keluar (OUT) | `stock_transactions` | INSERT |
|   | `stock_transaction_details` | INSERT (batch) |
|   | `items.qty` | UPDATE (conditional decrement) |

---

## Complete Table Reference

| Table | Created In Migration | Row Granularity | Written In | Read In |
|---|---|---|---|---|
| `daily_patients` | `CreateDailyPatients` | 1 row per `service_date` | Step 1 | Step 3 |
| `menu_schedules` | `CreateMenuSchedules` | 1 row per `(day_of_month, menu_id)` | Setup | Step 3 |
| `menu_dishes` | `CreateMenuDishes` | 1 row per `(menu_id, meal_time_id, dish_id)` | Setup | Step 3 |
| `dish_compositions` | `CreateDishCompositions` | 1 row per `(dish_id, item_id)` | Setup | Step 3 |
| `items` | `CreateItems` | 1 row per item | Steps 4, 5 (qty mutation) | Steps 3, 4, 5 |
| `item_categories` | `CreateItemCategories` | 1 row per category | Setup | Step 3 |
| `spk_calculations` | `CreateSpkPersistenceTables` | 1 header per SPK version | Steps 2, 4 (is_finish) | Steps 3, 4 |
| `spk_recommendations` | `CreateSpkPersistenceTables` | 1 row per `(spk, item, target_date)` | Steps 2, 2b (override) | Steps 3, 4 |
| `stock_transactions` | `CreateStockTransactions` | 1 header per transaction | Steps 4, 5 | Steps 4, 5 |
| `stock_transaction_details` | `CreateStockTransactionDetails` | 1 row per `(tx, item)` | Steps 4, 5 | Steps 4, 5 |
| `transaction_types` | `CreateTransactionTypes` | IN / OUT / RETURN_IN / OPNAME_ADJUSTMENT | Setup | Steps 4, 5 |
| `approval_statuses` | `CreateApprovalStatuses` | APPROVED / PENDING / REJECTED | Setup | Steps 4, 5 |
| `audit_logs` | `CreateAuditLogs` | 1 row per auditable action | All steps | — |
| `monthly_stock_snapshots` | `CreateMonthlyStockSnapshots` | auto-snapshot per month | Before Step 4/5 txn | — |
