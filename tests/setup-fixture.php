<?php
define('VIS_DATA_ROOT',__DIR__.'/.runtime/data');
define('VIS_MIGRATION_LIBRARY',true);
require __DIR__.'/../migrate-plants.php';
if(file_exists(VIS_DATA_ROOT))throw new RuntimeException('Fixture already exists');
mkdir(VIS_DATA_ROOT.'/tables',0750,true);
foreach(visTableNames() as $t)file_put_contents(VIS_DATA_ROOT.'/tables/'.$t.'.json','[]');
foreach(['meta','image_mappings','audit_log'] as $key)file_put_contents(VIS_DATA_ROOT.'/'.$key.'.json','[]');
file_put_contents(VIS_DATA_ROOT.'/admins.json',json_encode(['fixture'=>password_hash('fixture-password-123',PASSWORD_DEFAULT)]));
echo visMigratePlants().PHP_EOL;
mkdir(__DIR__.'/.runtime/sessions',0750,true);
