# VIS administration — plant catalogues

The website uses PHP and JSON. MySQL is not required. One existing admin account manages all seven plants.

## Public use
Open `http://localhost/vehicle-information-system/` and select Shah Alam 1, Shah Alam 2, FiF, Tanjung Malim, Rasa, Pegoh or Pekan. Then select Model → Variant → Grade/Market → Module → Parts. The selected plant appears in the header. Change Plant returns to the chooser. Existing vehicle links without a plant continue to open FiF.

FiF contains the existing catalogue, including saved S70 A5 MY entries. Other plants start with no vehicle records. Their logo and background settings are copied from the existing branding.

## Admin use
1. Open `admin.php` and sign in with the existing account.
2. Choose **Plant** above the database table selector. The heading identifies the plant being edited.
3. Add/edit records and assign images as before. Record IDs, image assignments, audits and published dates belong to that plant.
4. Switching plants clears the selected record, filter, image selection and audit results. Controls are temporarily locked during requests.
5. Create a model in MODEL_SELECTION, its grade/module routes in FRONT_PAGE, its display information in VARIANT_COLOUR, and the required overview/difference/tagging records. Use Image Management to assign pictures explicitly for new plants. Only FiF retains legacy automatic filename matching.
6. Sort Order controls public image order. Zero is a valid first position. Overview/tagging/OPL sections keep their existing grouping.

To create or reset a shared account, use a private terminal:
`C:\xampp\php\php.exe C:\xampp\htdocs\vehicle-information-system\admin-create-user.php admin "YOUR-STRONG-PASSWORD-HERE"`
Use at least 12 characters. Passwords are hashed in the shared data/admins.json. The command may remain in terminal history.

## Data layout
- data/admins.json: shared usernames/password hashes.
- data/plants/<slug>/tables/*.json: that plant's ten vehicle/settings tables.
- data/plants/<slug>/image_mappings.json: that plant's image assignments.
- data/plants/<slug>/meta.json: published date.
- data/plants/<slug>/audit_log.json: admin activity.
- data/plants/.ready: initialization marker; keep it with the plant directories.
- assets/admin-uploads/<slug>/: new uploads for that plant.
- Existing assets/admin-uploads/<file>: legacy FiF uploads, kept at their original paths.
- Original image assets remain shared for manual assignment. New plant pictures must be assigned; production pictures are not guessed from FiF filenames.

Slugs: shah-alam-1, shah-alam-2, fif, tanjung-malim, rasa, pegoh, pekan.
The old data/tables and top-level mapping/meta/audit files are retained for recovery, but are no longer live after migration. Edit through admin. Do not rerun the legacy MySQL exporter; it is disabled after plant migration.

## Deploy this update to the existing portal
This feature changes multiple files; copying only api.php is insufficient.

Updated files: index.php, VariantSelect.php, GradeSelect.php, ModuleSelect.php, Results.php, theme.php, api.php, api-client.js, vis-last-updated.js, json-store.php, admin.php, admin.js, admin.css, admin-api.php, .htaccess, README_ADMIN.md.
New runtime files: plants.php, plant-page.php, PlantSelect.php, plant-context.js.
New CLI migration: migrate-plants.php. The legacy export-live-mysql-to-json.php now refuses to overwrite active plant storage; it is not needed on the public server.

1. Schedule a maintenance window and stop admin edits. Back up the portal's current data directory and assets together.
2. Copy the updated application files and new runtime files. Keep the portal's own current data/admins.json, production JSON files and assets. Do not replace live data with an older local copy. Keep Apache data/.htaccess protection, or configure equivalent denial for another server.
3. Copy migrate-plants.php temporarily, then run `php /path/to/vis/migrate-plants.php` in the portal's command line. It reads that server's current JSON, snapshots it under data/backups, stages and validates all seven catalogues, and activates them with one directory rename. Admin writes are blocked while copying. Rerunning preserves initialized catalogues. A failed run does not activate partial data; inspect its error and retry after correcting the cause.
4. Verify FiF records/images, all seven plants, existing login, isolated record edits, image assignments and publication dates. Other plants should contain no production data yet.
5. Confirm HTTP 403 for data/plants/fif/tables/MODEL_SELECTION.json and data/admins.json. Every nested data path, including backups and migration staging, must stay private.
6. Remove migrate-plants.php from the public deployment copy after successful migration. Keep a private copy for recovery. Exclude tests, docs, original_apps_script, all SQL files, config.php, cleanup-google-drive.php and the MySQL exporter from the public deployment copy.
7. Resume normal use after verification. Back up data/plants, data/admins.json and assets together.

For a new server with no existing VIS data, deploy the already initialized data directory from the authoritative site, including .ready and shared admins, plus assets. Do not run the migration again or mix old/new copies.

## Server requirements
PHP 8.1+ with JSON, sessions and fileinfo; HTTPS; PHP write access to data, plant table directories and assets/admin-uploads. Retain .htaccess files and disable directory listings. Require upload_max_filesize >= 12M and post_max_size > 12M. Uploaded images must be valid JPEG/PNG/WebP/GIF. Review rendered images manually: the audit checks file existence, not production correctness.

## Local verification
Run C:\xampp\php\php.exe tests/plants.php, tests/migration.php and tests/public-plants.php individually. Authenticated tests/admin-plants.php uses an isolated fixture server on 127.0.0.1:8098 with tests/fixture-router.php; it never edits production catalogue JSON. Do not deploy the test fixture. Browser smoke source files are retained under tests after verification.

Browser updates: public pages and admin load JavaScript with a file-version query so plant navigation changes refresh cached scripts automatically. When deploying script updates, include their matching PHP page templates and theme.php.

Admin design update: deploy admin.php, admin.js and the new admin.css together. The Public website link always opens index.php (the seven-plant homepage), regardless of the plant selected in admin. The redesigned workspace includes keyboard-accessible record cards, record filtering/counts and responsive phone layouts.

Model selection design update: deploy index.php, theme.php and the new model-selection.css together. The catalogue now uses a compact banner, responsive vehicle cards and a live model count. Admin Login is available on the plant chooser only; it has been removed from the shared catalogue header.

Remaining catalogue design update: deploy VariantSelect.php, GradeSelect.php, ModuleSelect.php, Results.php, updated model-selection.css and new catalogue-details.css together. All four steps share the model page's header/progress styling, compact section banners and responsive selection cards. Image zoom, image ordering and production data remain managed by their existing functions. Browser checks covered X50 variants, Vietnam/Mexico markets, S70 grades, underbody modules and assembly zoom, including 390px layouts.

Header navigation update: Home and the header brand open the seven-plant menu. The redundant Change Plant header button is removed. The Model progress step still opens the selected plant's catalogue, and Back buttons retain their previous-step behavior. Deploy updated theme.php for this navigation change.
