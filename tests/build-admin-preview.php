<?php
// Builds a local browser-only preview. API requests are mocked; real data is never edited.
$root=dirname(__DIR__);
ob_start();require $root.'/admin.php';$html=ob_get_clean();
$rows=json_decode(file_get_contents($root.'/data/plants/fif/tables/FRONT_PAGE.json'),true);
$tables=[];foreach(glob($root.'/data/plants/fif/tables/*.json') as $file)$tables[basename($file,'.json')]=count(json_decode(file_get_contents($file),true));
$mock='<script>window.fixtureRows='.json_encode($rows).';window.fixtureTables='.json_encode($tables).';</script>';
$mock.= <<<'JS'
<script>
window.adminTestCalls=[];
window.fetch=async(url,options)=>{const action=options.body.get('action'),plant=options.body.get('plant');adminTestCalls.push({action,plant});await new Promise(r=>setTimeout(r,20));let data=action==='status'?{logged:true,csrf:'fixture',username:'fixture'}:action==='tables'?{tables:plant==='fif'?fixtureTables:{FRONT_PAGE:0,MODEL_SELECTION:0,SITE_SETTINGS:2}}:action==='assets'?{assets:['assets/PHN transparent logo.png','assets/PHN building photo.jpg']}:{records:plant==='fif'?fixtureRows:[]};return {ok:true,json:async()=>data}};
</script>
JS;
$test= <<<'JS'
<script>
(async()=>{const lines=[],check=(v,m)=>{if(!v)throw Error(m);lines.push('PASS '+m)},wait=ms=>new Promise(r=>setTimeout(r,ms));try{
for(let i=0;i<100&&!document.querySelector('#recordList .item');i++)await wait(30);
check(document.querySelectorAll('#plant option').length===7,'seven plant choices');
check(new URL(document.getElementById('publicWebsite').href).search==='','Public website opens plant homepage after login');
check(document.querySelectorAll('#recordList .item').length>0,'records visible');
document.querySelector('#recordList .item').click();check(document.getElementById('editTitle').textContent.includes('#'),'record editor works');
document.getElementById('plant').value='pekan';await document.getElementById('plant').onchange();
check(new URL(document.getElementById('publicWebsite').href).search==='','Public website stays on plant homepage after switch');
check(document.querySelectorAll('#recordList .item').length===0,'switch clears previous records');
check(document.getElementById('newJson')!==null,'new record editor available');
document.getElementById('plant').value='fif';await document.getElementById('plant').onchange();
check(new URL(document.getElementById('publicWebsite').href).search==='','Public website stays home for FiF');
check(document.documentElement.scrollWidth<=document.documentElement.clientWidth,'page fits viewport');lines.push('ALL PASS');
}catch(e){lines.push('FAIL '+e.stack)}
const out=document.createElement('pre');out.id='testResult';out.textContent=lines.join('\n');out.style.cssText='margin:20px;font:12px monospace';document.body.append(out);
})();</script>
JS;
$html=preg_replace('~<script src="admin\.js[^\"]*"></script>~',$mock.'$0'.$test,$html);
file_put_contents($root.'/admin-ui-preview.html',$html);
