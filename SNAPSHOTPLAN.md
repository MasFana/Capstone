# Stock Snapshot Implementation Plan — Path A (v2)

## Design Decision

**Auto-trigger on first stock transaction of the month**, backed by an admin endpoint and CLI command. All three converge on the existing idempotent `StockSnapshotService::takeOpeningSnapshot()`.

### Why Path A

| Trigger | Reliability | Infra Need | Overhead |
|---|---|---|---|
| **Auto on first transaction** | Highest — mutation cannot happen before snapshot | None | One `COUNT` query until first transaction of month, then 0 |
| **Auto on login** | High — user entry point | None | Same, but doesn't cover API-only flows |
| **CLI command** | Medium — cron may miss | Host cron or Docker cron container | Zero on API path |
| **Manual endpoint** | Low — human forgets | None | — |

**Auto on transaction is primary**: guarantees snapshot exists before ANY stock mutation that a report tracks. Login is complementary backup for read-only users. CLI + endpoint give explicit control.

### Key Assumptions

> **`opening_qty` definition:** Opening quantity for month M is `items.qty` at the moment the first stock transaction of month M is about to execute. This captures the carry-over from the previous month, provided all prior stock movements went through the transaction service. Direct DB edits outside the transaction pipeline are NOT reflected.

> **Data type:** `opening_qty` is `DECIMAL(12,2)` per the existing migration — not integer. All types and interfaces must reflect this.

---

## Consumer Map

| Consumer | Depends on Snapshot? | Current Behavior If Missing |
|---|---|---|
| **`GET /reports/monthly-stock-export`** | **YES** — reads `opening_qty` from `monthly_stock_snapshots` via `ReportingService::getMonthlyStockExport()` (L528-542) | `stok_awal` = `null` per item → report shows empty cells |
| **`GET /api/v1/stock-snapshots`** (this plan) | **YES** — lists the table directly | Not implemented yet |
| **`POST /api/v1/stock-snapshots`** (this plan) | **YES** — writes to the table | Not implemented yet |
| `SpkKeringPengemasGenerationService` | **NO** — reads `items.qty` live | Works correctly (formula needs current on-hand, not opening) |
| `DashboardAggregateService` | **NO** — queries `items.qty` via SQL aggregates | Works correctly |
| `StockOpnameService` | **NO** — freezes its own `system_qty` | Works correctly |

Bottom line: the monthly stock export report is the only hard dependency. Everything else reads live data.

### How `opening_qty` Flows Into the Monthly Export Report

The monthly stock export (`GET /reports/monthly-stock-export`) uses `opening_qty` as the **seed value for the entire running balance computation**. It is not a display-only field.

```
ReportingService::getMonthlyStockExport()
  |
  +- 1. Query monthly_stock_snapshots
  |      SELECT item_id, opening_qty
  |        WHERE period_month = ? AND item_id IN (?)
  |      -> $snapshotMap[item_id] = (float) opening_qty
  |
  +- 2. For each item:
  |      $stokAwal   = $snapshotMap[$itemId] ?? null    // null if no snapshot
  |      $runningSisa = $stokAwal                        // seeds the balance
  |
  |      For each day in period:
  |        if ($runningSisa !== null):
  |          $runningSisa = $runningSisa + $masuk - $keluar
  |        -> harian[].sisa = $runningSisa                // cascades null
  |
  +- 3. Response per row:
         {
           "stok_awal": number | null,       // opening_qty (snapshot)
           "harian": [
             { "masuk": 12.5, "keluar": 0, "sisa": 225.0 },  // null if stok_awal null
             ...
           ]
         }
```

**Key invariant:** If `stok_awal` is `null`, every day's `sisa` is also `null` — the whole column is garbage. The report doesn't crash, it just produces empty cells.

**Type stack:**

| Layer | Type | Source |
|---|---|---|
| DB column | `DECIMAL(12,2)` | Migration |
| PHP cast | `(float)` | `StockSnapshotService::takeOpeningSnapshot()` line 77 |
| PHP usage | `(float)` | `ReportingService::getMonthlyStockExport()` line 540 |
| JS/TS SDK | `number` | `frontend/src/sdk/types/reports.ts` line 96 |

---

## Implementation Phases

### Phase 1: Foundation — Model + Service Enhancements

#### 1a. Create `backend/app/Models/MonthlyStockSnapshotModel.php`

CodeIgniter Model wrapping `monthly_stock_snapshots`.

```php
$table           = 'monthly_stock_snapshots';
$primaryKey      = 'id';
$allowedFields   = ['period_month', 'item_id', 'opening_qty'];
$useTimestamps   = true;
$useSoftDeletes  = false;
$returnType      = 'array';

$validationRules = [
    'period_month' => 'required|valid_date[Y-m-d]',
    'item_id'      => 'required|integer',
    'opening_qty'  => 'required|decimal',
];
```

Add `getAllPaginatedFiltered()` — follows `StockTransactionModel::getAllPaginatedFiltered()` pattern (clone-for-count, conditional filters):

```php
public function getAllPaginatedFiltered(
    int $page,
    int $perPage,
    ?string $periodMonth = null,
    ?int $itemId = null,
    ?int $categoryId = null,
): array
```

- Joins `items` for `item_name`, `item_category_id`
- Joins `item_categories` for `category_name`
- Filters: `period_month`, `item_id`, `item_category_id`
- Uses `$this->countAllResults(false)` for total, then `$this->findAll($perPage, $offset)` for data
- Returns `['snapshots' => ..., 'total' => ..., 'page' => ..., 'perPage' => ..., 'totalPages' => ...]`

---

#### 1b. Add `ensureOpeningSnapshot()` to `backend/app/Services/StockSnapshotService.php`

New public method on the existing service — centralizes the guard logic (DRY) and the failure-absorption guarantee:

```php
/**
 * Ensure a snapshot exists for the given month. Idempotent, failure-safe.
 * Designed to be called from auto-trigger hooks (transactions, login).
 * NEVER throws — all errors are logged and swallowed.
 *
 * @param string $month YYYY-MM format
 */
public function ensureOpeningSnapshot(string $month): void
{
    try {
        $periodMonth = $month . '-01';
        $exists = $this->db->table('monthly_stock_snapshots')
            ->where('period_month', $periodMonth)
            ->countAllResults();

        if ($exists === 0) {
            $this->takeOpeningSnapshot($month);
        }
    } catch (\Throwable $e) {
        log_message('error', '[StockSnapshot] Auto-trigger failed for {month}: {error}', [
            'month' => $month,
            'error' => $e->getMessage(),
        ]);
        // Intentionally swallowed — never block the calling operation
    }
}
```

**Why centralize here instead of duplicating in callers:**
- `takeOpeningSnapshot()` already lives in this service — keeping guard logic co-located is the DRY choice
- The `try/catch` guarantee is enforced in ONE place, not copied across `StockTransactionService` + `AuthService`
- Callers become one-liners — no private utility methods needed in each service
- Future trigger points (e.g., API imports) just call `ensureOpeningSnapshot()` without reimplementing the guard

#### 1c. Add `retakeOpeningSnapshot()` to `backend/app/Services/StockSnapshotService.php`

For admin corrections — delete existing snapshot rows and re-capture:

```php
/**
 * Force-retake: deletes existing rows for the month, then re-snapshots.
 * Use when snapshot data is incorrect (e.g., captured after a data fix).
 *
 * @param string $month YYYY-MM format
 * @return array Same shape as takeOpeningSnapshot()
 */
public function retakeOpeningSnapshot(string $month): array
{
    $periodMonth = $month . '-01';

    $this->db->transStart();
    $this->db->table('monthly_stock_snapshots')
        ->where('period_month', $periodMonth)
        ->delete();
    $this->db->transComplete();

    if (!$this->db->transStatus()) {
        return ['success' => false, 'message' => 'Failed to delete existing snapshot.', 'count' => 0];
    }

    return $this->takeOpeningSnapshot($month);
}
```

---

### Phase 2: Auto-Trigger Hooks

#### 2a. `backend/app/Services/StockTransactionService.php::createTransaction()` — insert immediately before `$this->db->transStart()` (L311)

```php
// IMPORTANT: Keep this immediately before transStart().
// Snapshot is idempotent but should only run after all validation passes.
(new StockSnapshotService())->ensureOpeningSnapshot(date('Y-m'));

$this->db->transStart();  // existing line 311
```

Add `use App\Services\StockSnapshotService;` to imports.

#### 2b. `backend/app/Services/StockTransactionService.php::createDirectCorrection()` — insert immediately before its `transStart()` (L655)

Same one-liner:

```php
(new StockSnapshotService())->ensureOpeningSnapshot(date('Y-m'));

$this->db->transStart();  // existing line 655
```

#### 2c. `backend/app/Services/AuthService.php::attemptLogin()` — insert after token generation (L61), before building the return array

```php
// Opportunistic snapshot trigger for read-only users
(new StockSnapshotService())->ensureOpeningSnapshot(date('Y-m'));
```

Add `use App\Services\StockSnapshotService;` to imports.

#### Design Rules for All Trigger Points

| Rule | How It's Enforced |
|---|---|
| **Idempotent** | `ensureOpeningSnapshot()` checks `COUNT` first; `takeOpeningSnapshot()` checks again internally (L43-53); unique constraint `(period_month, item_id)` as final safety |
| **Failure absorption** | `try/catch(\Throwable)` inside `ensureOpeningSnapshot()` — logged via `log_message()`, never propagated |
| **No transaction nesting** | Snapshot runs BEFORE the caller's `transStart()` — uses its own separate DB transaction |
| **Minimal overhead** | After first trigger per month: `COUNT` returns > 0 → immediate return, no further work |

#### Concurrency Safety

**Race condition:** If two concurrent requests both see `COUNT = 0` and both call `takeOpeningSnapshot()`:

1. Both enter `takeOpeningSnapshot()`, both pass the internal idempotency check (race window)
2. Both call `transStart()` → `insertBatch()` → `transComplete()`
3. First batch commits successfully
4. Second batch hits unique constraint violation on `(period_month, item_id)`
5. CI4's `insertBatch()` on MySQL sets `transStatus = false` on constraint violations
6. `transComplete()` detects failure → automatic rollback of second batch
7. `takeOpeningSnapshot()` catches `\Throwable` (L85-93) → returns failure array
8. `ensureOpeningSnapshot()` catches any remaining exception → logs and swallows
9. Both original operations (transactions/logins) proceed unaffected

**No data corruption possible.** The unique key is the authoritative guard.

---

### Phase 3: API Endpoints

#### 3a. New Controller — `backend/app/Controllers/Api/V1/StockSnapshots.php`

Extends `BaseController` (matches `StockTransactions.php` pattern). Response envelope matches existing controllers.

```
POST   /api/v1/stock-snapshots          -> StockSnapshots::take()
GET    /api/v1/stock-snapshots          -> StockSnapshots::index()
GET    /api/v1/stock-snapshots/current  -> StockSnapshots::current()
```

**`take()`**
- Body: `{ "month": "2026-06", "force": false }` — both fields optional
  - `month` defaults to `date('Y-m')`
  - `force` defaults to `false`; when `true`, calls `retakeOpeningSnapshot()` instead
- Validation: `month` must match `^\d{4}-(0[1-9]|1[0-2])$` (same regex as `takeOpeningSnapshot()` L33-38)
- Auth: `$user = auth()->user(); if ($user === null) → 401`
- Returns:
  - `201` with `{ success: true, message, count }` on new creation
  - `200` with `{ success: true, message, count: 0 }` if already exists (skipped)
  - `400` with `{ message, errors }` on invalid input
  - `401` / `403` on auth/role failure
- When `force: true`, audit-logged by `takeOpeningSnapshot()` internally (it already calls `AuditService`)

**`index()`**
- Query params: `page`, `perPage`, `period_month`, `item_id`, `item_category_id`
- Delegates to `MonthlyStockSnapshotModel::getAllPaginatedFiltered()`
- Returns paginated list with `item_name`, `category_name`, `opening_qty`
- Response: `{ data: [...], meta: { page, perPage, total, totalPages }, links: { self, first, last, next, previous } }`
- Uses `buildPaginationLinks()` helper (same as `StockTransactions` controller)

**`current()`**
- No params — checks current month
- Delegates to `StockSnapshotService::getCurrentMonthStatus()` (already exists, L100-123)
- Returns: `{ month: "2026-06", has_snapshot: bool, item_count: number | null }`
- Convenience endpoint for dashboard widgets

**OpenAPI Annotations** — full schemas on each method (consistent with `StockTransactions.php`):

```php
/**
 * @OA\Post(
 *     path="/api/v1/stock-snapshots",
 *     operationId="takeStockSnapshot",
 *     summary="Take opening stock snapshot for a month",
 *     tags={"Stock Snapshots"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="month", type="string", example="2026-06"),
 *             @OA\Property(property="force", type="boolean", example=false)
 *         )
 *     ),
 *     @OA\Response(response=201, description="Snapshot created",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="count", type="integer", example=42)
 *         )
 *     ),
 *     @OA\Response(response=200, description="Snapshot already exists"),
 *     @OA\Response(response=400, description="Invalid month format",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *     ),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
```

#### 3b. Routes — `backend/app/Config/Routes.php`

**CORS OPTIONS entries** — add to outer public block (L17-270):

```php
$routes->options('stock-snapshots', [CorsPreflightController::class, 'handle']);
$routes->options('stock-snapshots/current', [CorsPreflightController::class, 'handle']);
```

**Authenticated routes:**

| Route | Where to Add | Filter (exact syntax) |
|---|---|---|
| `GET stock-snapshots/current` | Shared read section (~L290-308), `role:admin,dapur,gudang` | `role:admin,dapur,gudang` |
| `GET stock-snapshots` | Inventory & Stock Ops section (~L387-455), `role:admin,gudang` | `role:admin,gudang` |
| `POST stock-snapshots` | Inventory & Stock Ops section (~L387-455), `role:admin,gudang` | `role:admin,gudang` |

**Note:** `GET current` goes in the broader access group so `dapur` role can check snapshot status for dashboards. The list and take operations are restricted to `admin,gudang`.

---

### Phase 4: CLI Commands

#### 4a. `backend/app/Commands/StockSnapshotTakeCommand.php`

Pattern follows `HistoricalOpnameBackfillCommand` (extends `BaseCommand`, uses `resolveOption()` pattern):

```php
class StockSnapshotTakeCommand extends BaseCommand
{
    protected $group       = 'Inventory';
    protected $name        = 'stock:snapshot-take';
    protected $description = 'Take opening stock snapshot for a month. Idempotent.';
    protected $usage       = 'stock:snapshot-take [--month YYYY-MM] [--force]';
    protected $arguments   = [];
    protected $options     = [
        '--month' => 'Target month in YYYY-MM format. Defaults to current month.',
        '--force' => 'Delete existing snapshot and re-take.',
    ];

    public function run(array $params): int
    {
        $month = $this->resolveOption($params, 'month') ?? date('Y-m');
        $force = $this->resolveOption($params, 'force') !== null;

        $service = new StockSnapshotService();

        $result = $force
            ? $service->retakeOpeningSnapshot($month)
            : $service->takeOpeningSnapshot($month);

        if (!$result['success']) {
            CLI::error($result['message']);
            return EXIT_ERROR;
        }

        CLI::write($result['message'], 'green');
        CLI::write('Items captured: ' . $result['count'], 'green');
        return EXIT_SUCCESS;
    }

    private function resolveOption(array $params, string $name): ?string
    {
        return $params[$name] ?? $params['--' . $name] ?? CLI::getOption($name);
    }
}
```

**Example usage:**
```bash
php spark stock:snapshot-take
php spark stock:snapshot-take --month 2026-06
php spark stock:snapshot-take --month 2026-06 --force
```

#### 4b. `backend/app/Commands/StockSnapshotCheckCommand.php` — Health Check

```php
class StockSnapshotCheckCommand extends BaseCommand
{
    protected $group       = 'Inventory';
    protected $name        = 'stock:snapshot-check';
    protected $description = 'List months with stock transactions but no snapshot.';
    protected $usage       = 'stock:snapshot-check';
    protected $arguments   = [];
    protected $options     = [];

    public function run(array $params): int
    {
        $db = Database::connect();

        // Find all distinct months from stock_transactions
        $txMonths = $db->query("
            SELECT DISTINCT DATE_FORMAT(transaction_date, '%Y-%m') as month
            FROM stock_transactions
            WHERE deleted_at IS NULL
            ORDER BY month
        ")->getResultArray();

        $gaps = [];
        foreach ($txMonths as $row) {
            $periodMonth = $row['month'] . '-01';
            $count = $db->table('monthly_stock_snapshots')
                ->where('period_month', $periodMonth)
                ->countAllResults();
            if ($count === 0) {
                $gaps[] = $row['month'];
            }
        }

        if (empty($gaps)) {
            CLI::write('All months with transactions have snapshots.', 'green');
            return EXIT_SUCCESS;
        }

        CLI::error('Missing snapshots for ' . count($gaps) . ' month(s):');
        foreach ($gaps as $month) {
            CLI::write('  - ' . $month, 'yellow');
        }
        return EXIT_ERROR;
    }
}
```

---

### Phase 5: Frontend SDK

#### 5a. New Types — `frontend/src/sdk/types/stockSnapshots.ts`

```typescript
// --- Domain Models ---

export interface StockSnapshotRow {
  id: number;
  period_month: string;     // "YYYY-MM-DD" (always first of month)
  item_id: number;
  item_name: string;
  category_name: string;
  opening_qty: number;      // DECIMAL(12,2) from backend, parsed as number
  created_at: string;
}

// --- Request DTOs ---

export interface CreateSnapshotRequest {
  month?: string;    // YYYY-MM — defaults to current month on server
  force?: boolean;   // delete & retake if true
}

// --- Response Envelopes ---

export interface CreateSnapshotResponse {
  success: boolean;
  message: string;
  count: number;
}

export interface CurrentSnapshotStatus {
  month: string;
  has_snapshot: boolean;
  item_count: number | null;
}

// --- Query Interface ---

export interface ListSnapshotsQuery {
  page?: number;
  perPage?: number;
  period_month?: string;      // "YYYY-MM-DD" format
  item_id?: number;
  item_category_id?: number;
}
```

**Pattern:** Types grouped as domain models → input DTOs → response envelopes → query interfaces (matching `stockOpnames.ts` convention).

#### 5b. New Resource — `frontend/src/sdk/resources/stockSnapshots.ts`

```typescript
import type { ApiClient } from "../client";
import type {
  ApiListResponse,
} from "../types/common";
import type {
  StockSnapshotRow,
  ListSnapshotsQuery,
  CreateSnapshotRequest,
  CreateSnapshotResponse,
  CurrentSnapshotStatus,
} from "../types/stockSnapshots";

/**
 * Wraps: /api/v1/stock-snapshots
 * Contract: §6.2 in api-contract.md
 * Access: admin, gudang (current: admin, dapur, gudang)
 */
export class StockSnapshotsResource {
  private readonly client: ApiClient;

  public constructor(client: ApiClient) {
    this.client = client;
  }

  /**
   * List paginated stock snapshots with item and category details.
   *
   * @endpoint GET /api/v1/stock-snapshots
   * @access admin, gudang
   * @param query - Optional filters and pagination
   * @returns Paginated list of snapshot rows
   * @throws {AuthenticationApiError} 401 if not authenticated
   * @throws {AuthorizationApiError} 403 if role not allowed
   */
  public async list(
    query?: ListSnapshotsQuery,
  ): Promise<ApiListResponse<StockSnapshotRow>> {
    return this.client.request({
      method: "GET",
      path: "/stock-snapshots",
      query: query as Record<string, string | number>,
    });
  }

  /**
   * Take (or retake) an opening stock snapshot for a month.
   *
   * @endpoint POST /api/v1/stock-snapshots
   * @access admin, gudang
   * @param request - Optional month and force flag
   * @returns Creation result with item count
   * @throws {AuthenticationApiError} 401 if not authenticated
   * @throws {AuthorizationApiError} 403 if role not allowed
   * @throws {ValidationApiError} 400 if month format invalid
   */
  public async take(
    request?: CreateSnapshotRequest,
  ): Promise<CreateSnapshotResponse> {
    return this.client.request({
      method: "POST",
      path: "/stock-snapshots",
      body: request,
    });
  }

  /**
   * Check current month's snapshot status.
   *
   * @endpoint GET /api/v1/stock-snapshots/current
   * @access admin, dapur, gudang
   * @returns Status object with has_snapshot flag and item count
   * @throws {AuthenticationApiError} 401 if not authenticated
   */
  public async current(): Promise<CurrentSnapshotStatus> {
    return this.client.request({
      method: "GET",
      path: "/stock-snapshots/current",
    });
  }
}
```

#### 5c. Wire into SDK — `frontend/src/sdk/index.ts` (4 touch-points)

| # | Touch-Point | Location | Change |
|---|---|---|---|
| 1 | Barrel re-export | L1–24 (top block) | Add `export * from "./resources/stockSnapshots";` |
| 2 | Named import | L26–47 (import block) | Add `import { StockSnapshotsResource } from "./resources/stockSnapshots";` |
| 3 | Property declaration | L54–74 (in `CapstoneSdk` class) | Add `public readonly stockSnapshots: StockSnapshotsResource;` |
| 4 | Constructor init | L78–98 (in constructor) | Add `this.stockSnapshots = new StockSnapshotsResource(this.client);` |

#### 5d. Types barrel — `frontend/src/sdk/types/index.ts`

Add `export * from "./stockSnapshots";` (1 line, among existing re-exports).

---

### Phase 6: Tests

#### 6a. Backend — `backend/tests/feature/Api/V1/StockSnapshotsTest.php`

Extends `CIUnitTestCase`, uses `DatabaseTestTrait`. Properties: `$migrate = true`, `$refresh = true`, `$namespace = 'App'`, `$DBGroup = 'tests'`.

| # | Test | What It Verifies |
|---|---|---|
| 1 | `testTakeCreatesSnapshotForCurrentMonth` | POST without body → 201, snapshot rows exist in DB |
| 2 | `testTakeWithSpecificMonth` | POST `{ month: "2026-06" }` → 201, snapshot for that month |
| 3 | `testTakeReturns200WhenSnapshotExists` | Same POST twice → first 201, second 200 with skip message |
| 4 | `testTakeWithForceRetakes` | POST `{ month: "2026-06", force: true }` → 201, rows replaced |
| 5 | `testTakeInvalidMonthFormat` | POST `{ month: "invalid" }` → 400, errors object present |
| 6 | `testTakeInvalidMonthValue` | POST `{ month: "2026-13" }` → 400 |
| 7 | `testTakeRequiresAuth` | No token → 401 |
| 8 | `testTakeRequiresCorrectRole` | Authenticated with `dapur` role → 403 |
| 9 | `testIndexReturnsPaginatedList` | GET → 200, paginated rows with `item_name`, `category_name`, `opening_qty` |
| 10 | `testIndexFiltersByPeriodMonth` | GET `?period_month=2026-06-01` → filtered results only |
| 11 | `testIndexFiltersByCategory` | GET `?item_category_id=1` → filtered results only |
| 12 | `testCurrentReturnsStatus` | GET /current → 200, `{ month, has_snapshot: true, item_count }` |
| 13 | `testCurrentWhenNoSnapshot` | GET /current in clean month → `{ has_snapshot: false, item_count: null }` |

#### 6b. Backend — Integration (auto-trigger)

Extend existing test files:

| # | Test | File | What It Verifies |
|---|---|---|---|
| 1 | `testCreateTransactionTriggersSnapshot` | `StockTransactionsTest.php` | POST transaction in new month → `monthly_stock_snapshots` populated |
| 2 | `testLoginTriggersSnapshot` | `AuthTest.php` | POST login in new month → snapshot taken |
| 3 | `testSnapshotFailureDoesNotBlockTransaction` | `StockTransactionsTest.php` | Mock snapshot service to throw → transaction still succeeds (201) |
| 4 | `testSnapshotFailureDoesNotBlockLogin` | `AuthTest.php` | Mock snapshot service to throw → login still succeeds |

#### 6c. Frontend — `frontend/src/sdk/tests/stockSnapshots.test.ts`

Uses Vitest with **inline fetch mock** pattern (matching `stockOpnames.test.ts` and `reports.test.ts` conventions — NOT `vi.fn()` mocks):

```typescript
import { describe, expect, it } from "vitest";
import { createCapstoneSdk } from "../index";

describe("StockSnapshots SDK Contract", () => {
  let requestedUrl: string;
  let requestedMethod: string;
  let requestedBody: string;

  const fetchMock = async (url: RequestInfo | URL, init?: RequestInit) => {
    requestedUrl = url.toString();
    requestedMethod = init?.method ?? "GET";
    requestedBody = String(init?.body);
    return new Response(
      JSON.stringify({ data: [], meta: {}, links: {} }),
      { status: 200, headers: { "Content-Type": "application/json" } },
    );
  };

  const sdk = createCapstoneSdk({
    baseUrl: "http://127.0.0.1:8080",
    fetchImplementation: fetchMock as typeof fetch,
  });

  // ... test cases
});
```

| # | Test | What It Verifies |
|---|---|---|
| 1 | `list sends GET with query params` | URL = `/api/v1/stock-snapshots?page=1&perPage=10`, method = GET |
| 2 | `list sends GET without query` | URL = `/api/v1/stock-snapshots`, method = GET |
| 3 | `take sends POST with month` | Method = POST, body contains `{ month: "2026-06" }` |
| 4 | `take sends POST without body` | Method = POST, body is undefined |
| 5 | `take sends POST with force` | Body contains `{ month: "2026-06", force: true }` |
| 6 | `current sends GET to /current` | URL ends with `/stock-snapshots/current`, method = GET |

---

### Phase 7: Smoke Verification

| # | Check | Method | Expected |
|---|---|---|---|
| 1 | PHP syntax | `find backend/app -name "*.php" -newer SNAPSHOTPLAN.md -exec php -l {} +` | No parse errors |
| 2 | Auto-trigger on first transaction | Create stock transaction in new month | `monthly_stock_snapshots` has rows |
| 3 | Auto-trigger on login | Login in new month | Snapshot taken if missing |
| 4 | Idempotency | `POST /api/v1/stock-snapshots` twice | 201 then 200, no duplicates |
| 5 | Force retake | `POST /api/v1/stock-snapshots { force: true }` | Rows replaced, 201 |
| 6 | CLI — take | `php spark stock:snapshot-take` | Success message, rows in DB |
| 7 | CLI — take with force | `php spark stock:snapshot-take --month 2026-06 --force` | Rows replaced |
| 8 | CLI — check | `php spark stock:snapshot-check` | Table output showing coverage |
| 9 | Monthly export with snapshot | `GET /reports/monthly-stock-export` | `stok_awal` populated per item |
| 10 | Monthly export without snapshot | Export for future month | `stok_awal: null` |
| 11 | Role enforcement (POST) | `POST /api/v1/stock-snapshots` with `dapur` role | 403 Forbidden |
| 12 | Role enforcement (current) | `GET /api/v1/stock-snapshots/current` with `dapur` role | 200 (allowed) |
| 13 | Invalid month format | `POST { month: "2026-13" }` | 400 Bad Request |
| 14 | CORS preflight | `OPTIONS /api/v1/stock-snapshots` | 200/204 with CORS headers |
| 15 | Frontend SDK build | `cd frontend && npx tsc --noEmit` | No type errors |
| 16 | Frontend tests | `cd frontend && npx vitest run` | All passing |
| 17 | Backend tests | `php spark test --group snapshot` | All passing |

---

## File Inventory

### Create (8 files)

| # | File | Est. Lines | Description |
|---|---|---|---|
| 1 | `backend/app/Models/MonthlyStockSnapshotModel.php` | ~70 | Model with validation rules + paginated filtered query |
| 2 | `backend/app/Commands/StockSnapshotTakeCommand.php` | ~70 | CLI take command with `--force` support |
| 3 | `backend/app/Commands/StockSnapshotCheckCommand.php` | ~55 | CLI health check for missing snapshots |
| 4 | `backend/app/Controllers/Api/V1/StockSnapshots.php` | ~220 | Controller with take/index/current + full OpenAPI annotations |
| 5 | `frontend/src/sdk/types/stockSnapshots.ts` | ~55 | All type interfaces including `ListSnapshotsQuery` |
| 6 | `frontend/src/sdk/resources/stockSnapshots.ts` | ~90 | Resource class with list/take/current + JSDoc |
| 7 | `backend/tests/feature/Api/V1/StockSnapshotsTest.php` | ~220 | 13 API test cases + setup |
| 8 | `frontend/src/sdk/tests/stockSnapshots.test.ts` | ~80 | 6 SDK contract tests |

### Modify (6 files)

| # | File | Change |
|---|---|---|
| 9 | `backend/app/Services/StockSnapshotService.php` | +`ensureOpeningSnapshot()` (~15 lines) + `retakeOpeningSnapshot()` (~15 lines) |
| 10 | `backend/app/Services/StockTransactionService.php` | +1 import + 1 line before `transStart()` in `createTransaction()` + 1 line in `createDirectCorrection()` |
| 11 | `backend/app/Services/AuthService.php` | +1 import + 1 line after token generation in `attemptLogin()` |
| 12 | `backend/app/Config/Routes.php` | +2 OPTIONS lines + 3 authenticated route lines |
| 13 | `frontend/src/sdk/types/index.ts` | +1 export line |
| 14 | `frontend/src/sdk/index.ts` | +4 lines (barrel export, import, property, constructor init) |

### No Change Needed

| File | Why |
|---|---|
| `backend/app/Services/ReportingService.php` | Already reads `monthly_stock_snapshots` via LEFT JOIN (L528-542) |
| `backend/app/Controllers/Api/V1/AuditLogs.php` | Already lists table in filter metadata |
| `backend/app/Services/DashboardAggregateService.php` | Uses live `items.qty` |
| `backend/app/Services/SpkKeringPengemasGenerationService.php` | Uses live `items.qty` by design |
| `backend/app/Models/ItemModel.php` | No new query needed |
| `backend/app/Services/StockSnapshotService.php` core method | `takeOpeningSnapshot()` unchanged — new methods call it |
| `backend/app/Database/Migrations/2026-04-15-120000_CreateMonthlyStockSnapshots.php` | Table already exists with correct schema |
| `backend/app/Database/Seeders/MonthlyExportScenarioSeeder.php` | Already calls `takeOpeningSnapshot()` |

---

## Edge Cases

| Case | Behavior |
|---|---|
| **New item added mid-month** | Not in current month's snapshot. Monthly report sees `opening_qty = 0` for it — expected (item didn't exist at month start). |
| **Item deleted mid-month** | Snapshot captured it at month-start. FK has `ON DELETE CASCADE` — if item is hard-deleted, snapshot row is also removed. If soft-deleted (`deleted_at` set), snapshot persists and report correctly shows opening stock. |
| **First transaction backdated to previous month** | Auto-trigger snapshots CURRENT month (uses `date('Y-m')`, not the transaction's `transaction_date`). Previous month should already have its snapshot from when its first transaction happened. |
| **Zero active items** | `takeOpeningSnapshot()` returns `{ success: true, count: 0 }`. No rows inserted. Not an error. |
| **Concurrent first transactions** | Unique key `(period_month, item_id)` prevents duplicates. First `insertBatch()` wins; second fails on constraint → `transComplete()` rollback → `ensureOpeningSnapshot()` swallows error → both original operations proceed. See Concurrency Safety section above. |
| **Snapshot service offline / DB error** | `ensureOpeningSnapshot()` catches `\Throwable`, logs error, returns void. Original operation proceeds. Month lacks snapshot — report shows `null` opening. `stock:snapshot-check` CLI flags the gap. |
| **No transactions in a month** | No auto-trigger fires. No snapshot taken. Report shows `stok_awal = null`. Admin can manually take via API or CLI. `stock:snapshot-check` flags it if needed. |
| **Force retake with concurrent reads** | `retakeOpeningSnapshot()` deletes then re-inserts within a transaction. Brief window of no data. Acceptable — force retake is a rare admin action, not automated. |
| **Month parameter without leading zero** | Validated via regex `^\d{4}-(0[1-9]|1[0-2])$` in both `takeOpeningSnapshot()` and controller — `"2026-1"` rejected with 400. |
| **Force retake for current month with active transactions** | Captures current `items.qty` — which now includes this month's transactions. The `opening_qty` will NOT match the original month-start value. Document this behavior to admin users. |
