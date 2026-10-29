<?php
declare(strict_types=1);
require_once __DIR__.'/plants.php';
// Server-side JSON database; the /data folder must never be public.
function visTableNames():array {return ['FRONT_PAGE','SITE_SETTINGS','MODEL_SELECTION','VARIANT_DIFFERENCE','OVERVIEW_DATABASE','GRADE_SELECTION','GRADE_COMPARISON','VARIANT_COLOUR','PAPER_COLOUR_INFO','VARIANT_TAGGING'];}
function visDataFile(string $key):string {
    if(!preg_match('~^(?:tables/[A-Z_]+|admins|image_mappings|meta|audit_log)$~',$key))throw new RuntimeException('Invalid data key');
    if($key==='admins')return visDataRoot().'/admins.json';
    if(visPlantStorageReady())return visDataRoot().'/plants/'.visPlant().'/'.$key.'.json';
    if(visPlant()!=='fif')throw new RuntimeException('Run migrate-plants.php before using plant catalogues');
    return visDataRoot().'/'.$key.'.json';
}
function visRead(string $key,array $default=[]):array {
    $file=visDataFile($key);
    if(!is_file($file))return $default;
    $fp=fopen($file,'rb');if(!$fp)throw new RuntimeException('Data file inaccessible: '.$key);
    try{if(!flock($fp,LOCK_SH))throw new RuntimeException('Cannot lock data');$text=stream_get_contents($fp);$result=json_decode($text,true,128,JSON_THROW_ON_ERROR);if(!is_array($result))throw new RuntimeException('Invalid JSON data');return $result;}
    finally{flock($fp,LOCK_UN);fclose($fp);}
}
function visUpdate(string $key,array $default,callable $modify):mixed {
    $migration=fopen(visDataRoot().'/.migration.lock','c+b');
    if(!$migration || !flock($migration,LOCK_SH|LOCK_NB)){
        if($migration)fclose($migration);
        throw new RuntimeException('Catalogue maintenance in progress. Try again shortly.');
    }
    try{return visUpdateUnlocked($key,$default,$modify);}
    finally{flock($migration,LOCK_UN);fclose($migration);}
}
function visUpdateUnlocked(string $key,array $default,callable $modify):mixed {
    $file=visDataFile($key);$dir=dirname($file);
    if(!is_dir($dir)&&!mkdir($dir,0750,true))throw new RuntimeException('Data directory not writable');
    $fp=fopen($file,'c+b');if(!$fp)throw new RuntimeException('Data file not writable');
    try{
        if(!flock($fp,LOCK_EX))throw new RuntimeException('Unable to lock data');
        rewind($fp);$contents=stream_get_contents($fp);
        $data=($contents!==false&&trim($contents)!=='')?json_decode($contents,true,128,JSON_THROW_ON_ERROR):$default;
        if(!is_array($data))throw new RuntimeException('Invalid JSON');
        $result=$modify($data);
        $json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
        rewind($fp);if(!ftruncate($fp,0)||fwrite($fp,$json)===false||!fflush($fp))throw new RuntimeException('Unable to write JSON');
        return $result;
    }finally{flock($fp,LOCK_UN);fclose($fp);}
}
function visRows(string $table):array {
    if(!in_array($table,visTableNames(),true))throw new RuntimeException('Invalid table');
    return visRead('tables/'.$table,[]);
}
