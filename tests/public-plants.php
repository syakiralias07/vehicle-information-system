<?php
require __DIR__.'/test-helpers.php';
$base='http://localhost/vehicle-information-system/';
function request(string $url,?array $body=null):array {
    $options=['http'=>['ignore_errors'=>true,'timeout'=>20]];
    if($body!==null)$options['http']+=['method'=>'POST','header'=>"Content-Type: application/json\r\n",'content'=>json_encode($body)];
    $text=file_get_contents($url,false,stream_context_create($options));
    preg_match('/\s(\d{3})\s/',$http_response_header[0],$m);
    return [(int)$m[1],$text];
}
[$status,$html]=request($base);
check($status===200&&str_contains($html,'Select Plant'),'root is plant chooser');
foreach(['shah-alam-1','shah-alam-2','fif','tanjung-malim','rasa','pegoh','pekan'] as $slug){
    check(str_contains($html,'plant='.$slug),'plant card '.$slug);
    [$status,$text]=request($base.'api.php',['action'=>'getModelCards','args'=>[],'plant'=>$slug]);
    $data=json_decode($text,true,512,JSON_THROW_ON_ERROR);
    check($status===200&&count($data['models'])===($slug==='fif'?3:0),'models '.$slug);
}
[$status,$text]=request($base.'api.php',['action'=>'getModelCards','args'=>[],'plant'=>'../fif']);
check($status===400,'invalid plant rejected by API');
[$status,$html]=request($base.'?plant=invalid');check($status===400,'invalid page plant rejected');
foreach(['VariantSelect.php','GradeSelect.php','ModuleSelect.php','Results.php','admin.php'] as $file){
    [$status]=request($base.$file.'?plant=invalid');check($status===400,'invalid direct page plant: '.$file);
}
[$status,$text]=request($base.'api.php',['action'=>'getParts','args'=>['X50','FL','STANDARD','ER']]);
$data=json_decode($text,true,512,JSON_THROW_ON_ERROR);
check($status===200&&$data['assemblies'][0]['sortOrder']===0&&$data['assemblies'][0]['assemblyName']==='FR STRUCTURE OVERVIEW','legacy FiF API and zero sort');
[$status,$html]=request($base.'?page=variants&model=S70');check($status===200&&str_contains($html,'"fif"'),'legacy deep link context');
[$status]=request($base.'data/plants/fif/tables/MODEL_SELECTION.json');check($status===403,'nested JSON protected');
