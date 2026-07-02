# Basah/Kering Transaction Validation — Analysis & Implementation Plan

## 1. Root Cause Analysis

### 1.1 Where Validation Is Performed

The primary validation resides in `StockTransactionService::createTransaction()` (`backend/app/Services/StockTransactionService.php`). The `createTransaction` method has two code paths for OUT transactions:

| Lines | Path | Day Guard? | Lock? | Category Check? |
|-------|------|-----------|-------|-----------------|
| 349–471 | **BASAH OUT** — draft workflow (PENDING) | Lines 352–364: queries `stock_transaction_details` JOIN `stock_transactions` JOIN `items` with `items.item_category_id = basahCategoryId` | **No** `FOR UPDATE` | Lines 340–347: rejects mixed basah+non-basah |
| 475–627 | **KERING/PENGEMAS OUT** — immediate APPROVED | **None** | N/A | **None** |

### 1.2 The Day Guard Query (Lines 352–364)

```php
$existingActive = $this->detailModel->builder()
    ->select('st.id, st.approval_status_id')
    ->join('stock_transactions st', 'st.id = stock_transaction_details.transaction_id')
    ->join('items', 'items.id = stock_transaction_details.item_id')
    ->where('st.transaction_date', $data['transaction_date'])
    ->where('st.type_id', (int) $data['type_id'])
    ->where('st.is_revision', false)
    ->where('st.deleted_at', null)
    ->where('st.approval_status_id !=', $rejectedStatusId)
    ->where('items.item_category_id', $basahCategoryId)
    ->groupBy('st.id')
    ->get()
    ->getRowArray();
```

Generated SQL (conceptually):
```sql
SELECT st.id, st.approval_status_id
FROM stock_transaction_details
INNER JOIN stock_transactions st ON st.id = stock_transaction_details.transaction_id
INNER JOIN items ON items.id = stock_transaction_details.item_id
WHERE st.transaction_date = ?
  AND st.type_id = ?
  AND st.is_revision = 0
  AND st.deleted_at IS NULL
  AND st.approval_status_id != ?
  AND items.item_category_id = ?
GROUP BY st.id
```

**This query IS correctly scoped to BASAH items only.**

The `items.item_category_id = $basahCategoryId` filter is an INNER JOIN condition. A KERING-only transaction's detail rows reference items whose `item_category_id` is NOT equal to `basahCategoryId`. Those rows are eliminated by the INNER JOIN. The query returns an empty result set; `getRowArray()` returns `null`; the guard passes.

**Confirmed:** The stated scenario — "OUT-KERING exists on day D, then create OUT-BASAH on day D" — does **NOT** fail due to this day guard query.

### 1.3 Why the Reported Symptom Occurs

We identified three probable causes for the symptom "basah transaction fails when kering exists on same day":

#### Bug A: `updateDraft` Has No Category Validation (HIGH — Primary Suspect)

**Location:** `StockTransactionService::updateDraft()` (lines 630–770)

The method validates that:
- The transaction is PENDING (line 646)
- The transaction type is OUT (line 652)
- Each item_id exists (line 689)

It does **NOT** validate item_category_id. This means:

1. Create an OUT-BASAH draft for date D → PENDING basah-only transaction
2. Call `updateDraft()` replacing details with KERING items → **succeeds**, no error
3. The PENDING transaction now has KERING items but was created via the BASAH draft path

**How this causes the reported symptom:** If (2) has been done, the transaction's approval_status_id is still PENDING. The day guard query (lines 352–364) uses `GROUP BY st.id` without checking item categories on the matching transaction's details. But more critically:

- If a KERING OUT transaction was created directly (APPROVED path, line 475+) on day D
- Then a BASAH OUT transaction is attempted on day D
- The day guard query checks for other BASAH transactions on day D
- The KERING transaction has no BASAH items → day guard passes
- **However**, if an `updateDraft` was used to corrupt a previous basah draft into containing non-basah items, the state becomes inconsistent and can manifest as unexpected failures in later operations (submitDraft can process kering items through the basah workflow, silently).

#### Bug B: No Row-Level Lock on Day Guard (MEDIUM)

The day guard query (lines 352–364) uses **no** `FOR UPDATE` clause. Compare:
- `updateDraft` (line 698–701): **has** `FOR UPDATE`
- `submitDraft` (line 811–814): **has** `FOR UPDATE`
- Day guard (lines 352–364): **no** `FOR UPDATE`

**Race scenario:**
1. Request A and Request B simultaneously call `createTransaction` for OUT-BASAH on same day D
2. Both pass the day guard (both see no existing basah transaction)
3. Both create PENDING drafts
4. Two PENDING drafts exist for same date — violates business rule

**How this causes the reported symptom:** In a clean scenario it shouldn't. But if the race condition produced a duplicate PENDING draft, and that draft was later updated via `updateDraft` to contain mixed items, subsequent validation steps may behave unpredictably.

#### Bug C: Missing KERING/PENGEMAS Day Guard (DESIGN GAP — MEDIUM)

When `$hasBasah === false` and `$hasNonBasah === true` (lines 329–338), control skips the basah block entirely (line 349: `if ($hasBasah)` is false). The transaction falls through to the normal path (line 475+) which:
- Creates an immediately **APPROVED** transaction (PENDING → no draft state)
- No day guard check exists

**Impact:**
- Multiple KERING OUT transactions on the same day are allowed
- Each immediately deducts stock (line 552–557)
- Can oversell the same KERING item across multiple same-day transactions

**Relation to reported symptom:** This is the reverse direction — kering is unguarded, not over-guarded. But if the business expects both directions to be guarded, this is the mirror bug.

### 1.4 Affected Components

| Component | File | Relationship |
|-----------|------|-------------|
| `StockTransactionService::createTransaction()` | `Services/StockTransactionService.php` | Main creation method; day guard exists for BASAH only |
| `StockTransactionService::updateDraft()` | `Services/StockTransactionService.php` | No category validation; can break basah draft invariant |
| `StockTransactionService::submitDraft()` | `Services/StockTransactionService.php` | No category re-validation before stock mutation |
| `StockTransactionService::cancelDraft()` | `Services/StockTransactionService.php` | No category issues (only sets REJECTED, no mutation) |
| `StockTransactionModel` | `Models/StockTransactionModel.php` | Data access; no custom validators |
| `StockTransactionDetailModel` | `Models/StockTransactionDetailModel.php` | Detail access; `builder()` resets joins per `get()` call — no leaked filter risk |
| `SpkStockPostingService::post()` | `Services/SpkStockPostingService.php` | Always creates IN transactions; cannot conflict with OUT day guard |
| `SpkStockInPrefillService` | `Services/SpkStockInPrefillService.php` | Read-only prefill; no transaction creation |
| `SpkBasahGenerationService` | `Services/SpkBasahGenerationService.php` | SPK calculations only; no stock transaction creation |
| `SpkKeringPengemasGenerationService` | `Services/SpkKeringPengemasGenerationService.php` | SPK calculations only; no stock transaction creation |
| `StockTransactions` controller | `Controllers/Api/V1/StockTransactions.php` | Routes API calls to service methods; no additional validation |
| `Config/Validation.php` | `Config/Validation.php` | Only list-query rules; no creation validation rules |

---

## 2. Critical Discussion

### 2.1 Analyst Findings

**Analyst1** (deep code analysis):
- Traced the exact execution path for "OUT-KERING on day D, then OUT-BASAH on day D"
- Confirmed the day guard query correctly excludes KERING-only transactions
- Found Bug A (updateDraft no category check), Bug B (no FOR UPDATE), Bug C (missing kering guard)
- Provided detailed execution trace with line references

**Analyst2** (systems integrator analysis):
- Mapped all validation points across every component
- Identified 5 bugs: BUG-1 (updateDraft category mixing), BUG-2 (day guard bypass via updateDraft), BUG-3 (submitDraft no re-validation), BUG-4 (missing kering guard), BUG-5 (zero test coverage)
- Traced dependency chain: BUG-1 → BUG-2 → BUG-3
- Confirmed SpkStockPostingService creates IN only — no conflict path
- Confirmed all other components do not create stock transactions

**Challenger** (devil's advocate — partial, interrupted):
- Read all key files including models, service methods, and tests
- Was verifying assumptions: day guard correctness, PENGEMAS handling, `findById` method behavior
- Was about to compare fix approaches before API interruption

### 2.2 Convergent Findings

Both analysts converged on these facts:

| Finding | Status | Evidence |
|---------|--------|----------|
| Day guard query correctly filters by `item_category_id = basah` | **Confirmed** | Line 361: `->where('items.item_category_id', $basahCategoryId)` |
| Mixed basah+kering OUT rejected in `createTransaction` | **Confirmed** | Lines 340–347: `if ($hasBasah && $hasNonBasah) { return REJECT }` |
| `updateDraft` has no category validation | **Confirmed** | Lines 689–693: only validates item existence |
| `submitDraft` has no category re-validation | **Confirmed** | Lines 827–866: loads existing details, processes without category check |
| No `FOR UPDATE` lock on day guard | **Confirmed** | Lines 352–364: plain SELECT without lock |
| SpkStockPostingService creates IN only | **Confirmed** | Line 108: `$this->transactionTypeModel->getIdByName(TransactionTypeModel::NAME_IN)` |
| No other day guard query exists in codebase | **Confirmed** | Searched all service and model files |
| Zero test coverage for draft lifecycle | **Confirmed** | No tests for BASAH draft creation, day guard collision, updateDraft, submitDraft, cancelDraft |

### 2.3 Alternative Approaches Considered

#### Approach A: Fix `updateDraft` to Validate Item Category (Primary Recommendation)
- **What:** Add category validation in `updateDraft` after the item existence check loop (after line 692)
- **Cost:** ~20 lines, single file
- **Risk:** Low (contained, no query changes)
- **Why preferred:** Directly fixes the invariant-break path. Prevents `updateDraft` from silently converting basah drafts to non-basah or mixed-category drafts.

#### Approach B: Add `FOR UPDATE` Lock to Day Guard
- **What:** Wrap the day guard query in a transaction with `FOR UPDATE` lock on `stock_transactions` rows for the given date+type
- **Cost:** ~20 lines, single file; requires restructuring the transStart/transComplete for the basah draft path
- **Risk:** Low-Medium (transaction scope change; must ensure transComplete on all return paths including error returns)
- **Why deferred:** The race condition is real but less likely than the `updateDraft` invariant break. Can be done as a second change.

#### Approach C: Add KERING/PENGEMAS Day Guard
- **What:** Add a parallel day guard for non-basah OUT types after line 349's `if ($hasBasah)` block
- **Cost:** ~30 lines, single file
- **Risk:** Medium — changes existing business behavior. Multiple same-day KERING OUTs may currently be relied upon by callers.
- **Why deferred:** Requires business sign-off. May be intentional design.

#### Approach D: Move Category Check from Item Level to Transaction Level
- **What:** Add an `item_category_id` column to `stock_transactions` itself, denormalized from details
- **Cost:** High — requires migration, model changes, and updating all creation/update paths
- **Risk:** High — schema change, data migration, all query paths affected
- **Why rejected:** Unnecessary complexity. The invariant that all items in a transaction share the same category is already enforced at creation. The only gap is `updateDraft`.

#### Approach E: Add Database-Level Unique Index
- **What:** Create a partial unique index on `stock_transactions(transaction_date, type_id)` with a condition excluding rejected/revision/deleted
- **Cost:** Medium — requires migration
- **Risk:** Medium — MySQL partial indexes have limitations; cannot express "only BASAH" condition across a JOIN to details/items
- **Why rejected:** Cannot enforce category-based uniqueness with a simple index. The category lives in the items table, accessible only through a JOIN.

### 2.4 Selected Approach

**Primary: Approach A** — Fix `updateDraft` to validate item category.

**Secondary: Approach B** — Add `FOR UPDATE` lock to day guard (implement as a follow-up).

**Tertiary: Approach C** — Add KERING/PENGEMAS day guard (requires business decision).

### 2.5 Assumptions and Uncertainties

| Item | Status | Action |
|------|--------|--------|
| Day guard query does not cause the reported symptom | **Confirmed** | No change needed to the query |
| `updateDraft` missing category check is the primary bug | **Inferred** (not directly provable without repro) | Implement fix; it's correct independently |
| No existing production data has mixed-category transactions | **Unknown** | Verify before deploy: `SELECT st.id FROM stock_transactions st JOIN stock_transaction_details d1 ... JOIN stock_transaction_details d2 ... WHERE d1.item_id in basah and d2.item_id in kering` |
| KERING day guard is intentionally absent | **Unconfirmed** — needs business input | Ask product owner before implementing |
| Existing in-flight basah drafts may have been corrupted by `updateDraft` | **Unknown** | Check PENDING OUT transactions for non-basah items |
| Race condition has caused duplicate PENDING drafts in production | **Unknown** | Check for duplicate PENDING OUT-basah transactions on same date |
| CI4 Query Builder caches JOINs across `get()` calls | **Confirmed FALSE** | `get()` resets `QBJoin[]` via `resetSelect()` |

---

## 3. Minimal Change Plan

### 3.1 Change 1: Validate Item Category in `updateDraft` (Critical)

**File:** `backend/app/Services/StockTransactionService.php`

**Location:** After line 692 (end of item existence validation loop), before the `$this->db->transStart()` at line 695.

**What to add:** After the item validation loop (lines 665–693), insert a category consistency check:

1. Load `$itemCategoryModel` and resolve `$basahCategoryId`
2. Iterate `$data['details']`, load each item, check `item_category_id === $basahCategoryId`
3. If any detail has a non-basah item, return 400 error with message like "Basah draft can only contain Basah items."
4. If the transaction's item category requirement changes (if business rules evolve), this check can be parameterized or gated on the transaction's type

**Why this works:**
- Prevents the invariant break where a basah draft silently accumulates non-basah items
- Ensures `updateDraft` enforces the same category constraint that `createTransaction` enforces
- Simple, contained change — no query modifications, no schema changes
- Return path follows the existing error pattern (400, "Validation failed.")

**Preserving existing behavior:**
- BASAH OUT drafts continue to work normally when updated with BASAH items
- KERING-only OUT transactions never reach `updateDraft` (line 652 rejects non-OUT types, but kering uses APPROVED path, not draft)
- Other update behaviors (basic validation, transaction locking, detail replacement) are unaffected

**Edge cases:**
- If `updateDraft` is called on a PENDING transaction that has no items yet (impossible — createTransaction always creates at least one detail row that passes the mixing check)
- If the BASAH category is deleted from `item_categories` table — `getIdByName` returns `null`; treat as system error (match existing pattern at lines 326–328)
- If a PENDING transaction has non-basah items (from before this fix) — subsequent `updateDraft` calls would reject them; this is correct behavior (fixes existing corruption)

### 3.2 Change 2: Add `FOR UPDATE` Lock to Day Guard (Recommended)

**File:** `backend/app/Services/StockTransactionService.php`

**Location:** Lines 349–380 (the basah day guard block)

**What to add:**
1. Before the day guard query (before line 352), start a database transaction: `$this->db->transStart()`
2. Issue a SELECT ... FOR UPDATE on `stock_transactions` rows matching the date and type to serialize concurrent creation
3. Run the existing day guard query under this lock
4. Adjust the return paths (success and failure) to call `$this->db->transComplete()` or `$this->db->transRollback()` respectively

**Why this works:**
- Prevents the race condition where two concurrent requests both pass the empty day guard and create duplicate drafts
- Consistent with the locking pattern already used in `updateDraft` and `submitDraft`

**Risks:**
- The entire basah draft creation path (lines 349–471) already has its own `transStart/transComplete` starting at line 385. Adding a second transaction layer requires careful scoping. **Alternative:** Move the day guard inside the existing transaction (after line 385) and acquire the lock there. This is safer but requires restructuring the early-return paths.
- Long-held locks on concurrent requests could cause latency under contention

**Simpler alternative:** Accept the race condition risk. The probability of two users hitting the same endpoint for the same date at the exact same microsecond is low. The `updateDraft` fix (Change 1) is far more critical.

### 3.3 Change 3: Add KERING/PENGEMAS Day Guard (If Business Requires)

**File:** `backend/app/Services/StockTransactionService.php`

**Location:** After line 349's `if ($hasBasah)` block, at line 472 (before fall-through to normal path)

**What to add:** 
```php
if ($hasBasah) {
    // existing basah guard (lines 349–471)
} elseif ($hasNonBasah) {
    // NEW: KERING/PENGEMAS day guard
    // One non-REJECTED OUT per date (same structure as basah guard but without draft workflow)
    $existingActive = $this->detailModel->builder()
        ->select('st.id, st.approval_status_id')
        ->join('stock_transactions st', 'st.id = stock_transaction_details.transaction_id')
        ->join('items', 'items.id = stock_transaction_details.item_id')
        ->where('st.transaction_date', $data['transaction_date'])
        ->where('st.type_id', (int) $data['type_id'])
        ->where('st.is_revision', false)
        ->where('st.deleted_at', null)
        ->where('st.approval_status_id !=', $rejectedStatusId)
        ->where('items.item_category_id !=', $basahCategoryId)  // NOT basah
        ->groupBy('st.id')
        ->get()
        ->getRowArray();

    if ($existingActive !== null) {
        return [
            'success' => false,
            'status_code' => 409,
            'message' => 'Validation failed.',
            'errors' => ['transaction' => 'A KERING/PENGEMAS OUT transaction already exists for this date.'],
            'data' => ['existing_transaction_id' => (int) $existingActive['id']],
        ];
    }
}
```

**Why deferred:** Changes existing business behavior. Must be confirmed with stakeholders.

### 3.4 Change 4: Add Test Coverage (Required with Any Fix)

**File:** `backend/tests/feature/Api/V1/StockTransactionsTest.php`

Required test scenarios:
- BASAH OUT creation succeeds (draft path)
- Day guard rejects duplicate BASAH OUT on same date
- Day guard allows BASAH OUT when only KERING OUT exists on same date
- `updateDraft` rejects non-basah items on basah draft
- `updateDraft` succeeds with basah items on basah draft
- `submitDraft` succeeds for valid basah draft
- Race condition protection (with concurrent requests, if Change 2 implemented)

---

## 4. Verification Plan

### 4.1 Test Scenarios

#### Scenario 1: Basah Succeeds When Only Kering Exists on Same Day (Regression)

**Setup:**
1. Create an OUT-KERING transaction for date D (item with `item_category_id = KERING`)
2. Verify it is created as APPROVED

**Test:**
1. Create an OUT-BASAH transaction for same date D (item with `item_category_id = BASAH`)
2. Assert HTTP 201 (or service returns success)
3. Assert the BASAH transaction exists as PENDING
4. Assert the BASAH transaction's details reference only BASAH items

**Expected:** Succeeds. The day guard (line 361) filters by BASAH category only.

#### Scenario 2: Kering Succeeds When Only Basah Exists on Same Day

**Setup:**
1. Create an OUT-BASAH transaction for date D
2. Verify it is created as PENDING

**Test:**
1. Create an OUT-KERING transaction for same date D
2. Assert HTTP 201 (or service returns success)

**Expected:** Succeeds. KERING has no day guard (current behavior).

**Note:** If Change 3 (kering day guard) is implemented, this test expectation changes to match Scenario 1.

#### Scenario 3: Duplicate Basah on Same Day Is Rejected (Day Guard)

**Setup:**
1. Create an OUT-BASAH transaction for date D
2. Verify PENDING status

**Test:**
1. Create another OUT-BASAH transaction for same date D
2. Assert HTTP 409
3. Assert response contains `existing_transaction_id` matching step 1's transaction

**Expected:** Rejected. Day guard fires.

#### Scenario 4: Duplicate Basah on Different Day Succeeds

**Setup:**
1. Create an OUT-BASAH transaction for date D1

**Test:**
1. Create an OUT-BASAH transaction for date D2 (different day)
2. Assert HTTP 201

**Expected:** Succeeds.

#### Scenario 5: updateDraft Rejects Non-Basah Items (Fix for Bug A)

**Setup:**
1. Create an OUT-BASAH transaction for date D (PENDING)
2. Capture the transaction ID

**Test:**
1. Call `updateDraft` with the transaction ID and details containing a KERING item
2. Assert HTTP 400 with error about non-BASAH items

**Expected:** Rejected. This is the primary fix.

#### Scenario 6: updateDraft Accepts Basah Items (No Regression)

**Setup:**
1. Create an OUT-BASAH transaction for date D (PENDING)
2. Capture the transaction ID

**Test:**
1. Call `updateDraft` with the transaction ID and details containing OTHER BASAH items
2. Assert HTTP 200

**Expected:** Succeeds.

#### Scenario 7: Existing Non-Basah Drafts Are Not Affected (No Regression)

**Setup:**
1. If any existing PENDING transaction has non-basah items (from before fix):

**Test:**
1. Call `updateDraft` on this transaction
2. Verify the fix correctly rejects the update (forces the client to clean up)

**Expected:** The fix correctly enforces the invariant going forward. Previously corrupted drafts must be handled manually or recreated.

#### Scenario 8: Concurrent Basah Creation (Race Condition — If Change 2 Implemented)

**Setup:**
1. Send two concurrent POST requests to create OUT-BASAH for same date D

**Test:**
1. Assert exactly one request succeeds (HTTP 201)
2. Assert exactly one request fails (HTTP 409) or succeeds with a different date

**Expected:** At most one draft created per date. Requires Change 2 (FOR UPDATE) to be bulletproof.

### 4.2 Existing Flows That Must Remain Unaffected

- **IN transactions**: No day guard, no category check. Should continue to work.
- **RETURN_IN transactions**: No day guard. Should continue to work.
- **OPNAME_ADJUSTMENT transactions**: No day guard. Should continue to work.
- **SpkStockPostingService**: Creates IN transactions via `createTransaction`. Should continue to work (type=IN → never enters OUT path).
- **Revision workflow** (`submitRevision`, `approveRevision`, `rejectRevision`): No day guard. Should continue to work.
- **Direct corrections** (`createDirectCorrection`): No day guard. Should continue to work.
- **cancelDraft**: Simply sets REJECTED status. Should continue to work.
- **List/filter endpoints**: Unaffected — no changes to queries.
- **Notification service** (min stock): Unaffected — no changes to notification logic.

### 4.3 Data Verification Before Deploy

Run these queries against production (or staging) before deploying changes:

```sql
-- Check for corrupted drafts: PENDING OUT transactions with non-basah items
SELECT st.id, st.transaction_date, st.approval_status_id
FROM stock_transactions st
JOIN stock_transaction_details d ON d.transaction_id = st.id
JOIN items i ON i.id = d.item_id
JOIN item_categories ic ON ic.id = i.item_category_id
WHERE st.type_id = (SELECT id FROM transaction_types WHERE name = 'OUT')
  AND st.is_revision = 0
  AND st.deleted_at IS NULL
  AND st.approval_status_id = (SELECT id FROM approval_statuses WHERE name = 'PENDING')
  AND ic.name != 'BASAH'
GROUP BY st.id;

-- Check for duplicate PENDING OUT-basah drafts on same date
SELECT st.transaction_date, COUNT(*) as draft_count
FROM stock_transactions st
JOIN stock_transaction_details d ON d.transaction_id = st.id
JOIN items i ON i.id = d.item_id
JOIN item_categories ic ON ic.id = i.item_category_id
WHERE st.type_id = (SELECT id FROM transaction_types WHERE name = 'OUT')
  AND st.is_revision = 0
  AND st.deleted_at IS NULL
  AND st.approval_status_id = (SELECT id FROM approval_statuses WHERE name = 'PENDING')
  AND ic.name = 'BASAH'
GROUP BY st.transaction_date
HAVING COUNT(*) > 1;
```

---

## 5. Implementation Order

| Order | Change | Reason | Risk | Dependencies |
|-------|--------|--------|------|-------------|
| 1 | `updateDraft` category validation | Closes the invariant break that causes the most severe data corruption | Low (contained, no query changes, no schema changes) | None |
| 2 | Test coverage for draft lifecycle | Ensures Changes 1–4 don't regress | Low (test-only) | Change 1 |
| 3 | `FOR UPDATE` lock on day guard | Prevents race condition for concurrent requests | Low-Medium (transaction scoping) | None |
| 4 | KERING/PENGEMAS day guard | Business policy enforcement | Requires sign-off | Change 3 |
