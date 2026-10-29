<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Strict');
if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off')ini_set('session.cookie_secure','1');
session_start();
define('VIS_LIBRARY_MODE',true);
require __DIR__.'/api.php';
function output($data,int $status=200):never{http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
function fail(string $message,int $status=400):never{output(['error'=>$message],$status);}
function logged():bool{return !empty($_SESSION['vis_admin']);}
function csrf():void{if(!hash_equals($_SESSION['vis_csrf']??'',(string)($_POST['csrf']??'')))fail('Invalid session token',403);}
function adminTables():array{return ['FRONT_PAGE','SITE_SETTINGS','MODEL_SELECTION','VARIANT_DIFFERENCE','OVERVIEW_DATABASE','GRADE_SELECTION','GRADE_COMPARISON','VARIANT_COLOUR','PAPER_COLOUR_INFO','VARIANT_TAGGING'];}
function auditLog(string $action,string $details):void {
    visUpdate('audit_log',[],static function(&$log)use($action,$details){$log[]=['username'=>$_SESSION['vis_admin'],'action'=>$action,'details'=>$details,'created_at'=>gmdate('c')];});
}
function imageRows(string $table):array {return visRows($table);}

// Audit browser-encoded image URLs against actual local files.
// Unlike visAssetPathValid(), this accepts a literal # inside a filename.
function auditImageFileExists(string $url):bool {
    if ($url==='') return false;
    $path=rawurldecode($url);
    if (!str_starts_with($path,'assets/') || str_contains($path,'..') || str_contains($path,"\\") || preg_match('/[?\x00-\x1f]/',$path)) return false;
    if (!preg_match('~\.(png|jpe?g|webp|gif)$~i',$path)) return false;
    $base=realpath(__DIR__.'/assets');
    $real=realpath(__DIR__.'/'.$path);
    return $base!==false && $real!==false && str_starts_with($real,$base.DIRECTORY_SEPARATOR) && is_file($real);
}
if($_SERVER['REQUEST_METHOD']!=='POST')fail('POST required',405);
$action=(string)($_POST['action']??'');
try{
 $plant=$_POST['plant']??'fif';
 if(!is_string($plant))fail('Unknown plant',400);
 try{visSetPlant($plant);}catch(InvalidArgumentException $e){fail('Unknown plant',400);}
 if($action==='login'){
   $username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');
   if(($_SESSION['login_failures']??0)>=8)fail('Too many unsuccessful attempts. Close your browser session and contact the administrator.',429);
   if(strlen($username)>80 || strlen($password)>1024)fail('Invalid credentials',401);
   $users=visRead('admins',[]);$row=isset($users[$username])?['username'=>$username,'password_hash'=>$users[$username]]:null;
   // constant-cost comparison for non-existent accounts
   if(!$row || !password_verify($password,$row['password_hash'])){$_SESSION['login_failures']=($_SESSION['login_failures']??0)+1;sleep(1);fail('Invalid credentials',401);}
   session_regenerate_id(true);$_SESSION['login_failures']=0;$_SESSION['vis_admin']=$row['username'];$_SESSION['vis_csrf']=bin2hex(random_bytes(24));
   output(['ok'=>true,'csrf'=>$_SESSION['vis_csrf'],'username'=>$row['username']]);
 }
 if($action==='status')output(['logged'=>logged(),'csrf'=>logged()?$_SESSION['vis_csrf']:null,'username'=>$_SESSION['vis_admin']??null]);
 if($action==='logout'){csrf();$_SESSION=[];session_destroy();output(['ok'=>true]);}
 if(!logged())fail('Admin login required',401);
 csrf();
 // Keep catalogue actions consistent with migration and serialize asset mutations.
 $maintenance=fopen(visDataRoot().'/.migration.lock','c+b');
 if(!$maintenance||!flock($maintenance,LOCK_SH|LOCK_NB))fail('Catalogue maintenance in progress. Try again shortly.',503);
 if(in_array($action,['upload','map','delete_uploaded_image','save_record','delete_record'],true)){
   $assetLock=fopen(visDataRoot().'/.assets.lock','c+b');
   if(!$assetLock||!flock($assetLock,LOCK_EX))fail('Unable to lock image assignments',503);
 }
 if($action==='publish'){visMarkPublished();auditLog('publish','Manual publish date');output(['ok'=>true,'updated'=>visLastUpdated()]);}
 if($action==='tables'){
   $tables=[];foreach(adminTables() as $table)$tables[$table]=count(visRows($table));
   output(['tables'=>$tables,'updated'=>visLastUpdated()]);
 }
 if($action==='records'){
   $table=(string)($_POST['table']??'');if(!in_array($table,adminTables(),true))fail('Invalid table');
   output(['records'=>imageRows($table)]);
 }
 if($action==='save_record'){
   $table=(string)($_POST['table']??'');if(!in_array($table,adminTables(),true))fail('Invalid table');
   $data=json_decode((string)($_POST['data']??''),true,128,JSON_THROW_ON_ERROR);
   if(!is_array($data)||array_is_list($data)||strlen(json_encode($data))>100000)fail('Expected a JSON record object');
   foreach($data as $key=>$val)if(!is_string($key)||strlen($key)>200||(!is_scalar($val)&&$val!==null))fail('Record fields must be simple values');
   foreach($data as $val)if(is_string($val)&&str_starts_with(rawurldecode($val),'assets/admin-uploads/')&&!visAssetBelongsToPlant(rawurldecode($val)))fail('Uploaded images must belong to the selected plant');
    foreach($data as $field=>$value){
      if(is_string($value) && preg_match('~(?:drive\.google\.com|docs\.google\.com|googleusercontent\.com)~i',$value))
        fail('Google Drive links are no longer supported. Upload the image using Image Management.');
    }
   $id=(int)($_POST['id']??0);
   $id=visUpdate('tables/'.$table,[],static function(&$rows)use($id,$data){
     $target=$id;
     if($target){foreach($rows as &$entry){if((int)$entry['id']===$target){$entry['data']=$data;return $target;}}throw new RuntimeException('Record not found');}
     $max=0;foreach($rows as $entry)$max=max($max,(int)($entry['id']??0));
     $target=$max+1;$rows[]=['id'=>$target,'data'=>$data];return $target;
   });
   visMarkPublished();auditLog('save_record',$table.' #'.$id);output(['ok'=>true,'id'=>$id]);
 }
 if($action==='delete_record'){
   $table=(string)($_POST['table']??'');$id=(int)($_POST['id']??0);
   if(!in_array($table,adminTables(),true)||$id<1)fail('Invalid record');
   visUpdate('tables/'.$table,[],static function(&$rows)use($id){$rows=array_values(array_filter($rows,static fn($r)=>(int)$r['id']!==$id));});
   visMarkPublished();auditLog('delete_record',$table.' #'.$id);output(['ok'=>true]);
 }
 if($action==='assets'){
   $list=[];$root=realpath(__DIR__.'/assets');
   $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
   foreach($it as $file)if($file->isFile()&&preg_match('/\.(png|jpe?g|webp|gif)$/i',$file->getFilename())){
      $rel='assets/'.str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));if(visAssetBelongsToPlant($rel))$list[]=$rel;
   }
   sort($list);output(['assets'=>$list]);
 }
 if($action==='upload'){
   if(empty($_FILES['image']) || $_FILES['image']['error']!==UPLOAD_ERR_OK)fail('Select a valid image to upload');
   $tmp=$_FILES['image']['tmp_name'];
   if($_FILES['image']['size']>12*1024*1024)fail('Maximum image size is 12 MB');
   $info=@getimagesize($tmp);if(!$info || $info[0]>18000||$info[1]>18000)fail('Invalid image dimensions');
   $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
   $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime]??null;
   if(!$ext)fail('Only JPEG, PNG, WEBP and GIF images are allowed');
   $folder=__DIR__.'/'.visUploadPrefix();if(!is_dir($folder)&&!mkdir($folder,0755,true))fail('Upload folder unavailable',500);
   $path=visUploadPrefix().bin2hex(random_bytes(16)).'.'.$ext;
   if(!move_uploaded_file($tmp,__DIR__.'/'.$path))fail('Unable to save image',500);
   auditLog('upload',$path);output(['ok'=>true,'path'=>$path]);
 }
 if($action==='delete_uploaded_image'){
   $path=(string)($_POST['path']??'');
   if(!preg_match('~^assets/admin-uploads/(?:[a-z0-9-]+/)?[a-f0-9]{32}\.(png|jpg|webp|gif)$~',$path) || !visAssetBelongsToPlant($path) || !visAssetPathValid($path))fail('Only images uploaded for the selected plant can be deleted');
   $selectedPlant=visPlant();
   try{foreach(visPlants() as $slug=>$label){
     visSetPlant($slug);
     foreach(visRead('image_mappings',[]) as $m)if(($m['image_path']??'')===$path)fail('This image is assigned to a record. Remove its image assignments first.');
     foreach(visTableNames() as $table)foreach(visRows($table) as $entry)foreach($entry['data'] as $value){
       if(is_string($value)&&rawurldecode($value)===$path)fail('This image is referenced by a record. Remove its references first.');
     }
   }}finally{visSetPlant($selectedPlant);}
   if(!unlink(__DIR__.'/'.$path))fail('Unable to delete image',500);
   auditLog('delete_uploaded_image',$path);output(['ok'=>true]);
 }
 if($action==='map'){
   $table=(string)($_POST['table']??'');$id=(int)($_POST['id']??0);$path=(string)($_POST['path']??'');
   $name=(string)($_POST['name']??'');$kind=(string)($_POST['kind']??'');$grade=(string)($_POST['grade']??'');$part=(string)($_POST['part']??'');
   if(!in_array($table,adminTables(),true)||$id<1||strlen($name)>255||strlen($grade)>100||strlen($part)>100||!in_array($kind,['part','overview','opl','tagging','grade_card',''],true))fail('Invalid mapping');
   $row=null;foreach(visRows($table) as $entry){if((int)$entry['id']===$id){$row=$entry['data'];break;}}
   if(!is_array($row))fail('Record not found');
   if($grade!=='')$row['Grade']=$grade;if($part!=='')$row['Part Number']=$part;
   $key=visMappingKey($row,$name,$kind);
   if($path!=='' && (!visAssetBelongsToPlant($path)||!visAssetPathValid($path)))fail('Image must exist in assets and belong to the selected plant');
   visUpdate('image_mappings',[],static function(&$mappings)use($path,$key,$table,$id,$kind,$name){
     if($path==='')unset($mappings[$key]);
     else $mappings[$key]=['image_path'=>$path,'label'=>$table.' #'.$id.' '.$kind.' '.$name,'updated_at'=>gmdate('c')];
   });
   visMarkPublished();auditLog('image_mapping',$table.' #'.$id.' '.$path);output(['ok'=>true,'key'=>$key]);
 }
 if($action==='audit'){
   $issues=[];$tot=0;$mapped=0;$seen=[];
   foreach(['VARIANT_DIFFERENCE'=>'part','OVERVIEW_DATABASE'=>'overview','VARIANT_TAGGING'=>'tagging','GRADE_SELECTION'=>'grade_card'] as $table=>$defaultKind){
     foreach(imageRows($table) as $entry){
       $r=$entry['data'];if(!is_array($r))continue;
       $name=$table==='OVERVIEW_DATABASE'?(string)($r['Picture Name']??''):($table==='GRADE_SELECTION'?'GRADE CARD':'');
       $kind=$table==='OVERVIEW_DATABASE'&&str_starts_with(strtoupper($name),'OPL')?'opl':$defaultKind;
       if($table==='GRADE_SELECTION')$path=gradeCardImagePath((string)($r['Model']??''),(string)($r['Variant']??''),(string)($r['Grade']??''),$r);
       else $path=imagePath($r,$name,$kind);
       $tot++;
       if(auditImageFileExists($path)){$mapped++;continue;}
       $issues[]=['table'=>$table,'id'=>$entry['id'],'model'=>$r['Model']??'','variant'=>$r['Variant']??'','grade'=>$r['Grade']??'','module'=>$r['Module']??'','name'=>$name?:($r['Part Name']??$r['Display Name']??$r['Tagging Type']??''),'part'=>$r['Part Number']??'','reason'=>$path===''?'Unmapped':'File missing'];
     }
   }
   output(['total'=>$tot,'ok'=>$mapped,'missing'=>count($issues),'issues'=>$issues]);
 }
 fail('Unknown action',404);
}catch(Throwable $e){fail('Request failed: '.$e->getMessage(),500);}
