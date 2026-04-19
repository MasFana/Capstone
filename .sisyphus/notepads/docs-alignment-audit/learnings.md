## 2026-04-19T16:55:00Z Task: bootstrap
Initialized notepad for docs-alignment-audit.

## 2026-04-19 Task 1: Runtime + SDK Truth Matrix
- Added section  in  with exact runtime routes, actor gates, controller/service ownership, and exact frontend SDK methods for domains: items, auth/password, daily-patients, spk, stock-opnames, dashboard, reports.
- Added  in  mapping each required domain to backend runtime and frontend SDK truth source files.
- Confirmed api-contract cross-reference to  now resolves to an existing heading in runtime-status.

## 2026-04-19 Task 1: Runtime + SDK Truth Matrix (correction)
- Added section "4.2 Compact Runtime Cross-Reference Matrix" in "backend/docs/architecture/runtime-status.md" with exact runtime routes, actor gates, controller/service ownership, and exact frontend SDK methods for domains: items, auth/password, daily-patients, spk, stock-opnames, dashboard, reports.
- Added "Runtime + SDK Truth Matrix (compact)" in "backend/docs/governance/source-of-truth-map.md" mapping each required domain to backend runtime and frontend SDK truth source files.
- Confirmed api-contract cross-reference to "4.2 Compact Runtime Cross-Reference Matrix" resolves to an existing heading in runtime-status.

## 2026-04-19 Task 4: Route↔Doc and SDK↔Doc Spot-Check Matrix
- Added "Task 4 Spot-Check Matrix — Route↔Doc and SDK↔Doc" in "backend/docs/governance/source-of-truth-map.md" with 20 sampled checks and verification-friendly columns: route / SDK method, implementation source, doc location, and status.
- Included explicit coverage rows for the mandatory terms: auth/password, items CRUD+restore, dailyPatients list/get/create, SPK extended methods, dashboard, reports, and stockOpnames.
- Created evidence files under ".sisyphus/evidence/" to capture the spot-check matrix artifact and any future verification errors.

## 2026-04-19 Task 2: Actor Model Documentation Normalization
- Formalized the two-layer actor model: Shield Auth Groups (kredensial) vs App Roles (logika bisnis).
- Source of truth for Shield groups is .
- Source of truth for App roles is , enforced via  and referenced in .
- Target files normalized: , , and all  quickstarts (, , ).
- Confirmed that "Shield Groups" and "App Roles" are consistently distinguished to avoid ambiguity between system-level auth and application-level access gates.

## 2026-04-19 Task 2: Actor Model Documentation Normalization
- Formalized the two-layer actor model: Shield Auth Groups (kredensial) vs App Roles (logika bisnis).
- Source of truth for Shield groups is app/Config/AuthGroups.php.
- Source of truth for App roles is app/Database/Seeds/RoleSeeder.php, enforced via app/Filters/RoleFilter.php and referenced in app/Config/Routes.php.
- Target files normalized: backend/docs/architecture/runtime-status.md, backend/docs/reference/api-contract.md, and all by-user quickstarts (admin, dapur, gudang).
- Confirmed that 'Shield Groups' and 'App Roles' are consistently distinguished to avoid ambiguity between system-level auth and application-level access gates.
### Successful Approaches
- Structured feature documentation by domain provides a middle ground between role-specific quickstarts and technical workflow guides.
- Using a centralized Feature Index (README.md) allows for better discoverability and cross-linking.
### Documentation Alignment Learnings
- Corrected relative links in backend/docs/ to avoid stale 'docs/' prefixing within the same directory tree.
- Replaced legacy file references with links to changelog and migration-map for better context.
Learnings from Item CRUD Lifecycle Task:
- Item names are globally unique including soft-deleted rows.
- Restoration is required if a name is already taken by a deleted item (handled by restore_id in validation).
- units and categories are resolved by name case-insensitively.
- is_active defaults to true on create.
- Stock quantity (qty) is strictly read-only and managed via transactions/opname.

## 2026-04-19 Task 7: Reference Docs Runtime Alignment
- Runtime tiebreakers reconfirmed from app/Config/Routes.php + controllers/services: AuthService, StockTransactionService, StockOpnameService, SpkStockPostingService, Reports.
- Corrected semantic drift in backend/docs/reference/api-contract.md: SPK  now documented as stock transaction  posting with finalization  (matching SpkStockPostingService + route actions).
- Corrected schema reference links in backend/docs/reference/schema.md (, ) and refreshed open questions to remove stale already-implemented concerns.
- Clarified FK/reference integrity wording in backend/docs/reference/errors.md for unit resolution (/ -> item unit IDs).
- Updated backend/docs/governance/source-of-truth-map.md spot-check section pointers to current api-contract section numbering and labels.
- Verification evidence generated via required command at .sisyphus/evidence/task-7-reference-alignment.txt with empty stderr file.

## 2026-04-19 Task 7: Notepad Correction
- Previous appended lines lost inline literals due shell interpolation of backticks.
- Correct statement: SPK post-stock is documented as posting recommendations into stock transactions with IN mutation semantics and finalizing is_finish=true.
- Correct statement: schema references now point to api-contract.md and ../architecture/runtime-status.md.
- Correct statement: errors reference clarifies unit_base/unit_convert resolution to item unit IDs.
- Correct statement: source-of-truth map spot-check doc pointers now match current api-contract section labels.

## 2026-04-19 Task 8: Governance for Ownership, Review Cadence, and Canonical Precedence
- Added "Canonical Precedence" section to backend/docs/governance/source-of-truth-map.md clarifying that canonical docs live in backend/docs/ only, with explicit rules for precedence.
- Added "Ownership and Review Responsibilities" table in source-of-truth-map.md mapping each doc scope to owner, reviewer, and responsibility.
- Added "Review Cadence" section defining per-release, per-feature, quarterly, and spot-check verification schedules.
- Added "Canonical Precedence Rules" section with 5 binding rules: implementation is source of truth, runtime docs override planned, canonical docs override legacy, governance docs are binding, no duplicate truth.
- Updated backend/docs/governance/doc-templates.md with new section 1.1 "Governance Requirements" requiring ownership, canonical status, implementation grounding, review cadence, and last-updated date.
- Updated verification checklist in doc-templates.md to include 9 items covering governance requirements (ownership, canonical status, implementation grounding, review cadence, last-updated).
- Added new governance policy entry to backend/docs/governance/changelog.md dated 2026-04-19 documenting all ownership, review cadence, and canonical precedence policies.
- Updated backend/docs/README.md with "Canonical Precedence" section at top clarifying that backend/docs/ is canonical and implementation sources are source of truth.
- Verification grep confirmed 47 matches for "owner|review cadence|source of truth|canonical" across governance docs with zero errors.
- Evidence files created: .sisyphus/evidence/task-8-governance-alignment.txt (47 lines) and .sisyphus/evidence/task-8-governance-alignment-error.txt (0 lines).
### Task 9 Learnings
- Successfully aligned Frontend SDK README with exported surface.
- Documented auth.changePassword, dailyPatients extensions, and SPK stock posting.
- Added representation for dashboard, reports, and stockOpnames.

## [2026-04-19] SRS Archive (Task 10)
- SRS has been archived to `backend/docs/archive/` with ARCHIVED banners and canonical redirects.
- Standard archive pattern: ARCHIVED header -> Redirects to Canonical -> Original Content.
- Governance changelog updated to reflect this transition.

### Verification Results for Task 11

- System Request.md has been moved from the root to backend/docs/archive/.
- Archived document includes standard Caution banner and canonical redirects to README.md, api-contract.md, runtime-status.md, and schema.md.
- backend/docs/archive/README.md has been updated to include System Request.md in Current Archive Contents.
- No active references to 'System Request' were found in non-archived backend/docs/ files.

## 2026-04-19 Task 12: Final Documentation Consistency Sweep
- Final validation passed after filtering the `rtk git diff --name-only` banner line `--- Changes ---`, which is tool output rather than a file path.
- Required term coverage remained coherent across backend docs and `frontend/README.md` for `auth/password`, `items`, `dailyPatients`, `stock-opnames`, `spk`, `dashboard`, and `reports`.
- Legacy root docs (`Software Requirements Specification (SRS).md`, `System Request.md`) are intentionally absent from the active tree and should be treated as moved/archived references during doc sweeps.

## 2026-04-19: Defect Fixes for F2/F3
- Corrected SPK post-stock permissions in `frontend/README.md` to `admin` only, aligning with `spk.ts` SDK resources.
- Resolved broken link in `backend/docs/guides/by-feature/README.md` by pointing to the correct section anchor in the top-level documentation index.
- Cleaned up `backend/docs/archive/README.md` by removing stale "planned to move" entries for documents that were already archived.

## 2026-04-19: Final Defect Fixes for F2/F3
- Corrected non-canonical SDK method names for `dashboard`, `reports`, and `stockOpnames` resources in `frontend/README.md`.
- Removed the stale "Planned Archive Candidates (Not Yet Moved)" section from `backend/docs/archive/README.md` to ensure documentation reflects the current state of the archival process.
