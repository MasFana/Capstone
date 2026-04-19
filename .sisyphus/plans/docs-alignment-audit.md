# Documentation Alignment with Runtime + SDK Truth

## TL;DR
> **Summary**: Align all project documentation to current implemented behavior across backend runtime, user/feature workflows, reference/governance docs, and frontend SDK surface.
> **Deliverables**:
> - Updated canonical backend docs (`architecture`, `guides`, `reference`, `governance`)
> - New/updated feature-oriented workflow coverage including item CRUD lifecycle
> - Legacy docs archived under `/docs/archive` with canonical pointers
> - Frontend SDK README aligned to actual exported API surface
> - Verification evidence (links + route/SDK spot-check matrix)
> **Effort**: Large
> **Parallel**: YES - 3 waves
> **Critical Path**: 1 → 4 → 7 → 10 → 11

## Context
### Original Request
Analyze and correct documentation so runtime truth includes item CRUD, actor model is accurate, guides cover complete workflows by feature and by user, reference/governance are aligned with current implementation, then cross-check frontend SDK docs.

### Interview Summary
- Runtime and workflow truth source is backend routes/controllers/services/models.
- Existing docs are stronger by-user and by-workflow, but weak on by-feature mapping and consolidated item lifecycle documentation.
- Legacy SRS/SR docs must be archived and removed from canonical flow.
- Frontend SDK README alignment is in-scope.
- Verification depth is strict: link checks plus backend-route and SDK-export spot-check matrix.

### Metis Review (gaps addressed)
- Added hard guardrail to prevent code/config changes; markdown-only scope.
- Added explicit anti-scope-creep rule: document current reality, do not fix runtime code.
- Added concrete automated acceptance checks (`grep`/scripted checks), no human-only verification.
- Added order-of-operations to avoid drift while editing large/legacy docs.

## Work Objectives
### Core Objective
Produce a decision-complete, implementation-accurate documentation set that reflects the current backend runtime and frontend SDK behavior.

### Deliverables
- Updated `backend/docs/architecture/runtime-status.md`
- Updated `backend/docs/guides/README.md` and role/workflow docs
- Added/updated feature-oriented docs, including a dedicated item CRUD lifecycle guide
- Updated `backend/docs/reference/api-contract.md` and related references
- Updated governance docs to remove stale references and enforce source-of-truth map
- Updated `frontend/README.md` to match SDK exports/resources/methods
- Archived legacy docs under `backend/docs/archive/` and linked to canonical docs
- Spot-check matrix artifact in docs proving route↔doc and SDK↔doc alignment

### Definition of Done (verifiable conditions with commands)
- All targeted docs updated and contain required sections.
  - `git diff --name-only | grep -E '(^backend/docs/|^frontend/README.md$)'`
- No code/config files changed.
  - `git diff --name-only | grep -Ev '(^backend/docs/|^frontend/README.md$)'` returns empty
- Runtime and SDK spot-check terms present in docs.
  - `grep -R "auth/password\|items\|stock-opnames\|spk" backend/docs frontend/README.md`
- Broken local markdown links in touched docs = zero.
  - `python3 - <<'PY'\nimport re, pathlib\n# executor: validate local markdown links in touched docs and fail on missing files\nPY`

### Must Have
- Runtime actor model reflects actual auth groups + app roles.
- Item CRUD fully documented (create/read/update/delete/restore + constraints/permissions).
- Workflow coverage presented both by user role and by feature.
- Reference docs and governance map aligned to current file structure and section anchors.
- Frontend SDK README fully matches current exports/methods.
- Legacy SRS/SR are archived in `/docs/archive` with explicit canonical replacements.

### Must NOT Have (guardrails, AI slop patterns, scope boundaries)
- Must NOT modify runtime source code, SDK source code, or config files.
- Must NOT preserve stale links/anchors to removed legacy docs.
- Must NOT add speculative behavior not backed by routes/controllers/SDK exports.
- Must NOT use vague acceptance criteria (e.g., “looks good”, “clear enough”).

## Verification Strategy
> ZERO HUMAN INTERVENTION - all verification is agent-executed.
- Test decision: tests-after (documentation verification) using Bash + scripted checks
- QA policy: Every task includes happy-path and failure/edge-case QA scenarios
- Evidence: `.sisyphus/evidence/task-{N}-{slug}.{ext}`

## Execution Strategy
### Parallel Execution Waves
> Target: 5-8 tasks per wave. <3 per wave (except final) = under-splitting.
> Extract shared dependencies as Wave-1 tasks for max parallelism.

Wave 1: truth extraction + alignment matrix foundation (Tasks 1-4)
Wave 2: backend canonical docs alignment (Tasks 5-8)
Wave 3: frontend + legacy archive alignment + final consistency pass (Tasks 9-12)

### Dependency Matrix (full, all tasks)
- 1 blocks 2,3,4,5,6,7,8,9,10,11,12
- 2 blocks 5,6,7,8
- 3 blocks 5,6
- 4 blocks 9
- 5 blocks 8,12
- 6 blocks 12
- 7 blocks 10,11,12
- 8 blocks 12
- 9 blocks 12
- 10 blocks 12
- 11 blocks 12
- 12 precedes Final Verification Wave

### Agent Dispatch Summary (wave → task count → categories)
- Wave 1 → 4 tasks → `deep`, `writing`, `quick`
- Wave 2 → 4 tasks → `writing`, `unspecified-low`, `deep`
- Wave 3 → 4 tasks → `writing`, `quick`, `deep`

## TODOs
> Implementation + Test = ONE task. Never separate.
> EVERY task MUST have: Agent Profile + Parallelization + QA Scenarios.

- [x] 1. Build Runtime + SDK Truth Matrix

  **What to do**: Extract the canonical implementation surface into a doc-facing matrix using `backend/app/Config/Routes.php`, role/auth files, selected controllers/services/models, and `frontend/src/sdk/index.ts` + resources. Record route groups, actors/permissions, CRUD surfaces, and SDK method exports that documentation must reflect.
  **Must NOT do**: Do not alter source code or infer undocumented behavior not present in implementation.

  **Recommended Agent Profile**:
  - Category: `deep` - Reason: Requires cross-file synthesis and accuracy under multiple truth sources.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['playwright']` - Not needed for documentation truth extraction.

  **Parallelization**: Can Parallel: NO | Wave 1 | Blocks: [2,3,4,5,6,7,8,9,10,11,12] | Blocked By: []

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `backend/app/Config/Routes.php` - primary runtime route map and access boundaries.
  - API/Type: `backend/app/Config/AuthGroups.php` - auth group capabilities.
  - API/Type: `backend/app/Filters/RoleFilter.php` - role enforcement behavior.
  - API/Type: `backend/app/Database/Seeds/RoleSeeder.php` - active role values.
  - Pattern: `backend/app/Controllers/Api/V1/Items.php` - item CRUD/restore endpoints.
  - API/Type: `backend/app/Services/ItemManagementService.php` - item business constraints.
  - API/Type: `frontend/src/sdk/index.ts` - exported SDK surface.
  - Pattern: `frontend/src/sdk/resources/*.ts` - per-resource method coverage.

  **Acceptance Criteria** (agent-executable only):
  - [ ] A matrix section exists in docs listing roles, feature modules, endpoints, and corresponding SDK resources.
  - [ ] Matrix includes explicit rows for `items`, `auth/password`, `daily-patients`, `spk`, `stock-opnames`, `dashboard`, `reports`.
  - [ ] `grep -R "auth/password\|stock-opnames\|daily-patients\|spk\|dashboard\|reports" backend/docs` returns expected matrix references.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path truth extraction
    Tool: Bash
    Steps: Parse runtime routes and SDK exports; generate/update docs matrix section; run grep for required route/sdk terms.
    Expected: Matrix contains all required terms and maps each to actor + SDK surface.
    Evidence: .sisyphus/evidence/task-1-truth-matrix.txt

  Scenario: Failure/edge case stale inference blocked
    Tool: Bash
    Steps: Check matrix rows for terms not present in routes or SDK exports (e.g., typo/nonexistent endpoint).
    Expected: No matrix rows reference nonexistent routes/methods.
    Evidence: .sisyphus/evidence/task-1-truth-matrix-error.txt
  ```

  **Commit**: NO | Message: `docs(runtime): add implementation truth matrix` | Files: [`backend/docs/**`]

- [x] 2. Normalize Actor Model Documentation

  **What to do**: Update role/actor documentation so all docs consistently distinguish auth groups/permissions from app roles (`admin`, `dapur`, `gudang`) and document where each is enforced.
  **Must NOT do**: Do not merge distinct concepts into one role model; do not invent roles not present in seeds/filters.

  **Recommended Agent Profile**:
  - Category: `writing` - Reason: Structured doc harmonization across multiple pages.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['ultrabrain']` - No hard architecture redesign needed.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [5,6,7] | Blocked By: [1]

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `backend/docs/guides/by-user/admin-quickstart.md` - actor-facing messaging style.
  - Pattern: `backend/docs/guides/by-user/dapur-quickstart.md`
  - Pattern: `backend/docs/guides/by-user/gudang-quickstart.md`
  - API/Type: `backend/app/Config/AuthGroups.php` - auth group truth.
  - API/Type: `backend/app/Filters/RoleFilter.php` - app role truth.
  - API/Type: `backend/app/Database/Seeds/RoleSeeder.php` - seeded roles.

  **Acceptance Criteria** (agent-executable only):
  - [ ] Guides and runtime/reference docs explicitly state auth groups vs app roles.
  - [ ] `grep -R "AuthGroups\|RoleFilter\|admin\|dapur\|gudang" backend/docs` shows coherent usage.
  - [ ] No doc claims roles not present in `RoleSeeder`.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path actor consistency
    Tool: Bash
    Steps: Update role sections across docs; run grep for role vocabulary and enforcement references.
    Expected: All actor docs consistently map to AuthGroups/RoleFilter without contradictions.
    Evidence: .sisyphus/evidence/task-2-actor-model.txt

  Scenario: Failure/edge case conflicting role names
    Tool: Bash
    Steps: Search for deprecated/incorrect role names or mixed terms across docs.
    Expected: No unsupported role names remain in touched docs.
    Evidence: .sisyphus/evidence/task-2-actor-model-error.txt
  ```

  **Commit**: NO | Message: `docs(guides): normalize actor model definitions` | Files: [`backend/docs/guides/**`, `backend/docs/architecture/**`, `backend/docs/reference/**`]

- [x] 3. Produce By-Feature Workflow Coverage Layer

  **What to do**: Add or update feature-oriented docs index and coverage so app workflows are discoverable by feature domain (auth, users/roles, items/master data, stock tx, SPK, stock opname, menu planning, dashboard, reports), complementing existing by-user and by-workflow docs.
  **Must NOT do**: Do not remove existing by-user/by-workflow structure; extend with cross-links.

  **Recommended Agent Profile**:
  - Category: `writing` - Reason: Documentation taxonomy and information architecture work.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['quick']` - Work spans multiple docs and requires structural consistency.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [5,7,10] | Blocked By: [1]

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `backend/docs/README.md` - top-level index conventions.
  - Pattern: `backend/docs/guides/README.md` - current by-user/by-workflow IA.
  - Pattern: `backend/docs/guides/by-workflow/*.md` - workflow doc style.
  - Pattern: `backend/app/Config/Routes.php` - feature scope truth.

  **Acceptance Criteria** (agent-executable only):
  - [ ] A feature-oriented index section/page exists and is linked from `backend/docs/README.md` and `backend/docs/guides/README.md`.
  - [ ] Feature list includes: auth, users/roles, items/master data, stock transactions, SPK, stock opname, menu planning, dashboard, reports.
  - [ ] `grep -R "By Feature\|auth\|items\|stock transactions\|SPK\|stock opname\|dashboard\|reports" backend/docs/guides backend/docs/README.md` returns expected entries.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path feature discoverability
    Tool: Bash
    Steps: Add feature index/cross-links; validate links and required feature keywords in index docs.
    Expected: Reader can navigate docs by user, workflow, and feature from index pages.
    Evidence: .sisyphus/evidence/task-3-feature-layer.txt

  Scenario: Failure/edge case orphan feature docs
    Tool: Bash
    Steps: Check for feature pages not linked from top-level indexes.
    Expected: No orphan feature docs in touched scope.
    Evidence: .sisyphus/evidence/task-3-feature-layer-error.txt
  ```

  **Commit**: NO | Message: `docs(guides): add by-feature workflow coverage` | Files: [`backend/docs/README.md`, `backend/docs/guides/**`]

- [x] 4. Build Route↔Doc and SDK↔Doc Spot-Check Matrix

  **What to do**: Add strict verification matrix documenting sampled backend routes and frontend SDK methods with corresponding doc locations; include pass/fail criteria and evidence paths.
  **Must NOT do**: Do not claim full formal verification of every endpoint unless actually covered.

  **Recommended Agent Profile**:
  - Category: `quick` - Reason: Focused artifact creation with deterministic checks.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['oracle']` - Not needed for mechanical mapping task.

  **Parallelization**: Can Parallel: YES | Wave 1 | Blocks: [9,12] | Blocked By: [1]

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `backend/docs/governance/source-of-truth-map.md` - existing mapping structure.
  - API/Type: `backend/app/Config/Routes.php` - route source.
  - API/Type: `frontend/src/sdk/index.ts` - SDK export source.
  - Pattern: `frontend/src/sdk/resources/*.ts` - method-level source.

  **Acceptance Criteria** (agent-executable only):
  - [ ] Matrix includes at least 15 high-risk/high-visibility checks across runtime + SDK.
  - [ ] Includes mandatory checks: `auth/password`, `items` CRUD+restore, `dailyPatients` list/get/create, SPK extended methods, `dashboard`, `reports`, `stockOpnames`.
  - [ ] `grep -R "auth/password\|dailyPatients\|stockOpnames\|postBasahStock\|postKeringPengemasStock\|dashboard\|reports" backend/docs frontend/README.md` confirms mapped terms.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path strict spot-check matrix
    Tool: Bash
    Steps: Build matrix table and run grep-based checks for each mandatory term.
    Expected: Every mandatory term maps to both implementation source and doc location.
    Evidence: .sisyphus/evidence/task-4-spotcheck-matrix.txt

  Scenario: Failure/edge case unmapped mandatory term
    Tool: Bash
    Steps: Temporarily evaluate matrix for missing mandatory term coverage.
    Expected: Missing term triggers explicit fail row; then corrected before completion.
    Evidence: .sisyphus/evidence/task-4-spotcheck-matrix-error.txt
  ```

  **Commit**: NO | Message: `docs(governance): add route/sdk spot-check matrix` | Files: [`backend/docs/governance/**`, `backend/docs/reference/**`]

- [x] 5. Correct Runtime Status and Remove Stale Anchors/Links

  **What to do**: Update `backend/docs/architecture/runtime-status.md` to reflect current section names/content, remove stale references to removed legacy files, and align wording to current runtime status.
  **Must NOT do**: Do not preserve references to deleted docs; do not rename sections without updating inbound references.

  **Recommended Agent Profile**:
  - Category: `writing` - Reason: Content correction + anchor/link hygiene in architecture docs.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['deep']` - Truth already established in Task 1.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [8,12] | Blocked By: [1,2,3]

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `backend/docs/architecture/runtime-status.md` - target runtime status page.
  - Pattern: `backend/docs/governance/changelog.md` - removed-legacy-doc history.
  - API/Type: `backend/app/Config/Routes.php` - current runtime truth.

  **Acceptance Criteria** (agent-executable only):
  - [ ] No references to removed legacy file paths remain in runtime-status.
  - [ ] Runtime-status sections and labels referenced by other docs exist and match.
  - [ ] `grep -R "use-case-diagram\.md\|Compact Runtime Cross-Reference Matrix" backend/docs/architecture/runtime-status.md backend/docs/reference/api-contract.md` returns only valid/current usages.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path runtime-status alignment
    Tool: Bash
    Steps: Update runtime-status content/anchors; validate references from api-contract and docs index.
    Expected: No stale anchors/removed links; references resolve to current sections.
    Evidence: .sisyphus/evidence/task-5-runtime-status.txt

  Scenario: Failure/edge case stale anchor regression
    Tool: Bash
    Steps: Search for old section names/removed paths after edits.
    Expected: Zero stale anchor/path matches in touched architecture/reference docs.
    Evidence: .sisyphus/evidence/task-5-runtime-status-error.txt
  ```

  **Commit**: NO | Message: `docs(architecture): fix runtime status stale links/anchors` | Files: [`backend/docs/architecture/runtime-status.md`, `backend/docs/reference/**`]

- [x] 6. Add Dedicated Item CRUD Lifecycle Documentation

  **What to do**: Create/update a single canonical item lifecycle doc covering item create/read/update/delete/restore behavior, constraints, role access, and links to API/reference and related workflows.
  **Must NOT do**: Do not scatter item lifecycle details across many pages without canonical pointer.

  **Recommended Agent Profile**:
  - Category: `writing` - Reason: Consolidation of fragmented behavior into authoritative guide.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['quick']` - Requires cross-doc linking and role constraints.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [12] | Blocked By: [1,2]

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `backend/app/Controllers/Api/V1/Items.php` - endpoint behavior.
  - API/Type: `backend/app/Services/ItemManagementService.php` - business constraints.
  - API/Type: `backend/app/Models/ItemModel.php` - persistence assumptions.
  - Pattern: `backend/docs/reference/api-contract.md` - API documentation style.
  - Pattern: `backend/docs/guides/by-workflow/*.md` - workflow-guide style.
  - API/Type: `frontend/src/sdk/resources/items.ts` - SDK method surface.

  **Acceptance Criteria** (agent-executable only):
  - [ ] Canonical item lifecycle section/page exists and is linked from docs indexes.
  - [ ] Includes create/update/delete/restore semantics and role constraints.
  - [ ] Includes explicit mapping to backend endpoints and SDK methods.
  - [ ] `grep -R "items\|restore\|role\|create\|update\|delete" backend/docs frontend/README.md` confirms canonical coverage.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path canonical item lifecycle
    Tool: Bash
    Steps: Add lifecycle page/section and index links; verify required lifecycle keywords and endpoint/sdk references.
    Expected: Single canonical item CRUD lifecycle narrative exists and is discoverable.
    Evidence: .sisyphus/evidence/task-6-item-crud-lifecycle.txt

  Scenario: Failure/edge case fragmented item docs
    Tool: Bash
    Steps: Check for contradictory item behavior claims across guides/reference/runtime docs.
    Expected: No contradictory item lifecycle claims in touched docs.
    Evidence: .sisyphus/evidence/task-6-item-crud-lifecycle-error.txt
  ```

  **Commit**: NO | Message: `docs(guides): add canonical item CRUD lifecycle` | Files: [`backend/docs/guides/**`, `backend/docs/reference/**`, `backend/docs/README.md`]

- [x] 7. Align Reference Docs with Current Runtime Surface

  **What to do**: Update `backend/docs/reference/api-contract.md`, `schema.md`, and `errors.md` cross-links and endpoint/resource descriptions to match current runtime modules and section anchors.
  **Must NOT do**: Do not expand into speculative API redesign or undocumented future endpoints.

  **Recommended Agent Profile**:
  - Category: `deep` - Reason: Requires precise consistency across multiple canonical references.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['unspecified-low']` - Precision demands higher rigor.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [10,11,12] | Blocked By: [1,2,3]

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `backend/docs/reference/api-contract.md`
  - Pattern: `backend/docs/reference/schema.md`
  - Pattern: `backend/docs/reference/errors.md`
  - API/Type: `backend/app/Config/Routes.php`
  - Pattern: `backend/docs/architecture/runtime-status.md`
  - Pattern: `backend/docs/governance/source-of-truth-map.md`

  **Acceptance Criteria** (agent-executable only):
  - [ ] Reference docs include current endpoint/resource names and valid section links.
  - [ ] Route examples and terminology align with runtime-status and source-of-truth map.
  - [ ] `grep -R "auth/password\|item-categories\|item-units\|stock-opnames\|menu-schedules\|reports" backend/docs/reference backend/docs/architecture backend/docs/governance` shows coherent presence.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path reference alignment
    Tool: Bash
    Steps: Update reference docs and cross-links; run keyword/anchor checks against runtime-status and source-of-truth-map.
    Expected: Reference set reflects current runtime endpoints and valid internal links.
    Evidence: .sisyphus/evidence/task-7-reference-alignment.txt

  Scenario: Failure/edge case broken reference links
    Tool: Bash
    Steps: Run a local markdown-link validation script over touched reference files.
    Expected: Zero broken local links in touched reference docs.
    Evidence: .sisyphus/evidence/task-7-reference-alignment-error.txt
  ```

  **Commit**: NO | Message: `docs(reference): align api/schema/errors to runtime` | Files: [`backend/docs/reference/**`, `backend/docs/architecture/**`, `backend/docs/governance/**`]

- [x] 8. Update Governance for Ownership, Review Cadence, and Canonical Map

  **What to do**: Refresh governance docs to define doc ownership/review cadence, update source-of-truth mappings, and enforce canonical precedence between runtime/source docs and legacy/root docs.
  **Must NOT do**: Do not create governance rules that conflict with existing project contribution flow.

  **Recommended Agent Profile**:
  - Category: `unspecified-low` - Reason: Policy writing with moderate cross-doc updates.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['ultrabrain']` - No novel algorithmic/architecture challenge.

  **Parallelization**: Can Parallel: YES | Wave 2 | Blocks: [12] | Blocked By: [1,5]

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `backend/docs/governance/source-of-truth-map.md`
  - Pattern: `backend/docs/governance/doc-templates.md`
  - Pattern: `backend/docs/governance/changelog.md`
  - Pattern: `backend/docs/README.md`

  **Acceptance Criteria** (agent-executable only):
  - [ ] Governance explicitly states owner/reviewer roles and review cadence.
  - [ ] Canonical precedence rules include root legacy docs and backend canonical docs.
  - [ ] `grep -R "owner\|review cadence\|source of truth\|canonical" backend/docs/governance` confirms required governance terms.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path governance completeness
    Tool: Bash
    Steps: Update governance pages and verify required policy terms + mapping entries.
    Expected: Governance set defines ownership, cadence, and canonical precedence.
    Evidence: .sisyphus/evidence/task-8-governance-alignment.txt

  Scenario: Failure/edge case policy contradiction
    Tool: Bash
    Steps: Search for conflicting canonical-precedence statements across governance docs.
    Expected: No contradictory policy statements remain in touched governance pages.
    Evidence: .sisyphus/evidence/task-8-governance-alignment-error.txt
  ```

  **Commit**: NO | Message: `docs(governance): define ownership cadence and precedence` | Files: [`backend/docs/governance/**`, `backend/docs/README.md`]

- [x] 9. Align Frontend SDK README to Actual Exported Surface

  **What to do**: Update `frontend/README.md` so documented SDK methods/resources exactly match `frontend/src/sdk/index.ts` and resource wrappers, including missing methods discovered in cross-check.
  **Must NOT do**: Do not document methods absent from exported SDK surface.

  **Recommended Agent Profile**:
  - Category: `writing` - Reason: Technical API docs correction with method-level accuracy.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['deep']` - Mapping already defined by tasks 1 and 4.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: [12] | Blocked By: [1,4]

  **References** (executor has NO interview context - be exhaustive):
  - API/Type: `frontend/src/sdk/index.ts` - canonical export list.
  - Pattern: `frontend/src/sdk/resources/auth.ts` - includes `changePassword`.
  - Pattern: `frontend/src/sdk/resources/dailyPatients.ts` - list/get/create methods.
  - Pattern: `frontend/src/sdk/resources/spk.ts` - extended SPK methods.
  - Pattern: `frontend/src/sdk/resources/dashboard.ts`
  - Pattern: `frontend/src/sdk/resources/reports.ts`
  - Pattern: `frontend/src/sdk/resources/stockOpnames.ts`
  - Pattern: `backend/docs/reference/api-contract.md` - API parity checks.

  **Acceptance Criteria** (agent-executable only):
  - [ ] Frontend README documents `auth.changePassword`.
  - [ ] Frontend README documents `dailyPatients.list` and `dailyPatients.get` in addition to create.
  - [ ] Frontend README documents extended SPK methods and resources for dashboard/reports/stockOpnames.
  - [ ] `grep -E "changePassword|dailyPatients\.list|dailyPatients\.get|postBasahStock|postKeringPengemasStock|dashboard|reports|stockOpnames" frontend/README.md` returns all required entries.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path SDK README parity
    Tool: Bash
    Steps: Update README methods/resources and run grep for required method names.
    Expected: README method list matches exported SDK surface for targeted resources.
    Evidence: .sisyphus/evidence/task-9-frontend-readme-alignment.txt

  Scenario: Failure/edge case undocumented exported method
    Tool: Bash
    Steps: Compare exported method identifiers to README entries for targeted resources.
    Expected: No targeted exported methods remain undocumented.
    Evidence: .sisyphus/evidence/task-9-frontend-readme-alignment-error.txt
  ```

  **Commit**: NO | Message: `docs(frontend-sdk): align README with exported methods` | Files: [`frontend/README.md`]

- [x] 10. Archive SRS into `/docs/archive` with Canonical Redirects

  **What to do**: Move or recreate `Software Requirements Specification (SRS).md` under `backend/docs/archive/` and add an archive banner that points to canonical docs (`backend/docs/README.md`, `reference/api-contract.md`, `architecture/runtime-status.md`).
  **Must NOT do**: Do not keep SRS in active canonical navigation; do not leave archive copy without redirect links.

  **Recommended Agent Profile**:
  - Category: `writing` - Reason: Controlled archival and canonical redirect documentation.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['deep']` - No full semantic rewrite needed after archival decision.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: [12] | Blocked By: [3,7]

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `Software Requirements Specification (SRS).md`
  - Pattern: `backend/docs/archive/README.md`
  - Pattern: `backend/docs/README.md`
  - Pattern: `backend/docs/reference/api-contract.md`
  - Pattern: `backend/docs/architecture/runtime-status.md`

  **Acceptance Criteria** (agent-executable only):
  - [ ] SRS exists only in `backend/docs/archive/` scope and is marked archived.
  - [ ] Archived SRS contains canonical redirects to backend docs index, API contract, and runtime status.
  - [ ] Canonical docs indexes do not treat SRS as active reference.
  - [ ] `grep -R "ARCHIVED\|Canonical\|api-contract\|runtime-status" backend/docs/archive` returns expected entries.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path SRS archival
    Tool: Bash
    Steps: Move/create archived SRS under docs/archive, add banner + canonical redirects, validate links.
    Expected: SRS is archived and points users to canonical docs.
    Evidence: .sisyphus/evidence/task-10-srs-archive.txt

  Scenario: Failure/edge case stale active references
    Tool: Bash
    Steps: Search for active-nav links or canonical claims still pointing to SRS.
    Expected: No active canonical nav depends on SRS.
    Evidence: .sisyphus/evidence/task-10-srs-archive-error.txt
  ```

  **Commit**: NO | Message: `docs(archive): archive SRS with canonical redirects` | Files: [`backend/docs/archive/**`, `backend/docs/README.md`]

- [x] 11. Archive System Request into `/docs/archive` with Canonical Redirects

  **What to do**: Move or recreate `System Request.md` under `backend/docs/archive/` and add archive banner + canonical redirect links to active docs.
  **Must NOT do**: Do not keep System Request as an active source-of-truth in canonical docs.

  **Recommended Agent Profile**:
  - Category: `writing` - Reason: Structured archival and redirect work.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['deep']` - Full content reconciliation intentionally replaced by archival policy.

  **Parallelization**: Can Parallel: YES | Wave 3 | Blocks: [12] | Blocked By: [7]

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `System Request.md`
  - Pattern: `backend/docs/archive/README.md`
  - Pattern: `backend/docs/README.md`
  - Pattern: `backend/docs/reference/api-contract.md`
  - Pattern: `backend/docs/architecture/runtime-status.md`

  **Acceptance Criteria** (agent-executable only):
  - [ ] System Request exists only in `backend/docs/archive/` scope and is marked archived.
  - [ ] Archived System Request points to canonical backend docs index + API contract + runtime status.
  - [ ] `grep -R "ARCHIVED\|Canonical\|api-contract\|runtime-status" backend/docs/archive` returns expected entries.

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path System Request archival
    Tool: Bash
    Steps: Move/create archived System Request under docs/archive with redirects and validate links.
    Expected: System Request is archived and points to canonical docs.
    Evidence: .sisyphus/evidence/task-11-system-request-archive.txt

  Scenario: Failure/edge case stale canonical dependency
    Tool: Bash
    Steps: Search canonical indexes/reference docs for active dependency on System Request.
    Expected: No canonical doc depends on System Request.
    Evidence: .sisyphus/evidence/task-11-system-request-archive-error.txt
  ```

  **Commit**: NO | Message: `docs(archive): archive System Request with canonical redirects` | Files: [`backend/docs/archive/**`, `backend/docs/README.md`]

- [x] 12. Run Full Documentation Consistency + Link Integrity Sweep

  **What to do**: Execute final full-scope consistency checks over all touched docs (terminology, cross-links, route/sdk spot-check matrix, canonical precedence statements) and produce consolidated evidence.
  **Must NOT do**: Do not conclude completion with unresolved check failures.

  **Recommended Agent Profile**:
  - Category: `quick` - Reason: Deterministic final validation pass.
  - Skills: `[]` - No additional skills required.
  - Omitted: `['writing']` - Main task is verification, not new content creation.

  **Parallelization**: Can Parallel: NO | Wave 3 | Blocks: [] | Blocked By: [1,4,5,6,7,8,9,10,11]

  **References** (executor has NO interview context - be exhaustive):
  - Pattern: `backend/docs/**`
  - Pattern: `frontend/README.md`
  - Pattern: `backend/docs/archive/**`
  - Pattern: `.sisyphus/evidence/**`

  **Acceptance Criteria** (agent-executable only):
  - [ ] All touched markdown links validate successfully.
  - [ ] Spot-check matrix terms are present and consistent across implementation and docs.
  - [ ] No forbidden non-doc files modified.
  - [ ] Consolidated verification artifact created.
  - [ ] Commands executed and captured in evidence:
    - `git diff --name-only`
    - `grep -R "auth/password\|items\|dailyPatients\|stock-opnames\|spk\|dashboard\|reports" backend/docs frontend/README.md`

  **QA Scenarios** (MANDATORY - task incomplete without these):
  ```
  Scenario: Happy path final consistency sweep
    Tool: Bash
    Steps: Run file-change whitelist check, required-term grep checks, local markdown link validation, matrix verification.
    Expected: All checks pass; consolidated evidence file summarizes pass status.
    Evidence: .sisyphus/evidence/task-12-final-consistency.txt

  Scenario: Failure/edge case validation failure handling
    Tool: Bash
    Steps: Intentionally run checks before final fixes (if pending), capture failures, then re-run after fixes.
    Expected: Any initial failures are resolved and final run passes cleanly.
    Evidence: .sisyphus/evidence/task-12-final-consistency-error.txt
  ```

  **Commit**: NO | Message: `docs(qa): run final consistency and link integrity checks` | Files: [`.sisyphus/evidence/**`, `backend/docs/**`, `frontend/README.md`]

## Final Verification Wave (MANDATORY — after ALL implementation tasks)
> 4 review agents run in PARALLEL. ALL must APPROVE. Present consolidated results to user and get explicit "okay" before completing.
> **Do NOT auto-proceed after verification. Wait for user's explicit approval before marking work complete.**
> **Never mark F1-F4 as checked before getting user's okay.** Rejection or user feedback -> fix -> re-run -> present again -> wait for okay.
- [x] F1. Plan Compliance Audit — oracle
- [x] F2. Code Quality Review — unspecified-high
- [x] F3. Real Manual QA — unspecified-high (+ playwright if UI)
- [x] F4. Scope Fidelity Check — deep

## Commit Strategy
- Commit 1: docs(runtime+guides): align actor model, item CRUD, workflow coverage
- Commit 2: docs(reference+governance): fix stale links/anchors and source-of-truth mapping
- Commit 3: docs(frontend+archive): align SDK README and archive SRS/System Request with redirects
- Commit 4: docs(verification): add/update alignment matrix and evidence artifacts

## Success Criteria
- No contradictions remain between runtime routes/roles, SDK exports, and documentation claims.
- By-user and by-feature workflows are both complete and cross-linked.
- Governance documents accurately reflect current canonical source-of-truth and link integrity.
- Frontend SDK documentation fully describes implemented export surface and key methods.
- SRS and System Request are archived and no longer treated as canonical sources.
