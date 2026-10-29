<?php
declare(strict_types=1);
// Run locally or on the server using PHP CLI; never through HTTP.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/json-store.php';

function visMigratePlants():string {
    $root=visDataRoot();
    if(!is_dir($root))throw new RuntimeException('Missing source data directory');
    $lock=fopen($root.'/.migration.lock','c+b');
    if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Data is busy; stop admin edits and retry');
    try{
        if(visPlantStorageReady())return 'Already initialized; existing plant data was preserved.';
        if(file_exists($root.'/plants'))throw new RuntimeException('Uninitialized plants directory exists; inspect it before retrying');
        $keys=array_merge(array_map(fn($t)=>'tables/'.$t,visTableNames()),['image_mappings','meta','audit_log']);
        $token=gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
        $backup=$root.'/backups/pre-plants-'.$token;
        $stage=$root.'/.plants-stage-'.$token;
        $write=static function(string $file,array $data):void {
            $dir=dirname($file);if(!is_dir($dir)&&!mkdir($dir,0750,true))throw new RuntimeException('Cannot create '.$dir);
            $json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            if(file_put_contents($file,$json)===false)throw new RuntimeException('Cannot write '.$file);
            if(json_decode(file_get_contents($file),true,128,JSON_THROW_ON_ERROR)!==$data)throw new RuntimeException('Verification failed: '.$file);
        };
        $source=[];
        foreach(array_merge($keys,['admins']) as $key){
            $file=$root.'/'.$key.'.json';
            if(!is_file($file))throw new RuntimeException('Missing source file: '.$key);
            $rows=json_decode(file_get_contents($file),true,128,JSON_THROW_ON_ERROR);
            if(!is_array($rows))throw new RuntimeException('Invalid source: '.$key);
            $source[$key]=$rows;$write($backup.'/'.$key.'.json',$rows);
        }
        foreach(visTableNames() as $table)foreach($source['tables/'.$table] as $row){
            if(!isset($row['id'])||!is_array($row['data']??null))throw new RuntimeException('Invalid record in '.$table);
        }
        foreach(visPlants() as $slug=>$label)foreach($keys as $key){
            $data=($slug==='fif'||$key==='tables/SITE_SETTINGS')?$source[$key]:[];
            $write($stage.'/'.$slug.'/'.$key.'.json',$data);
        }
        if(file_put_contents($stage.'/.ready',json_encode(['version'=>1,'backup'=>basename($backup),'created_at'=>gmdate('c')],JSON_THROW_ON_ERROR))===false)throw new RuntimeException('Cannot mark staging complete');
        if(!rename($stage,$root.'/plants'))throw new RuntimeException('Cannot activate staged catalogues');
        return 'Seven catalogues initialized. FiF data preserved. Snapshot: '.$backup;
    }finally{flock($lock,LOCK_UN);fclose($lock);}
}
if(!defined('VIS_MIGRATION_LIBRARY')){
    try{echo visMigratePlants().PHP_EOL;}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
}
