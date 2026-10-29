<?php
require __DIR__.'/test-helpers.php';
// Reset only the isolated test server's catalogue files, never application data.
define('VIS_DATA_ROOT',__DIR__.'/.runtime/data');
require __DIR__.'/../json-store.php';
foreach(visPlants() as $slug=>$label){visSetPlant($slug);foreach(visTableNames() as $table)visUpdate('tables/'.$table,[],function(&$rows){$rows=[];});foreach(['meta','image_mappings','audit_log'] as $key)visUpdate($key,[],function(&$rows){$rows=[];});}
$cookie='';$csrf='';
function admin(string $action,array $params=[],?string $plant='fif'):array {
    global $cookie,$csrf;
    $post=['action'=>$action,'csrf'=>$csrf]+$params;
    if($plant!==null)$post['plant']=$plant;
    $headers="Content-Type: application/x-www-form-urlencoded\r\n".($cookie?"Cookie: $cookie\r\n":'');
    $text=file_get_contents('http://127.0.0.1:8098/admin-api.php',false,stream_context_create(['http'=>['method'=>'POST','header'=>$headers,'content'=>http_build_query($post),'ignore_errors'=>true]]));
    foreach($http_response_header as $h)if(preg_match('/^Set-Cookie: ([^;]+)/i',$h,$m))$cookie=$m[1];
    preg_match('/\s(\d{3})\s/',$http_response_header[0],$m);
    return [(int)$m[1],json_decode($text,true,512,JSON_THROW_ON_ERROR)];
}
[$status,$d]=admin('login',['username'=>'fixture','password'=>'fixture-password-123']);
check($status===200&&$d['ok'],'shared admin login');$csrf=$d['csrf'];
[$status,$d]=admin('tables',[],'../fif');check($status===400,'invalid admin plant rejected');
foreach(['fif','pekan'] as $plant){
    [$status,$d]=admin('save_record',['table'=>'MODEL_SELECTION','data'=>json_encode(['Model'=>'TEST-'.$plant,'Active'=>'Yes'])],$plant);
    check($status===200&&$d['id']===1,'independent record IDs: '.$plant);
}
foreach(['fif','pekan'] as $plant){
    [$status,$d]=admin('records',['table'=>'MODEL_SELECTION'],$plant);
    check($status===200&&count($d['records'])===1&&$d['records'][0]['data']['Model']==='TEST-'.$plant,'isolated record: '.$plant);
}
[$status,$d]=admin('records',['table'=>'MODEL_SELECTION'],'rasa');check($status===200&&$d['records']===[],'third plant remains empty');
[$status,$d]=admin('assets',[],'pekan');check($status===200,'plant assets endpoint');
check(count(array_filter($d['assets'],fn($asset)=>str_starts_with($asset,'assets/admin-uploads/')&&!str_starts_with($asset,'assets/admin-uploads/pekan/')))===0,'asset list respects plant ownership');
[$status,$d]=admin('map',['table'=>'MODEL_SELECTION','id'=>1,'name'=>'','kind'=>'part','path'=>'assets/admin-uploads/fif/00000000000000000000000000000000.png'],'pekan');
check($status===400,'cross-plant image assignment rejected');
// Exercise a real HTTP multipart upload and its entire assignment/deletion lifecycle.
$boundary='vis-test-'.bin2hex(random_bytes(8));
$multipart='';
foreach(['action'=>'upload','csrf'=>$csrf,'plant'=>'fif'] as $key=>$value)$multipart.="--$boundary\r\nContent-Disposition: form-data; name=\"$key\"\r\n\r\n$value\r\n";
$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a7ioAAAAASUVORK5CYII=');
$multipart.="--$boundary\r\nContent-Disposition: form-data; name=\"image\"; filename=\"fixture.png\"\r\nContent-Type: image/png\r\n\r\n".$png."\r\n--$boundary--\r\n";
$text=file_get_contents('http://127.0.0.1:8098/admin-api.php',false,stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: multipart/form-data; boundary=$boundary\r\nCookie: $cookie\r\n",'content'=>$multipart,'ignore_errors'=>true]]));
if(!str_starts_with($text,'{'))echo $text.PHP_EOL; $upload=json_decode($text,true,512,JSON_THROW_ON_ERROR);
check(!empty($upload['ok'])&&str_starts_with($upload['path'],'assets/admin-uploads/fif/'),'upload stored under selected plant');
$path=$upload['path'];
[$status]=admin('map',['table'=>'MODEL_SELECTION','id'=>1,'name'=>'','kind'=>'part','path'=>$path],'fif');check($status===200,'map own uploaded image');
[$status]=admin('delete_uploaded_image',['path'=>$path],'fif');check($status===400,'assigned image cannot be deleted');
[$status]=admin('delete_uploaded_image',['path'=>$path],'pekan');check($status===400,'other plant cannot delete upload');
[$status,$otherAssets]=admin('assets',[],'pekan');check($status===200&&!in_array($path,$otherAssets['assets'],true),'other plant does not list upload');
[$status]=admin('map',['table'=>'MODEL_SELECTION','id'=>1,'name'=>'','kind'=>'part','path'=>''],'fif');check($status===200,'unassign own image');
[$status]=admin('delete_uploaded_image',['path'=>$path],'fif');check($status===200&&!file_exists(dirname(__DIR__).'/'.$path),'unassigned own image deleted');
[$status,$d]=admin('publish',[],'pekan');check($status===200&&$d['updated'],'publication works');
visSetPlant('rasa');check(visRead('meta')===[],'publication leaves other plant unchanged');
$saved=$csrf;$csrf='wrong';[$status,$d]=admin('delete_record',['table'=>'MODEL_SELECTION','id'=>1],'pekan');check($status===403,'CSRF still required');$csrf=$saved;
[$status,$d]=admin('delete_record',['table'=>'MODEL_SELECTION','id'=>1],'pekan');check($status===200,'delete selected plant record');
[$status,$d]=admin('records',['table'=>'MODEL_SELECTION'],'fif');check(count($d['records'])===1,'delete preserves other plant');
[$status,$d]=admin('logout');check($status===200,'logout works');
[$status,$d]=admin('records',['table'=>'MODEL_SELECTION'],'fif');check($status===401,'login required');
