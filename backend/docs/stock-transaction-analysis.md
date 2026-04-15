# Stock Transaction System — Production-Grade Analysis

> **Date:** 2026-04-13
> **Scope:** Normal stock movement (IN/OUT/RETURN_IN), direct stock correction intent, transaction revision flow (submit/approve/reject), consistency between schema, service logic, tests, and docs.
> **Constraint:** Minimal redesign bias. Prefer solutions that fit current model first. No event-sourcing rewrite.

## Status Update (2026-04-14) — Boundary Hardening Regression Coverage

Current runtime + feature tests now enforce these boundaries explicitly:

- **SPK generation is non-mutating for stock**: SPK generate/preview paths do not write `items.qty` and do not create stock transactions (`SpkBasahTest::testGenerateDoesNotCreateStockTransactions`, `OperationalStockPreviewTest::testOperationalPreviewReturnsSameDayDraftAndDoesNotMutateStockOrSpkHistory`).
- **Stock mutation remains authoritative in stock-transaction endpoints**: `items.qty` changes only through stock transaction create/direct-correction/revision-approval flow in `StockTransactionService`.
- **Normal transaction semantics remain auto-approved and non-revision**: IN/OUT/RETURN_IN creation persists `approval_status_id = APPROVED` with `is_revision = false` (`StockTransactionsTest::testNormalTransactionTypesAreAutoApprovedAndNonRevision`).
- **Revision semantics remain immutable + admin-gated approval**: submit revision remains pending/non-mutating; approve/reject remain admin-only actions with approval-state guards.
- **Admin revision review contract remains flat-schema compatible**:
  - `GET /stock-transactions` returns flat rows (no embedded details),
  - `GET /stock-transactions/{id}` returns header-only,
  - `GET /stock-transactions/{id}/details` returns line-only payload (`StockTransactionsTest::testAdminRevisionReviewListShowAndDetailsKeepFlatContracts`).

These checks preserve stage separation as three independently testable phases: **menu projection/SPK computation → SPK generation/history → explicit stock posting via stock transaction endpoints**.

---

## A. Current Logic Map

### A1. Transaction Types & Stock Direction

| Type | Direction | Stock Effect | Guard |
|---|---|---|---|
| `IN` | Inbound | `items.qty + X` | None (unconditional) |
| `OUT` | Outbound | `items.qty - X` | `WHERE qty >= X` + `affectedRows()` check |
| `RETURN_IN` | Return inbound | `items.qty + X` | None (unconditional) |

**Source:** `TransactionTypeModel` constants (`NAME_IN`, `NAME_OUT`, `NAME_RETURN_IN`), `StockTransactionService.php:347-394`.

### A2. Stock Mutation Sites (Exhaustive)

There are exactly **two** runtime mutation sites for `items.qty` in the entire codebase. Both are in `StockTransactionService.php`. No database triggers, stored procedures, event listeners, or other services touch `items.qty`.

| # | Method | Lines | Trigger | Effect |
|---|---|---|---|---|
| 1 | `createTransaction()` | 346–394 | Normal stock creation (auto-approved) | Immediate qty mutation |
| 2 | `approveRevision()` | 788–841 | Admin approves a revision | Immediate qty mutation |

**Guards against direct writes:**
- `ItemModel::$allowedFields` (lines 11–20) excludes `qty` from mass assignment.
- `ItemManagementService` (line 19) lists `qty` in `FORBIDDEN_FIELDS`.
- All qty mutations bypass the model via raw `$this->db->table('items')` query builder.

**Source:** Background agent `bg_205ffbce` — confirmed no other mutation points exist.

### A3. Transaction Lifecycle

```
┌─────────────────────────────────────────────────────────────────┐
│  createTransaction()                                            │
│  ┌──────────┐     ┌──────────┐     ┌──────────────────────┐    │
│  │ Validate  │────▶│ Insert   │────▶│ Mutate items.qty     │    │
│  │ payload + │     │ txn +    │     │ (IN: +X, OUT: -X,    │    │
│  │ stock     │     │ details  │     │  RETURN_IN: +X)      │    │
│  └──────────┘     └──────────┘     └──────────────────────┘    │
│  Result: is_revision=false, approval_status=APPROVED            │
│  Stock: mutated immediately                                     │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  submitRevision()  (lines 452–707)                              │
│  ┌──────────┐     ┌──────────┐     ┌──────────────────────┐    │
│  │ Validate  │────▶│ Check    │────▶│ Insert revision txn  │    │
│  │ parent +  │     │ parent   │     │ + details            │    │
│  │ payload   │     │ !revision│     │                      │    │
│  └──────────┘     └──────────┘     └──────────────────────┘    │
│  Result: is_revision=true, approval_status=PENDING              │
│  Stock: NOT mutated                                             │
│  Constraint: Cannot revise a revision (line 463)                │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  approveRevision()  (lines 709–903)                             │
│  ┌──────────┐     ┌──────────┐     ┌──────────────────────┐    │
│  │ Validate  │────▶│ Load     │────▶│ Mutate items.qty     │    │
│  │ revision  │     │ details  │     │ ★ ADDITIVE — uses    │    │
│  │ is PENDING│     │ + type   │     │ revision qty as-is   │    │
│  └──────────┘     └──────────┘     └──────────────────────┘    │
│  Result: approval_status → APPROVED, approved_by set            │
│  Stock: mutated with revision qty ON TOP of parent's effect     │
│  ★ THIS IS THE BUG                                              │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  rejectRevision()  (lines 906–1027)                             │
│  ┌──────────┐     ┌──────────────────────────────────────┐     │
│  │ Validate  │────▶│ Update status → REJECTED              │     │
│  │ PENDING   │     │ Stock: NOT mutated                    │     │
│  └──────────┘     └──────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────┘
```

### A4. Access Control

| Endpoint | Roles | Filter |
|---|---|---|
| `POST /stock-transactions` | `admin`, `gudang` | `tokens`, `role:admin,gudang` |
| `POST /stock-transactions/{id}/submit-revision` | `admin`, `gudang` | `tokens`, `role:admin,gudang` |
| `POST /stock-transactions/{id}/approve` | `admin` | `tokens`, `role:admin` |
| `POST /stock-transactions/{id}/reject` | `admin` | `tokens`, `role:admin` |

**Source:** `Routes.php:84-183`, `RoleFilter.php:13-57`.

### A5. Schema Highlights

| Table | Key Fields | Constraints |
|---|---|---|
| `stock_transactions` | `is_revision`, `parent_transaction_id`, `approval_status_id`, `approved_by`, `type_id` | `parent_transaction_id` self-FK with SET NULL on delete |
| `stock_transaction_details` | `transaction_id`, `item_id`, `qty`, `input_qty`, `input_unit` | `UNIQUE(transaction_id, item_id)` |
| `items` | `qty` | Not in `allowedFields`; no version/lock column |

**Notable absences:**
- No `is_superseded` flag on parent transactions.
- No unique constraint preventing multiple approved revisions per parent.
- No `SELECT FOR UPDATE` or row-locking anywhere.
- No explicit MySQL isolation level configured in app or `.env`.

**Source:** All 5 migration files, `Config/Database.php`, `.env`, background agent `bg_cb1e7a51`.

---

## B. Invariants

These are the rules the system **must** maintain at all times.

### B1. Core Stock Invariants

| ID | Invariant | Currently Held? |
|---|---|---|
| I-1 | `items.qty` must never go negative | **Yes** — OUT uses `WHERE qty >= X` guard |
| I-2 | `items.qty` reflects the net effect of all **effective** stock movements | **NO** — revision approval double-counts |
| I-3 | Each stock mutation must be atomic and crash-safe | **Partial** — wrapped in `transStart/transComplete` but no row locking |
| I-4 | A revision's intent is to **correct/replace** the parent's effect, not add to it | **NO** — code applies additive mutation |

### B2. Workflow Invariants

| ID | Invariant | Currently Held? |
|---|---|---|
| W-1 | Only PENDING revisions can be approved or rejected | **Yes** — checked at lines 741–762 |
| W-2 | Only one revision per parent should be approved at any time | **NO** — no constraint or code check |
| W-3 | Revisions cannot be chained (no revision of a revision) | **Yes** — enforced at line 463 |
| W-4 | Stock is mutated only on create (immediate) and approve (deferred) | **Yes** — submit and reject do not mutate |

### B3. Concurrency Invariants

| ID | Invariant | Currently Held? |
|---|---|---|
| C-1 | Concurrent stock operations on the same item must serialize | **Partial** — atomic SQL arithmetic, but no SELECT FOR UPDATE |
| C-2 | Double-approval of the same revision must be idempotent or blocked | **Partial** — status check exists but has TOCTOU window |
| C-3 | Concurrent approval of sibling revisions must be prevented | **NO** — no guard at all |

---

## C. Root Problems

### C1. PRIMARY: Additive Revision Approval (Severity: Critical)

**What:** `approveRevision()` (lines 788–841) uses identical mutation logic to `createTransaction()` (lines 346–394). It applies the revision's detail quantities as **new, independent stock movements** rather than as corrections of the parent's already-applied effect.

**Why it's wrong:** A revision with `qty=40` on a parent with `qty=30` should change the net stock effect from 30 to 40 (delta of 10). Instead, it applies 40 as a fresh mutation, making the total effect 70.

**Evidence from code:**

```php
// approveRevision() — lines 788-798
// Applies revision qty directly, never reads parent details
foreach ($details as $detail) {
    $changeQty  = (float) $detail['qty'];
    $itemId     = (int) $detail['item_id'];
    $escapedQty = $this->db->escape(number_format($changeQty, 2, '.', ''));

    if (in_array($type['name'], [TransactionTypeModel::NAME_IN, TransactionTypeModel::NAME_RETURN_IN], true)) {
        $builder->set('qty', "qty + {$escapedQty}", false);  // Adds revision qty on top
```

**Evidence from tests (all three types confirm additive behavior):**

| Test | Parent | Revision | Expected (test asserts) | Correct behavior |
|---|---|---|---|---|
| `testApproveRevisionMutatesQtyForInType` (line 1652) | IN +50 | qty=75 | `qtyAfterParent + 75` = **+125 total** | Should be +75 total (delta +25) |
| `testApproveRevisionMutatesQtyForOutType` (line 1709) | OUT -30 | qty=40 | `qtyAfterParent - 40` = **-70 total** | Should be -40 total (delta -10) |
| `testApproveRevisionMutatesQtyForReturnInType` (line 1762) | RETURN_IN +25 | qty=35 | `qtyAfterParent + 35` = **+60 total** | Should be +35 total (delta +10) |

**Impact:** Every approved revision corrupts `items.qty`. The magnitude of corruption equals the parent's original qty (it's counted twice, effectively).

### C2. SECONDARY: No Sibling Revision Guard (Severity: High)

**What:** The schema and code allow multiple revisions to be submitted and approved against the same parent transaction. There is no unique constraint on `(parent_transaction_id, approval_status_id)` and no code check for existing approved siblings.

**Evidence:** `submitRevision()` (lines 452–707) only checks that the parent exists and `is_revision=false`. It does not check whether another pending or approved revision already exists. `approveRevision()` (lines 709–903) does not check for sibling revisions either.

**Impact:** If two revisions are submitted and both approved, each one applies its own additive mutation. Combined with C1, this compounds the corruption.

### C3. SECONDARY: TOCTOU Race on Approval (Severity: Medium)

**What:** The approval status check (`approval_status_id === PENDING` at line 741) and the status update (line 846) are not protected by row-level locking. Two concurrent `POST .../approve` requests could both pass the PENDING check before either writes.

**Evidence:** Background agent `bg_cb1e7a51` confirmed: no `SELECT FOR UPDATE`, no advisory locks, no mutex. The only protection is the DB transaction wrapper (`transStart`/`transComplete`) which does not prevent concurrent reads of the same row.

**Impact:** Potential double-mutation if two admins click approve simultaneously. Low probability in practice (admin-only endpoint, manual action), but not impossible.

### C4. MINOR: Documentation Ambiguity on Revision Semantics (Severity: Low)

**What:** The documentation describes revision approval in ambiguous terms that don't clarify whether it means "replace" or "add":

- `api-design.md` line ~1300: *"items.qty baru berubah ketika revision di-approve"* — says **when** stock changes, not **how**.
- `system-design.md` section 8.4: *"Jika disetujui… mutasi qty revisi diterapkan"* — "applied" is ambiguous.
- `data-dictionary.md` line ~327: *"Mutasi stok dari revisi hanya diterapkan ketika status revisi berubah menjadi APPROVED"* — confirms timing, silent on semantics.

**Impact:** Future developers will read the docs and not know whether the additive behavior is intentional or a bug. The docs need to explicitly state "net correction" semantics after the fix.

### C5. MINOR: Reporting Double-Count Risk (Severity: Low, escalates to Medium with volume)

**What:** Both the parent (APPROVED) and its approved revision (APPROVED) appear in query results. Any report summing all APPROVED transactions will double-count the stock effect for revised transactions.

**Evidence:** No `is_superseded` column, no query filter to exclude parents with approved children. `StockTransactionModel::getAllPaginatedFiltered()` returns all transactions without distinguishing effective vs. superseded.

**Impact:** Stock movement reports, period summaries, and audit trails will show inflated totals.

---

## D. Best-Fit Solution

### D1. Chosen Approach: Signed Net Delta Correction

**Strategy:** In `approveRevision()`, compute the **signed net delta** between the revision's intended effect and the parent's already-applied effect, per item. Apply only the difference.

This is mathematically equivalent to "reverse the parent, then apply the revision" (Approach B) but executes as a single atomic mutation per item with no intermediate state (Approach A). **B semantics, A execution.**

### D2. Why This Approach

| Criterion | Delta Correction | Reversal-then-Apply | Schema Change (`is_superseded`) |
|---|---|---|---|
| Schema change needed | **No** | No | Yes |
| Mutations per item | **1** | 2 | 1 |
| Intermediate negative state risk | **None** | Yes (brief reversal) | None |
| Handles item set mismatch | **Yes** (union of items) | Yes | Yes |
| Preserves audit trail | **Yes** (parent stays as-is) | Yes | Yes |
| Endpoint contract changes | **None** | None | Possible |
| Implementation complexity | **Low-Medium** | Medium | Medium-High |

**Delta correction wins** because it requires zero schema changes, has no intermediate state risk, produces a single atomic mutation per item, and naturally handles the case where revision adds or removes items compared to the parent.

### D3. Core Algorithm

```
FUNCTION approveRevision(revisionId, approverId, ipAddress):
    // 1. Validate revision exists and is PENDING
    revision = findRevisionById(revisionId)
    IF revision is NULL OR not is_revision → fail

    IF revision.approval_status ≠ PENDING → fail

    // 2. START DB TRANSACTION
    db.transStart()

    // 3. Load parent transaction and its details
    parent = findById(revision.parent_transaction_id)
    IF parent is NULL → rollback, fail

    parentDetails = getDetailsByTransactionId(parent.id)
    revisionDetails = getDetailsByTransactionId(revisionId)

    // 4. Check no sibling revision is already approved
    existingApproved = findApprovedRevisionByParentId(parent.id)
    IF existingApproved is not NULL → rollback, fail("Another revision already approved")

    // 5. Determine stock direction
    type = findType(revision.type_id)
    direction = (type.name IN ['IN', 'RETURN_IN']) ? +1 : -1

    // 6. Build item maps
    parentMap = {}
    FOR EACH detail IN parentDetails:
        parentMap[detail.item_id] = detail.qty

    revisionMap = {}
    FOR EACH detail IN revisionDetails:
        revisionMap[detail.item_id] = detail.qty

    allItemIds = UNION(keys(parentMap), keys(revisionMap))

    // 7. Apply signed deltas
    FOR EACH itemId IN allItemIds:
        parentQty   = parentMap[itemId] OR 0.0
        revisionQty = revisionMap[itemId] OR 0.0
        signedDelta = direction × (revisionQty - parentQty)

        IF signedDelta = 0.0 → SKIP

        IF signedDelta > 0:
            // Stock increases — unconditional
            UPDATE items SET qty = qty + signedDelta WHERE id = itemId
        ELSE:
            // Stock decreases — guarded
            absDelta = ABS(signedDelta)
            UPDATE items SET qty = qty - absDelta WHERE id = itemId AND qty >= absDelta
            IF affectedRows = 0 → rollback, fail("Insufficient stock for correction")

    // 8. Update revision status
    UPDATE stock_transactions SET
        approval_status_id = APPROVED,
        approved_by = approverId
    WHERE id = revisionId

    // 9. Audit log
    auditService.log(...)

    // 10. Commit
    db.transComplete()
```

### D4. Delta Examples

**Case 1: IN revision increases qty**
- Parent: IN, item_id=1, qty=50 → already applied +50
- Revision: item_id=1, qty=75
- direction = +1, delta = +1 × (75 − 50) = **+25**
- Stock change: +25 (net effect goes from +50 to +75) ✓

**Case 2: OUT revision increases qty**
- Parent: OUT, item_id=1, qty=30 → already applied −30
- Revision: item_id=1, qty=40
- direction = −1, delta = −1 × (40 − 30) = **−10**
- Stock change: −10 (net effect goes from −30 to −40) ✓

**Case 3: IN revision decreases qty (correction downward)**
- Parent: IN, item_id=1, qty=100 → already applied +100
- Revision: item_id=1, qty=60
- direction = +1, delta = +1 × (60 − 100) = **−40**
- Stock change: −40 with guard (net effect goes from +100 to +60) ✓
- If current stock < 40 → approval fails (stock was consumed)

**Case 4: Revision removes an item**
- Parent: OUT, item_id=1 qty=30, item_id=2 qty=20
- Revision: item_id=1 qty=30 (item_id=2 dropped)
- For item_id=1: direction=−1, delta = −1 × (30 − 30) = 0 → skip
- For item_id=2: direction=−1, delta = −1 × (0 − 20) = **+20** → stock restored ✓

**Case 5: Revision adds a new item**
- Parent: IN, item_id=1 qty=50
- Revision: item_id=1 qty=50, item_id=2 qty=30 (new)
- For item_id=1: delta = +1 × (50 − 50) = 0 → skip
- For item_id=2: delta = +1 × (30 − 0) = **+30** → new stock added ✓

### D5. Sibling Revision Guard

Add a model method to check for existing approved revisions:

```
FUNCTION findApprovedRevisionByParentId(parentId):
    SELECT * FROM stock_transactions
    WHERE parent_transaction_id = parentId
      AND is_revision = true
      AND approval_status_id = APPROVED_STATUS_ID
      AND deleted_at IS NULL
    LIMIT 1
```

Call this inside `approveRevision()` after loading parent, before mutating stock. If a sibling is already approved, return a validation error: *"Another revision for this transaction has already been approved."*

### D6. Reporting Guidance (No Code Change Required)

Define **effective transaction** as a query rule:

```sql
-- Effective stock movement: exclude parents that have an approved child
SELECT st.*
FROM stock_transactions st
WHERE st.is_revision = false
  AND NOT EXISTS (
      SELECT 1 FROM stock_transactions rev
      WHERE rev.parent_transaction_id = st.id
        AND rev.is_revision = true
        AND rev.approval_status_id = <APPROVED_ID>
        AND rev.deleted_at IS NULL
  )
  AND st.deleted_at IS NULL

UNION ALL

-- Include approved revisions as effective
SELECT st.*
FROM stock_transactions st
WHERE st.is_revision = true
  AND st.approval_status_id = <APPROVED_ID>
  AND st.deleted_at IS NULL
```

This does not require schema changes. It can be implemented when reporting endpoints are built (they are not yet implemented per `project-flow-alignment.md`).

---

## E. Implementation Plan

### E1. File Change Map

| # | File | Change | Lines Affected |
|---|---|---|---|
| 1 | `StockTransactionService.php` | Rewrite `approveRevision()` mutation block with delta logic | ~788–841 |
| 2 | `StockTransactionService.php` | Extract `applySignedItemDelta()` helper method | New method |
| 3 | `StockTransactionService.php` | Optionally refactor `createTransaction()` to use same helper | ~346–394 |
| 4 | `StockTransactionModel.php` | Add `findApprovedRevisionByParentId()` method | New method |
| 5 | `StockTransactionsTest.php` | Fix 3 existing tests (IN/OUT/RETURN_IN) to assert delta behavior | ~1652–1812 |
| 6 | `StockTransactionsTest.php` | Add new tests: item removal, item addition, partial overlap, sibling block, insufficient stock on downward correction | New tests |
| 7 | `docs/api-design.md` | Update revision approval section to document "net correction" semantics | Section ~1300 |
| 8 | `docs/system-design.md` | Clarify revision = replacement, not addition | Section 8.4 |
| 9 | `docs/data-dictionary.md` | Document effective-transaction query rule | Section ~327 |

### E2. Step-by-Step

**Step 1: Add `findApprovedRevisionByParentId()` to `StockTransactionModel`**

```php
public function findApprovedRevisionByParentId(int $parentId, int $approvedStatusId): ?array
{
    return $this->where('parent_transaction_id', $parentId)
                ->where('is_revision', true)
                ->where('approval_status_id', $approvedStatusId)
                ->first();
}
```

**Step 2: Add `applySignedItemDelta()` helper to `StockTransactionService`**

```php
private function applySignedItemDelta(int $itemId, float $signedDelta): array
{
    $builder = $this->db->table('items');
    $builder->where('id', $itemId);

    if ($signedDelta > 0.0) {
        $escapedQty = $this->db->escape(number_format($signedDelta, 2, '.', ''));
        $builder->set('qty', "qty + {$escapedQty}", false);
        $builder->set('updated_at', date('Y-m-d H:i:s'));

        if (! $builder->update()) {
            return ['success' => false, 'message' => 'Failed to update item quantity.', 'errors' => []];
        }
    } else {
        $absDelta   = abs($signedDelta);
        $escapedQty = $this->db->escape(number_format($absDelta, 2, '.', ''));
        $builder->where("qty >= {$escapedQty}", null, false);
        $builder->set('qty', "qty - {$escapedQty}", false);
        $builder->set('updated_at', date('Y-m-d H:i:s'));

        if (! $builder->update()) {
            return ['success' => false, 'message' => 'Failed to update item quantity.', 'errors' => []];
        }

        if ($this->db->affectedRows() === 0) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['details' => 'Insufficient stock. Stock may have changed since revision submission.'],
            ];
        }
    }

    return ['success' => true];
}
```

**Step 3: Rewrite `approveRevision()` mutation block**

Replace lines 788–841 with:

```php
// Load parent details for delta computation
$parentDetails = $this->detailModel->getDetailsByTransactionId((int) $revision['parent_transaction_id']);

// Check no sibling revision is already approved
$existingApproved = $this->transactionModel->findApprovedRevisionByParentId(
    (int) $revision['parent_transaction_id'],
    $approvedStatusId
);
if ($existingApproved !== null) {
    $this->db->transRollback();
    return [
        'success' => false,
        'message' => 'Validation failed.',
        'errors'  => ['id' => 'Another revision for this transaction has already been approved.'],
    ];
}

// Build item maps
$parentMap = [];
foreach ($parentDetails as $pd) {
    $parentMap[(int) $pd['item_id']] = (float) $pd['qty'];
}

$revisionMap = [];
foreach ($details as $rd) {
    $revisionMap[(int) $rd['item_id']] = (float) $rd['qty'];
}

$allItemIds = array_unique(array_merge(array_keys($parentMap), array_keys($revisionMap)));
$direction  = in_array($type['name'], [TransactionTypeModel::NAME_IN, TransactionTypeModel::NAME_RETURN_IN], true) ? 1 : -1;

// Apply signed deltas
foreach ($allItemIds as $itemId) {
    $parentQty   = $parentMap[$itemId] ?? 0.0;
    $revisionQty = $revisionMap[$itemId] ?? 0.0;
    $signedDelta = $direction * ($revisionQty - $parentQty);

    if (abs($signedDelta) < 0.005) {
        continue; // No meaningful change for this item
    }

    $result = $this->applySignedItemDelta($itemId, $signedDelta);
    if (! $result['success']) {
        $this->db->transRollback();
        return $result;
    }
}
```

**Step 4: Fix existing tests**

The three mutation tests must be updated to assert **delta** behavior:

```php
// testApproveRevisionMutatesQtyForInType
// Parent IN +50, Revision qty=75 → delta = +25
$this->assertSame($qtyAfterParent + 25, $qtyAfterApprove);
// (was: $qtyAfterParent + 75)

// testApproveRevisionMutatesQtyForOutType
// Parent OUT -30, Revision qty=40 → delta = -10
$this->assertSame($qtyAfterParent - 10, $qtyAfterApprove);
// (was: $qtyAfterParent - 40)

// testApproveRevisionMutatesQtyForReturnInType
// Parent RETURN_IN +25, Revision qty=35 → delta = +10
$this->assertSame($qtyAfterParent + 10, $qtyAfterApprove);
// (was: $qtyAfterParent + 35)
```

**Step 5: Add new tests**

| Test | Setup | Expected Result |
|---|---|---|
| Revision removes item | Parent OUT: item 1 qty=30, item 2 qty=20. Revision: item 1 qty=30 only. | item 2 stock restored by +20 |
| Revision adds item | Parent IN: item 1 qty=50. Revision: item 1 qty=50, item 2 qty=30. | item 2 stock increased by +30 |
| Downward IN correction fails on insufficient stock | Parent IN: item 1 qty=100. Stock consumed to 20. Revision: qty=50. Delta = −50, but only 20 available. | Approval fails, stock unchanged |
| Sibling revision blocked | Parent has one approved revision already. Second revision submitted. Attempt approve. | Returns validation error |
| Zero-delta skip | Revision has same qty as parent for all items. | Stock unchanged, approval succeeds |

**Step 6: Update documentation**

- `api-design.md`: In the revision approval section, add: *"Approving a revision applies the **net difference** between the revision's quantities and the parent's original quantities. The parent's effect is corrected, not duplicated."*
- `system-design.md` section 8.4: Replace ambiguous *"mutasi qty revisi diterapkan"* with explicit net-correction language.
- `data-dictionary.md`: Add a note about effective-transaction query rules for future reporting.

### E3. What NOT to Change

- **Parent `approval_status_id`**: Do not change it when a revision is approved. The parent stays APPROVED for audit trail purposes.
- **Schema**: No new columns, tables, or constraints needed for this fix.
- **Endpoint contracts**: Request/response shapes remain identical.
- **`createTransaction()`**: Optionally refactor to use `applySignedItemDelta()` for DRY, but this is a separate cleanup — not required for the fix.
- **`submitRevision()`**: No changes needed.
- **`rejectRevision()`**: No changes needed.

---

## F. Verification Plan

### F1. Automated Test Matrix

| Category | Test | Pass Criteria |
|---|---|---|
| **Correction: IN** | Parent IN +50, Revision qty=75 | Stock delta = +25 (not +75) |
| **Correction: OUT** | Parent OUT −30, Revision qty=40 | Stock delta = −10 (not −40) |
| **Correction: RETURN_IN** | Parent RETURN_IN +25, Revision qty=35 | Stock delta = +10 (not +35) |
| **Item removal** | Parent has items A,B. Revision has only A. | Item B effect reversed |
| **Item addition** | Parent has item A. Revision has items A,B. | Item B effect applied fresh |
| **Downward IN** | Parent IN +100. Stock at 20. Revision qty=50. | Fails: insufficient stock (delta = −50, only 20 available) |
| **Sibling block** | Approve revision, then try approving second revision for same parent | Second approval rejected with error |
| **Zero delta** | Revision has same quantities as parent | Approval succeeds, stock unchanged |
| **Existing: audit** | `testApproveRevisionWritesAuditLog` | Still passes (audit behavior unchanged) |
| **Existing: status** | `testApproveRevisionAlreadyApproved/Rejected` | Still passes (guard behavior unchanged) |
| **Existing: role** | `testApproveRevisionForbiddenForNonAdmin` | Still passes (access control unchanged) |

### F2. Manual Verification Steps

1. **Run full test suite**: `composer test` from `backend/` — all tests must pass.
2. **LSP diagnostics**: Zero errors on `StockTransactionService.php`, `StockTransactionModel.php`, `StockTransactionsTest.php`.
3. **Regression check**: Verify `createTransaction()` behavior is completely unaffected (existing create tests pass without modification).
4. **Edge case spot-check**: Manually trace through a RETURN_IN revision with item mismatch to confirm delta signs are correct.

### F3. Acceptance Criteria

- [ ] All 3 existing mutation tests updated and passing with delta assertions
- [ ] At least 5 new tests covering item mismatch, sibling block, insufficient stock, zero delta
- [ ] Sibling revision approval is blocked
- [ ] No schema migration needed
- [ ] No endpoint contract changes
- [ ] Documentation updated in all 3 docs files
- [ ] `composer test` passes with zero failures
- [ ] `items.qty` is never negative after any test sequence

---

## G. Decision Summary

### G1. Root Cause

`approveRevision()` copies `createTransaction()`'s mutation logic verbatim, applying revision quantities as new stock movements instead of computing the net correction relative to the parent. The tests codify this additive behavior as expected, masking the bug.

### G2. Chosen Fix

**Signed net delta correction** — compute `direction × (revisionQty − parentQty)` per item, apply only the difference. Zero schema changes, zero endpoint changes, one mutation per item, no intermediate state.

### G3. What Changes

| Component | Before | After |
|---|---|---|
| `approveRevision()` | Applies revision qty as additive mutation | Computes signed delta vs parent, applies difference only |
| `StockTransactionModel` | No sibling check | `findApprovedRevisionByParentId()` blocks double-approval |
| Tests (3 existing) | Assert additive behavior | Assert delta behavior |
| Tests (5+ new) | Don't exist | Cover item mismatch, sibling block, insufficient stock, zero delta |
| Documentation (3 files) | Ambiguous "applied" language | Explicit "net correction" semantics |

### G4. What Doesn't Change

- Schema (no migrations)
- Endpoint contracts (same request/response shapes)
- `createTransaction()`, `submitRevision()`, `rejectRevision()`
- Parent transaction status (stays APPROVED)
- Audit trail structure
- Access control

### G5. Risk Assessment

| Risk | Likelihood | Mitigation |
|---|---|---|
| Downward correction fails on consumed stock | Medium | Guard with `WHERE qty >= X`, return clear error |
| Legacy data with multiple approved revisions | Low (system is new) | Audit check before deploying; manual cleanup if found |
| Concurrent approval race | Low (admin-only, manual) | Sibling check + DB transaction; `SELECT FOR UPDATE` deferred to later hardening |
| Reporting double-count | Certain (if reports built naïvely) | Document effective-transaction query rule now; implement when reporting endpoints are built |

### G6. Escalation Triggers

Revisit with a schema change (e.g., `is_superseded` column) **only** if:
- Reporting endpoints need to filter superseded parents at the database level for performance.
- Business requires forced retroactive corrections even when stock has been consumed.
- Multi-level revision chains become a requirement.

None of these conditions exist today. The delta-based fix is sufficient.

### G7. Effort Estimate

**1–4 hours** including service changes, model method, test updates, new tests, and documentation updates.
