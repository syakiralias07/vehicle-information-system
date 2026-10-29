VIS JSON-only Migration (local XAMPP -> IT hosting)

1. Back up both your CURRENT MySQL vis_database and all website files/assets.
2. Copy new PHP/JS files into a SEPARATE clone of your website folder (do not overwrite the working site yet). Include assets from your existing site.
3. Before turning off MySQL, configure config.php with your existing local DB settings and run from Command Prompt:
   C:\xampp\php\php.exe C:\xampp\htdocs\vehicle-information-system-json\export-live-mysql-to-json.php
4. Check that all 10 JSON files are in data/tables/ and inspect data/image_mappings.json, data/admins.json and data/meta.json.
5. Check your web server denies access to data/ (HTTP 403). cPanel may use Apache, but add server-specific access controls if not.
6. Test the JSON copy in XAMPP with MySQL stopped: public navigation X50/X70/S70, images, admin login, image upload/mapping, add/edit/delete with TEMPORARY record, automated audit, last updated.
7. Remove export-live-mysql-to-json.php, config.php, database.sql, admin_schema.sql, cleanup-google-drive.php before uploading to your public server.
8. Make data/ writable by the PHP runtime but never web-accessible, and back up JSON data plus assets.

Important: JSON data cannot be generated accurately from old database.sql alone: it would miss your latest admin changes and image mappings. Run the exporter against your LIVE local MySQL first.
Concurrent admin writes are serialised per JSON file using file locks. Do not upload the JSON data/ directory to publicly readable hosting.

Plant update (2026-10-28): MySQL migration is a legacy one-time step only. The current website now uses separate plant JSON catalogues. See README_ADMIN.md for migrate-plants.php and portal deployment instructions. Never rerun the MySQL exporter over current plant data.
