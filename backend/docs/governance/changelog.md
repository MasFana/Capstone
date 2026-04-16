# Documentation Governance Changelog

This changelog records significant changes to the documentation structure, migrations, and governance policies.

## Removal: Deprecated Docs (2026-04-16)

### Summary

Removed all deprecated backend documentation files that were kept as migration bridges. Updated repository documentation links and governance docs to reflect the removal.

**Scope**: Documentation cleanup only.

**Date**: 2026-04-16

### Files Removed

- `backend/docs/api-design.md`
- `backend/docs/data-dictionary.md`
- `backend/docs/project-flow-alignment.md`
- `backend/docs/stock-transaction-analysis.md`
- `backend/docs/system-design-plan.md`
- `backend/docs/system-design.md`
- `backend/docs/typescript-sdk-maintenance-guide.md`
- `backend/docs/use-case-diagram.md`
- `backend/docs/fr-traceability-matrix.md`
- `backend/docs/migration-baseline-audit-task-1.md`

### Navigation Updates

- Updated `backend/docs/README.md`: Removed links to deleted legacy files.
- Updated `backend/README.md`: Removed links to deleted legacy files.
- Updated `README.md` (root): Removed links to deleted legacy files.
- Updated `frontend/README.md`: Removed links to deleted legacy files.
- Updated `backend/AGENTS.md`: Removed links and references to deleted legacy files.
- Updated `backend/docs/governance/migration-map.md`: Updated statuses to **REMOVED**.

---

## Release: Super-Optimized Documentation Architecture (2026-04-16)

### Summary

Completed comprehensive documentation reorganization to establish a maintainable, role/workflow-oriented structure with explicit governance policies. This release implements the "Super-Optimized Documentation Architecture" plan with incremental migration, deprecation bridges, and archive transition protocols.

**Scope**: Documentation structure and content only. No backend code, dependencies, or CI changes.

**Date**: 2026-04-16

### Affected Paths

#### New Canonical Directories Created
- `backend/docs/guides/by-user/` - Role-specific quickstart guides
- `backend/docs/guides/by-workflow/` - High-friction workflow procedures
- `backend/docs/reference/` - Technical specifications and API contracts
- `backend/docs/architecture/` - System design and runtime status
- `backend/docs/governance/` - Governance policies and migration maps
- `backend/docs/archive/` - Historical and superseded documentation

#### New Content Created
- `backend/docs/guides/by-user/admin-quickstart.md` - Admin role capabilities and workflows
- `backend/docs/guides/by-user/dapur-quickstart.md` - Dapur (kitchen) role operations
- `backend/docs/guides/by-user/gudang-quickstart.md` - Gudang (warehouse) role operations
- `backend/docs/guides/by-workflow/stock-opname-workflow.md` - Stock opname lifecycle (DRAFT → POSTED)
- `backend/docs/guides/by-workflow/spk-basah-workflow.md` - SPK basah generation and posting
- `backend/docs/guides/by-workflow/stock-correction-workflow.md` - Stock correction and revision flows
- `backend/docs/reference/api-contract.md` - Canonical API contract (moved from `api-design.md`)
- `backend/docs/reference/schema.md` - Canonical database schema (moved from `data-dictionary.md`)
- `backend/docs/reference/errors.md` - Unified error reference with HTTP status and business validation mapping
- `backend/docs/architecture/runtime-status.md` - Canonical runtime status (moved from `project-flow-alignment.md`)
- `backend/docs/governance/source-of-truth-map.md` - Mapping of docs to implementation sources (routes, controllers, services, models)
- `backend/docs/governance/doc-templates.md` - Documentation content templates and style contract
- `backend/docs/governance/migration-map.md` - Master migration map with ownership index and deprecation schedule
- `backend/docs/governance/changelog.md` - This file

#### Files Previously Deprecated (Archived Changelog)
- `backend/docs/api-design.md` - (Removed 2026-04-16)
- `backend/docs/data-dictionary.md` - (Removed 2026-04-16)
- `backend/docs/project-flow-alignment.md` - (Removed 2026-04-16)
- `backend/docs/stock-transaction-analysis.md` - (Removed 2026-04-16)
- `backend/docs/typescript-sdk-maintenance-guide.md` - (Removed 2026-04-16)
- `backend/docs/system-design.md` - (Removed 2026-04-16)
- `backend/docs/system-design-plan.md` - (Removed 2026-04-16)
- `backend/docs/use-case-diagram.md` - (Removed 2026-04-16)

#### Navigation Updates
- Updated `backend/docs/README.md` - New documentation index with canonical structure
- Updated `backend/README.md` - Links to canonical docs paths
- Updated `README.md` (project root) - Links to canonical docs paths
- Updated `backend/AGENTS.md` - Agent navigation with new docs structure and minimal read sets

### Compatibility Notes

**Breaking Changes**: None. All deprecated files remain in place with explicit replacement links.

**Migration Path**: 
1. Developers should update bookmarks to point to new canonical paths in `backend/docs/reference/`, `backend/docs/architecture/`, and `backend/docs/guides/`.
2. Deprecated files will remain accessible until v1.2.0 with deprecation banners and replacement links.
3. Hard removal of deprecated files is scheduled for v1.2.0 release.

**Verification**: 
- All canonical docs are grounded in implementation sources (routes, controllers, services, models).
- No broken relative markdown links in `backend/docs/`, `backend/README.md`, `README.md`, or `frontend/README.md`.
- Archive directory contains historical docs with retention policy defined in `backend/docs/archive/README.md`.

### Governance Policies Established

1. **Source-of-Truth Mapping**: All documentation must map to concrete implementation sources (see `backend/docs/governance/source-of-truth-map.md`).
2. **Deprecation-Then-Remove**: Deprecated docs include explicit replacement links and removal target release (see `backend/docs/governance/migration-map.md`).
3. **Archive Transition Protocol**: Archive infrastructure established with retention policy and removal conditions for future document archival (see `backend/docs/archive/README.md`).
4. **Documentation Templates**: All new docs follow templates defined in `backend/docs/governance/doc-templates.md`.

### Related Documentation

- **Migration Map**: `backend/docs/governance/migration-map.md` - Full mapping of old → new paths, ownership, and removal schedule
- **Source-of-Truth Map**: `backend/docs/governance/source-of-truth-map.md` - Implementation source references for all docs
- **Archive Policy**: `backend/docs/archive/README.md` - Archive criteria, retention period, and removal conditions
- **Documentation Index**: `backend/docs/README.md` - Entry point for all backend documentation

---

## Previous Releases

(None - this is the initial governance changelog entry)
