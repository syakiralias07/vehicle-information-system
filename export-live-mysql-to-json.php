<?php
// ONE-TIME exporter: run under PHP CLI while XAMPP MySQL is available.
// Never copy this script onto the public server.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/config.php';require __DIR__.'/json-store.php';
if(visPlantStorageReady()){fwrite(STDERR,"Plant storage is active. This legacy exporter is disabled to prevent replacing current JSON records.\n");exit(1);}
$pdo=new PDO('mysql:host='.VIS_DB_HOST.';dbname='.VIS_DB_NAME.';charset=utf8mb4',VIS_DB_USER,VIS_DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
foreach(visTableNames() as $table){
  $rows=[];foreach($pdo->query('SELECT id,data FROM `'.$table.'` ORDER BY id',PDO::FETCH_ASSOC) as $item){
    $decoded=json_decode($item['data'],true,128,JSON_THROW_ON_ERROR);
    if(!is_array($decoded))throw new RuntimeException('Bad JSON record in '.$table);
    $rows[]=['id'=>(int)$item['id'],'data'=>$decoded];
  }
  visUpdate('tables/'.$table,[],static function(&$dest)use($rows){$dest=$rows;});echo $table.': '.count($rows)." records\n";
}
$users=[];foreach($pdo->query('SELECT username,password_hash FROM vis_admins',PDO::FETCH_ASSOC) as $r)$users[$r['username']]=$r['password_hash'];
visUpdate('admins',[],static function(&$d)use($users){$d=$users;});echo 'Admins: '.count($users)."\n";
$mappings=[];foreach($pdo->query('SELECT mapping_key,image_path,label,updated_at FROM vis_image_mappings',PDO::FETCH_ASSOC) as $r)$mappings[$r['mapping_key']]=['image_path'=>$r['image_path'],'label'=>$r['label'],'updated_at'=>$r['updated_at']];
visUpdate('image_mappings',[],static function(&$d)use($mappings){$d=$mappings;});echo 'Image mappings: '.count($mappings)."\n";
$meta=[];foreach($pdo->query('SELECT setting_key,setting_value FROM vis_meta',PDO::FETCH_ASSOC) as $r)$meta[$r['setting_key']]=$r['setting_value'];
visUpdate('meta',[],static function(&$d)use($meta){$d=$meta;});
$logs=$pdo->query('SELECT admin_username,action,details,created_at FROM vis_audit_log ORDER BY id',PDO::FETCH_ASSOC)->fetchAll();
visUpdate('audit_log',[],static function(&$d)use($logs){$d=$logs;});
echo "Export complete. Verify the JSON files and test the website before stopping MySQL.\n";
