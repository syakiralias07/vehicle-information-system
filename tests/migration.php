<?php
declare(strict_types=1);
define('VIS_DATA_ROOT',sys_get_temp_dir().'/vis-plants-test-'.bin2hex(random_bytes(6)));
define('VIS_MIGRATION_LIBRARY',true);
require __DIR__.'/../migrate-plants.php';
require __DIR__.'/test-helpers.php';
mkdir(VIS_DATA_ROOT.'/tables',0750,true);
$original=[];
foreach(visTableNames() as $table){
    $original['tables/'.$table]=[['id'=>42,'data'=>['Model'=>'S70','Variant'=>'A5 MY','Sort Order'=>'0']]];
}
foreach(['admins'=>['test'=>'hash'],'image_mappings'=>['test'=>['image_path'=>'assets/test.png']],'meta'=>['last_published_at'=>'2026-10-28 00:00:00'],'audit_log'=>[['action'=>'test']]] as $key=>$value)$original[$key]=$value;
foreach($original as $key=>$value)file_put_contents(VIS_DATA_ROOT.'/'.$key.'.json',json_encode($value));
file_put_contents(VIS_DATA_ROOT.'/tables/FRONT_PAGE.json','broken JSON');
try{visMigratePlants();throw new RuntimeException('Invalid source accepted');}
catch(JsonException $e){check(!visPlantStorageReady(),'failed migration does not activate partial data');}
file_put_contents(VIS_DATA_ROOT.'/tables/FRONT_PAGE.json',json_encode($original['tables/FRONT_PAGE']));
echo visMigratePlants().PHP_EOL;
foreach($original as $key=>$value)check(visRead($key)===$value,'FiF preserved: '.$key);
foreach(visPlants() as $slug=>$label){visSetPlant($slug);check(count(visRows('MODEL_SELECTION'))===($slug==='fif'?1:0),'isolated models: '.$slug);check(visRows('SITE_SETTINGS')===$original['tables/SITE_SETTINGS'],'branding: '.$slug);}
visSetPlant('pekan');visUpdate('meta',[],function(&$m){$m=['test'=>'pekan'];});
visSetPlant('fif');check(visRead('meta')===$original['meta'],'publication isolated');
$lock=fopen(VIS_DATA_ROOT.'/.migration.lock','c+b');flock($lock,LOCK_EX);
try{visUpdate('meta',[],function(&$m){$m=[];});throw new RuntimeException('Maintenance write accepted');}catch(RuntimeException $e){check(str_contains($e->getMessage(),'maintenance'),'maintenance blocks writes');}
flock($lock,LOCK_UN);fclose($lock);
echo visMigratePlants().PHP_EOL;
visSetPlant('pekan');check(visRead('meta')===['test'=>'pekan'],'rerun preserves edited plant data');
echo "All migration checks passed.\n";
