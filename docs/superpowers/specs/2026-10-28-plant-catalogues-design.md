# Plant catalogues design

Date: 2026-10-28

## Intent and agreed requirements
Add plant selection before the existing vehicle catalogue. Seven plants: Shah Alam 1, Shah Alam 2, FiF, Tanjung Malim, Rasa, Pegoh and Pekan. Each plant has different production records. One existing admin account manages all plants. Preserve the PHP/JSON deployment model; no MySQL runtime dependency.

## Approaches
Separate JSON directories per plant was selected by the user. It isolates records, image assignments and publication dates and permits independent backups. A shared set of tables with a Plant field was considered but requires filtering every record and adds cross-plant editing risk.

## Public experience
The root homepage presents seven plant cards using the existing visual theme. Choosing a plant opens its model catalogue, followed by the existing variant, grade/market, module and parts flow. Show the selected plant and a Change Plant link. Carry a validated plant slug in page links and API requests; changing plants clears vehicle selection. Do not depend on one session-wide plant selection, so separate browser tabs can show different plants. Empty plants show a clear empty catalogue message.

Implementation choice: root with no plant opens the chooser. Existing deep vehicle links without a plant continue to resolve to FiF. Invalid plant identifiers are rejected rather than silently opening another catalogue. Existing API calls without a plant default to FiF for compatibility.

## Data and authentication
Use data/plants/<slug>/tables/*.json plus image_mappings.json, meta.json and audit_log.json for each plant. Slugs: shah-alam-1, shah-alam-2, fif, tanjung-malim, rasa, pegoh, pekan. Keep data/admins.json global so the existing CLI account tool and password hashes remain compatible. The plant registry is server-controlled; never construct paths from arbitrary user input.

Copy all current tables, mappings, metadata and audit history into FiF with a one-time CLI migration. Snapshot the existing data before migration; refuse to overwrite initialized plant data on reruns. Initialize the six other catalogues with empty tables, mappings and audit logs. Preserve existing files as a recovery source, but route migrated runtime reads and writes to the plant directories. Do not rerun the legacy MySQL exporter against this new layout.

Implementation choice: keep current asset paths and originals to preserve FiF images. New admin uploads use a plant-specific subdirectory; expose shared original images and the selected plant's uploads in the asset picker. Prevent deleting another plant's upload or deleting any file still referenced by another plant. Image mapping lookup, record audit and publish actions operate on the selected catalogue.

SITE_SETTINGS is plant-specific. During initialization copy only existing branding settings into empty plants so the website retains its current logo/background; no vehicle production records are copied to them.

## Admin experience
Retain one login. Show a clearly labelled plant selector (default FiF) above the tables. Changing plants clears the selected record, image assignment and filter, then reloads tables, assets and publication information. Every catalogue request carries the selected plant. Saves and uploads capture their target plant at dispatch; prevent switching plants during an in-flight mutation so a response cannot be mistaken for another catalogue. Authentication remains global. Existing CSRF and authorization checks remain enforced.

## Boundaries
No new production records, inferred parts, invented images or per-plant account permissions. Existing FiF data, including the user's new S70 A5 MY records, must survive migration. Preserve the recent zero Sort Order fix and current overview/tagging/OPL grouping. No deployment to the live portal in this task.

## Deployment and verification
Update the handover guide with all changed application files and the migration process. Preserve the portal's latest data before migrating there; do not replace its data with a stale local copy. Require PHP 8.1+, writable plant directories and upload directories, and HTTP denial for every nested data file.

Verify migration record counts and content against the source, rerun safety, all seven plants, invalid slug/path rejection, independent records/mappings/publication dates, existing admin authentication, CSRF enforcement, upload isolation, old FiF deep links, navigation retaining plant context, empty catalogue behavior, and HTTP 403 for nested JSON files. Verify real FiF model-to-parts API navigation and the zero-sort regression. Use temporary fixtures for write tests, without modifying real production records.

## Migration consistency and legacy uploads
Run migration during a maintenance window with catalogue writes disabled. Stage and validate every plant file before creating an initialization marker and switching runtime storage to plant directories; incomplete migrations must not become active. A rerun detects incomplete staging and refuses to replace existing initialized plant data. Legacy assets/admin-uploads/<file> belongs to FiF only; exclude these legacy uploads from other plants' asset lists while retaining their exact FiF mapping paths. Shared originals exclude every admin-upload directory. Back up existing data and uploads before starting.

User update: the admin Public website link must always return to the seven-plant chooser, not the currently edited plant. Admin presentation now uses a PHN workspace layout, responsive record browser/editor, and counts derived from API responses. This supersedes the previous preview-link behavior.
