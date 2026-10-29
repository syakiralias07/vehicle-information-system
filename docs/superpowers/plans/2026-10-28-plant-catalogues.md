# Plant Catalogues Implementation Plan

Execute inline using superpowers:executing-plans.

Goal: Seven independent JSON catalogues, one shared admin login, public plant chooser.
Spec: docs/superpowers/specs/2026-10-28-plant-catalogues-design.md
Architecture: validated request plant controls JSON paths; global credentials; staged CLI migration.
Tech: PHP 8.1+, JSON, JavaScript, Apache.
Constraints: preserve FiF and original assets; no MySQL or live deployment; invalid plants rejected; legacy deep links default FiF.
Review focus: interrupted migration, cross-tab context, cross-plant writes/assets, stale admin responses, invalid slugs.

## Task 1: Storage and migration
- [x] Write/run failing tests/plants.php (registry, context rejection, isolated files, shared admins).
- [x] Create plants.php registry/context and asset ownership helpers; extend json-store.php path resolution.
- [x] Create migrate-plants.php: CLI snapshot/stage/validate/activate; reject overwrites, lock writes.
- [x] Run fixture checks, migration and equality comparison with original files. Expected: exact FiF match and six empty catalogues.

## Task 2: Public pages
- [x] Add tests/public-plants.php: chooser, invalid slug, legacy FiF API, empty plants, sorting.
- [x] Create PlantSelect.php and plant-context.js; update index.php/theme.php and every generated navigation URL/API request.
- [x] Verify plant context in navigation without session-global state. Expected: seven cards, independent catalogues and correct zero sorting.

## Task 3: Admin
- [x] Add admin plant dropdown; reset editor/filter/mapping/audit on switch; lock workspace during requests.
- [x] Scope admin API storage, uploads, asset list/mapping/deletion to plant; retain global login/CSRF.
- [x] Test authenticated fixture CRUD, publication, mappings, upload ownership and CSRF without editing real records.

## Task 4: Handover
- [x] Lint PHP, run fixture/HTTP/browser checks; update README_ADMIN.md and migration guidance.
- [x] Fresh code review, fix important findings, rerun checks.

## Ledger
Ruling: Plain XAMPP workspace, no Git available; use in-place edits and original data recovery copies instead of Git-specific helpers. User already backed up files. No commits or worktree.

Task 1 complete: fixture migration, maintenance lock and rerun preservation tests passed. Local migration activated; original files and a pre-plants snapshot retained.
Task 2 complete: HTTP and browser checks passed for seven cards, independent tab context, FiF models, empty Pekan, Change Plant and zero-sort display.
Task 3 complete: isolated authenticated CRUD/CSRF/publication and multipart upload/assignment/deletion tests passed; mocked browser admin switch checks passed.
Ruling: Other plants use explicit image assignments instead of legacy filename guessing to avoid showing a FiF production image for a similarly named model. Cost: admins must assign their new plant images manually.
Ruling: No Git-specific task ledger helpers can run in this plain folder; checks and evidence are recorded here. Existing files are edited in place as authorized.

Task 4 complete: all PHP lint and fixture/HTTP checks passed; browser desktop/mobile/public/admin-switch checks passed. Fresh reviewer found no important/critical issues. No deferred minors. Temporary public browser harness pages moved under denied tests directory.
Final review: independent reviewer, no important findings. Mobile Change Plant overflow reproduced in browser and fixed; browser check passed afterward.
