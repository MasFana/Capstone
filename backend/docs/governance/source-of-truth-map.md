# Source-of-Truth Mapping Matrix

This matrix maps planned documentation domains to implementation sources in the current backend runtime. Use these paths as canonical verification points before authoring or revising docs.

## Roles

- Primary route and access control authority:
  - `backend/app/Config/Routes.php` (role filters: `role:admin,dapur,gudang`, `role:admin,gudang`, `role:admin,dapur`, `role:admin`)
  - `backend/app/Filters/RoleFilter.php` (enforcement for active user and allowed role names)
- Role identity and bootstrap:
  - `backend/app/Database/Seeds/RoleSeeder.php` (seeded role names: `admin`, `dapur`, `gudang`)
  - `backend/app/Controllers/Api/V1/Auth.php` + `backend/app/Services/AuthService.php` (login/me/logout/change password flow and role payload in user response)

### Validation query hints

- `rg "filter" backend/app/Config/Routes.php | rg "role:"`
- `rg "class RoleFilter|Insufficient permissions|Account is inactive" backend/app/Filters/RoleFilter.php`
- `rg "admin|dapur|gudang" backend/app/Database/Seeds/RoleSeeder.php`

## Stock transactions

- Endpoint surface:
  - `backend/app/Config/Routes.php` (`stock-transactions`, `stock-transactions/(:num)/details`, `stock-transactions/(:num)/submit-revision`, `stock-transactions/direct-corrections`, `stock-transactions/(:num)/approve`, `stock-transactions/(:num)/reject`)
  - `backend/app/Controllers/Api/V1/StockTransactions.php` (index/create/show/details/submitRevision/approve/reject/directCorrection)
- Mutation semantics and constraints:
  - `backend/app/Services/StockTransactionService.php` (supported types, forbidden fields, revision lifecycle, qty updates)
  - `backend/app/Models/TransactionTypeModel.php` (`NAME_IN`, `NAME_OUT`, `NAME_RETURN_IN`)
  - `backend/app/Models/ApprovalStatusModel.php` (`NAME_APPROVED`, `NAME_PENDING`, `NAME_REJECTED`)

### Validation query hints

- `rg "stock-transactions|submit-revision|direct-corrections" backend/app/Config/Routes.php`
- `rg "function (create|directCorrection|submitRevision|approve|reject)" backend/app/Controllers/Api/V1/StockTransactions.php`
- `rg "SUPPORTED_TRANSACTION_TYPES|NAME_IN|NAME_OUT|NAME_RETURN_IN" backend/app/Services/StockTransactionService.php backend/app/Models/TransactionTypeModel.php`
- `rg "NAME_APPROVED|NAME_PENDING|NAME_REJECTED" backend/app/Models/ApprovalStatusModel.php`

## Stock opname

- Endpoint surface:
  - `backend/app/Config/Routes.php` (`stock-opnames`, `stock-opnames/(:num)`, `stock-opnames/(:num)/submit`, `stock-opnames/(:num)/approve`, `stock-opnames/(:num)/reject`, `stock-opnames/(:num)/post`)
  - `backend/app/Controllers/Api/V1/StockOpnames.php` (create/show/submit/approve/reject/post)
- State machine and posting behavior:
  - `backend/app/Services/StockOpnameService.php` (state transition validation and posting transaction generation)
  - `backend/app/Models/StockOpnameModel.php` (`STATE_DRAFT`, `STATE_SUBMITTED`, `STATE_APPROVED`, `STATE_REJECTED`, `STATE_POSTED`)

### Validation query hints

- `rg "stock-opnames" backend/app/Config/Routes.php`
- `rg "function (create|show|submit|approve|reject|post)" backend/app/Controllers/Api/V1/StockOpnames.php`
- `rg "STATE_DRAFT|STATE_SUBMITTED|STATE_APPROVED|STATE_REJECTED|STATE_POSTED" backend/app/Models/StockOpnameModel.php backend/app/Services/StockOpnameService.php`

## SPK basah

- Endpoint surface:
  - `backend/app/Config/Routes.php` (`spk/basah/menu-calendar`, `spk/basah/generate`, `spk/basah/operational-stock-preview`, `spk/basah/history`, `spk/basah/history/(:num)`, `spk/basah/history/(:num)/post-stock`, `spk/basah/history/(:num)/override`)
  - `backend/app/Controllers/Api/V1/SpkBasah.php` (menuCalendarProjection/generate/history/show/postStock/overrideItem/operationalStockPreview)
- Generation and stock-post integration:
  - `backend/app/Services/SpkBasahGenerationService.php`
  - `backend/app/Services/SpkStockPostingService.php`
  - `backend/app/Services/SpkOverrideService.php`

### Validation query hints

- `rg "spk/basah" backend/app/Config/Routes.php`
- `rg "function (menuCalendarProjection|generate|history|show|postStock|overrideItem|operationalStockPreview)" backend/app/Controllers/Api/V1/SpkBasah.php`
- `rg "class SpkBasahGenerationService|class SpkStockPostingService|class SpkOverrideService" backend/app/Services/*.php`

## SPK kering-pengemas

- Endpoint surface:
  - `backend/app/Config/Routes.php` (`spk/kering-pengemas/menu-calendar`, `spk/kering-pengemas/generate`, `spk/kering-pengemas/history`, `spk/kering-pengemas/history/(:num)`, `spk/kering-pengemas/history/(:num)/post-stock`, `spk/kering-pengemas/history/(:num)/override`)
  - `backend/app/Controllers/Api/V1/SpkKeringPengemas.php` (menuCalendarProjection/generate/history/show/postStock/overrideItem)
- Generation and stock-post integration:
  - `backend/app/Services/SpkKeringPengemasGenerationService.php`
  - `backend/app/Services/SpkStockPostingService.php`
  - `backend/app/Services/SpkOverrideService.php`

### Validation query hints

- `rg "spk/kering-pengemas" backend/app/Config/Routes.php`
- `rg "function (menuCalendarProjection|generate|history|show|postStock|overrideItem)" backend/app/Controllers/Api/V1/SpkKeringPengemas.php`
- `rg "class SpkKeringPengemasGenerationService|class SpkStockPostingService|class SpkOverrideService" backend/app/Services/*.php`

## Menu planning

- Endpoint surface:
  - `backend/app/Config/Routes.php` (`menus`, `menu-dishes`, `menu-schedules`, `menu-schedules/(:num)`, `menu-calendar`, `daily-patients`, `daily-patients/(:num)`)
  - `backend/app/Controllers/Api/V1/Menus.php` (index/slots/assignSlot)
  - `backend/app/Controllers/Api/V1/MenuSchedules.php` (index/show/create/update/calendarProjection)
- Supporting service contracts:
  - `backend/app/Services/MenuPackageManagementService.php`
  - `backend/app/Services/MenuScheduleManagementService.php`

### Validation query hints

- `rg "menus|menu-dishes|menu-schedules|menu-calendar|daily-patients" backend/app/Config/Routes.php`
- `rg "function (index|slots|assignSlot)" backend/app/Controllers/Api/V1/Menus.php`
- `rg "function (index|show|create|update|calendarProjection)" backend/app/Controllers/Api/V1/MenuSchedules.php`
- `rg "class MenuPackageManagementService|class MenuScheduleManagementService" backend/app/Services/*.php`

## Errors

- Shared API error shape and status decisions:
  - `backend/app/Controllers/Api/V1/StockTransactions.php` (`Validation failed.`, `Unauthorized.`, `Stock transaction not found.` with 400/401/404)
  - `backend/app/Controllers/Api/V1/StockOpnames.php` (`Unauthorized.` and service-driven `status` propagation)
  - `backend/app/Controllers/Api/V1/SpkBasah.php` + `backend/app/Controllers/Api/V1/SpkKeringPengemas.php` (400 validation failures, 401 unauthenticated, 404 history-not-found)
- Domain/business validation sources:
  - `backend/app/Services/StockTransactionService.php` (unknown fields, invalid type/state, insufficient stock, revision not found)
  - `backend/app/Services/StockOpnameService.php` (invalid state transitions, missing rejection reason, insufficient stock on posting)

### Validation query hints

- `rg "Validation failed\.|Unauthorized\.|not found\." backend/app/Controllers/Api/V1/*.php`
- `rg "Insufficient stock|Invalid state transition|Unknown field\(s\)" backend/app/Services/StockTransactionService.php backend/app/Services/StockOpnameService.php`
