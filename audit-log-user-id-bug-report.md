# Audit Log Bug: "Sistem" instead of actual user name in Menu Core modules

## Problem

When users perform CRUD operations on Menu Core entities (dishes, dish compositions, menu packages, menu schedules), the audit log records `user_id = NULL`, causing the frontend to display **"Sistem"** as the officer name instead of the actual authenticated user.

## Root Cause

`AuditService::log()` accepts `?int $userId` as first parameter. Four **Service** classes hardcode `null` for this parameter, and their corresponding **Controllers** never extract or forward the authenticated user's identity or IP address.

The audit log display has a fallback at `AuditLogs.php:221`:

```php
$actorName = $row['user_name'] ?: 'Sistem';
```

When `user_id` is NULL, the LEFT JOIN to `users` yields NULL for `user_name`, so `'Sistem'` is used.

---

## Confirmed by API Test (localhost, 23 June 2026)

| Table | Operation | `actor` shown | `userId` in DB | Status |
|---|---|---|---|---|
| `items` | Create | `Admin User` | `1` | ✅ Correct (working reference) |
| `dishes` | Create | **`Sistem`** | **`NULL`** | ❌ Bug |
| `dish_compositions` | Create | **`Sistem`** | **`NULL`** | ❌ Bug |
| `menu_dishes` | (inferred from code) | **`Sistem`** | **`NULL`** | ❌ Bug |
| `menu_schedules` | (inferred from code) | **`Sistem`** | **`NULL`** | ❌ Bug |

### Test Commands Used

```bash
# Login
TOKEN=$(curl -s http://[::1]:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

# Create dish — actor recorded as "Sistem"
curl -s http://[::1]:8080/api/v1/dishes \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Test Dish Audit <timestamp>"}'

# Verify
curl -s "http://[::1]:8080/api/v1/audit-logs?table_name=dishes&sortBy=created_at&sortDir=DESC&perPage=1" \
  -H "Authorization: Bearer $TOKEN"

# Create item (working reference) — actor recorded as "Admin User"
curl -s http://[::1]:8080/api/v1/items \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Test Item Audit <timestamp>","item_category_id":1,"unit_base":"kg","unit_convert":"gram","conversion_base":1000,"min_stock":10}'
```

---

## Working Reference: Items Module

The `Items` controller correctly extracts auth context and forwards it to the service.

**`Controllers/Api/V1/Items.php:330-332`** — controller extracts auth context:

```php
$actor = auth()->user();
$actorId = $actor?->id;
$ipAddress = $this->request->getIPAddress();
$result = $this->itemService->createItem($data, $actorId, $ipAddress);
```

**`Services/ItemManagementService.php:117`** — service accepts and forwards:

```php
public function createItem(array $data, ?int $actorId = null, ?string $ipAddress = null): array
{
    // ...
    $this->auditService->log(
        $actorId,                           // was: null
        AuditActionType::Create,
        'items',
        (int) $created,
        'Created item ' . ($item['name'] ?? (string) $created),
        null,
        $item,
        $ipAddress,                         // was: null
    );
}
```

This pattern is consistently applied to `createItem`, `updateItem`, `deleteItem`, and `restoreItem` across both the controller and service.

---

## Affected Files

### Controllers — need to extract `$actorId` + `$ipAddress` and forward to services

| Controller | File | Affected Methods |
|---|---|---|
| `Dishes` | `Controllers/Api/V1/Dishes.php` | `create()`, `update()`, `delete()`, `deactivate()`, `reactivate()` |
| `DishCompositions` | `Controllers/Api/V1/DishCompositions.php` | `create()`, `update()`, `delete()` |
| `Menus` | `Controllers/Api/V1/Menus.php` | `assignSlot()`, `updateSlot()`, `deleteSlot()` |
| `MenuSchedules` | `Controllers/Api/V1/MenuSchedules.php` | `create()`, `update()` |

### Services — need to accept `$actorId`/`$ipAddress` and pass to audit log

| Service | File | Affected Methods | Audit log lines |
|---|---|---|---|
| `DishManagementService` | `Services/DishManagementService.php` | `createDish`, `updateDish`, `deleteDish`, `deactivateDish`, `reactivateDish` | 146, 220, 275, 335, 383 |
| `DishCompositionManagementService` | `Services/DishCompositionManagementService.php` | `createComposition`, `updateComposition`, `deleteComposition` | 165, 268, 311 |
| `MenuPackageManagementService` | `Services/MenuPackageManagementService.php` | `assignDishToSlot`, `updateSlotAssignment`, `deleteSlotAssignment` | 148, 314, 363 |
| `MenuScheduleManagementService` | `Services/MenuScheduleManagementService.php` | `createSchedule`, `updateSchedule` | 79, 177 |

**Total: 17 audit log call sites** passing `null` where `$userId`/`$ipAddress` should be.

---

## Fix Plan

### Step 1 — Controllers: Extract auth context

In every affected controller mutation method, add at the top (after JSON extraction):

```php
$actor = auth()->user();
$actorId = $actor?->id;
$ipAddress = $this->request->getIPAddress();
```

Then pass both as extra arguments to the service call:

```php
// Before
$result = $this->dishService->createDish($data);

// After
$result = $this->dishService->createDish($data, $actorId, $ipAddress);
```

### Step 2 — Services: Accept and forward auth params

For each affected service method, change the signature by adding two optional parameters:

```php
// Before
public function createDish(array $data): array

// After
public function createDish(array $data, ?int $actorId = null, ?string $ipAddress = null): array
```

Then replace every `null` first argument in `$this->auditService->log(...)` with `$actorId`, and every `null` last argument with `$ipAddress`:

```php
// Before
$this->auditService->log(null, AuditActionType::Create, 'dishes', (int) $created, 'Dish created.', null, ['name' => $name], null);

// After
$this->auditService->log($actorId, AuditActionType::Create, 'dishes', (int) $created, 'Dish created.', null, ['name' => $name], $ipAddress);
```

### Step 3 — Exclusions

- `ItemManagementService` + `Items` controller — already correct, no changes needed.
- `AuditService` + `AuditLogModel` — no changes needed; they already accept nullable params.
- No database schema changes.
- No frontend changes.

### Step 4 — Verification

After fix, create a dish and verify the audit log:

```bash
TOKEN=$(curl -s http://[::1]:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

# Create a dish
curl -s http://[::1]:8080/api/v1/dishes \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Post-fix test dish"}'

# Check audit log — should show actual user name, not "Sistem"
curl -s "http://[::1]:8080/api/v1/audit-logs?table_name=dishes&sortBy=created_at&sortDir=DESC&perPage=1" \
  -H "Authorization: Bearer $TOKEN" \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print(d['data'][0]['actor'] if d['data'] else 'no data')"
# Expected output: "Admin User"
```

---

## Summary

| Aspect | Detail |
|---|---|
| **Bug type** | `user_id` passed as `null` in audit log calls for Menu Core services |
| **Scope** | 4 controllers + 4 services, 17 audit log call sites |
| **Fix pattern** | Follow existing `Items` module — extract `auth()->user()` in controller, pass through service to `AuditService::log()` |
| **Risk** | Low — all new params are nullable with defaults, backward compatible |
| **DB changes** | None |
| **Frontend changes** | None |
