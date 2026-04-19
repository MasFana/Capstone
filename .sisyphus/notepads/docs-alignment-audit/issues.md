## 2026-04-19T16:55:00Z Task: bootstrap
No active issues recorded yet.

## 2026-04-19 Task 1: Runtime + SDK Truth Matrix
- Legacy-file phrases (, , , ) still appear across docs, but current occurrences are explicitly in deprecated context and governance migration/changelog records. Not treated as runtime-link errors for this task.
-  tool failed in this environment due to BusyBox option mismatch ( unsupported); verification used  via Bash for reproducible evidence output.

## 2026-04-19 Task 1: Runtime + SDK Truth Matrix (correction)
- Legacy-file phrases ("api-design.md", "data-dictionary.md", "project-flow-alignment.md", "system-design.md") still appear across docs, but only as deprecated context and governance migration/changelog records; not treated as runtime-link errors for this task.
- The default grep helper failed in this environment due to BusyBox option mismatch ("--exclude-dir" unsupported); verification used "rtk grep -R" via Bash for reproducible evidence output.

## 2026-04-19 Task 4: Route↔Doc and SDK↔Doc Spot-Check Matrix
- The required verification command references several SDK method names that do not appear verbatim in backend docs; that is expected because the grep target intentionally spans backend docs plus `frontend/README.md` and should be read as a mapped-reference check, not an endpoint inventory.
- The spot-check matrix is sampled by design; it should not be interpreted as complete endpoint verification beyond the listed rows.
### Encountered Issues
- Some feature domains (like SPK and Menu Planning) are tightly coupled, requiring careful cross-linking to avoid duplication.
- Verifying all keywords across multiple directory levels requires broad grep patterns.
### Documentation Alignment Issues
- Found multiple stale links to removed files like 'use-case-diagram.md' and legacy 'docs/' paths.
Issues encountered during Item CRUD Lifecycle Task:
- Documentation links were slightly outdated regarding the naming of the items master guide.
- Grep tool initially failed due to search complexity, switched to simpler bash commands.

## 2026-04-19 Task 7: Issues
- Markdown LSP diagnostics are unavailable in this environment (no .md server configured), so verification relied on runtime-source reads plus required  evidence output.
- Built-in grep helper still routes to BusyBox options incompatible with --exclude-dir; used required Bash  as stable verifier.

## 2026-04-19 Task 7: Issue Clarification
- Earlier issue note also lost command literals from shell interpolation; intended verifier was rtk grep -R and evidence file generation succeeded.

## 2026-04-19 Task 8: Governance for Ownership, Review Cadence, and Canonical Precedence
- No blocking issues encountered. All governance docs updated successfully with explicit ownership, review cadence, and canonical precedence policies.
- Verification grep confirmed coherent matches across all governance files with zero errors.
- Governance policies are now binding and enforceable across all documentation work.
### Task 9 Issues
- Encountered identical oldString/newString during edit of dailyPatients section as it was already accurate.

## 2026-04-19 Task 12 Issues
- `rtk git diff --name-only` prints a `--- Changes ---` banner that must be ignored when doing file-whitelist checks.
- Legacy root docs moved into `backend/docs/archive/` should not be treated as broken links during active-tree markdown validation.

## 2026-04-19: Post-Wave Correction Issues
- Discovered that several "incorrect" method names reported by reviewers were actually absent from the file, likely indicating they had already been partially fixed or the review was based on a different version. Focused on verifying remaining names and correcting permissions.
- Identified that `backend/docs/guides/by-workflow/README.md` was indeed missing, so redirected workflow links to the main `backend/docs/README.md` anchors.

## 2026-04-19: Resolved Remaining Defects
- Identified and fixed missed summary table entries in `frontend/README.md` where generic placeholder names were used instead of canonical SDK method exports.
- Cleaned up archive documentation by removing the "Planned Archive Candidates" section, which was providing misleading information about pending document moves that had already been completed.
