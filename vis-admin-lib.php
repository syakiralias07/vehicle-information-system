<?php
require_once __DIR__.'/json-store.php';
// Shared VIS administrative helpers. Never trusts client-supplied asset paths.
function visMappingKey(array $r,string $name,string $kind):string {
    $identity=[];
    foreach (['Model','Variant','Grade','Module','Assembly','Picture Name','Part Name','Part Number','Tagging Type','Setting','Colour Name','Display Name'] as $field)
        $identity[$field]=(string)($r[$field]??'');
    $identity['requested_name']=$name;
    $identity['image_kind']=$kind;
    return hash('sha256',json_encode($identity,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
}
function visAssetPathValid(string $path,bool $mustExist=true):bool {
    if(!str_starts_with($path,'assets/') || str_contains($path,'..') || str_contains($path,"\\") || preg_match('/[?#\x00-\x1f]/',$path))return false;
    if(!preg_match('~\.(png|jpg|jpeg|webp|gif)$~i',$path))return false;
    if(!$mustExist)return true;
    $base=realpath(__DIR__.'/assets');$real=realpath(__DIR__.'/'.$path);
    return $base!==false && $real!==false && str_starts_with($real,$base.DIRECTORY_SEPARATOR) && is_file($real);
}
function visMappedImage(array $row,string $name,string $kind):?string {
    $key=visMappingKey($row,$name,$kind);
    $mapping=visRead('image_mappings',[]);
    $path=$mapping[$key]['image_path']??null;
    return is_string($path)&&visAssetPathValid($path)?visEncodeAssetUrl($path):null;
}
function visEncodeAssetUrl(string $path):string {
    return implode('/',array_map('rawurlencode',explode('/',$path)));
}
function visLastUpdated():?string {
    $meta=visRead('meta',[]);
    return $meta['last_published_at']??null;
}
function visMarkPublished():void {
    visUpdate('meta',[],static function(&$data){$data['last_published_at']=gmdate('Y-m-d H:i:s');});
}
