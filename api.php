<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/vis-admin-lib.php';
function dataset(string $table):array {
    $allowed=visTableNames();
    if(!in_array($table,$allowed,true))throw new Exception('Unknown table');
    return array_map(static fn($entry)=>$entry['data'],visRows($table));
}
function eq($a,$b):bool{return strtoupper(trim((string)$a))===strtoupper(trim((string)$b));}
function active(array $r):bool{return !isset($r['Active'])||$r['Active']===''||in_array(strtoupper(trim((string)$r['Active'])),['YES','Y','TRUE','1','ACTIVE'],true);}
function matching(string $table,array $filters=[],bool $mustActive=true):array {return array_values(array_filter(dataset($table),function($r)use($filters,$mustActive){if($mustActive&&!active($r))return false;foreach($filters as $k=>$v){if(!eq($r[$k]??'',$v))return false;}return true;}));}
function order(array &$items):void{usort($items,fn($a,$b)=>(float)($a['sortOrder']??999)-(float)($b['sortOrder']??999));}
function vals(array $r,string $key,string $default=''):string{return trim((string)($r[$key]??$default));}
function imagePath(array $r,string $name='',string $kind=''):string {
  $override=visMappedImage($r,$name,$kind);
  if($override!==null)return $override;
  if(visPlant()!=='fif')return ''; // Other plants require their own explicit image assignments.
  // Uses the actual current asset paths, not a stale generated manifest.
  static $files=null;
  if ($files===null) {
    $files=[];
    $walk=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/assets',FilesystemIterator::SKIP_DOTS));
    foreach($walk as $file) {
      if (!$file->isFile() || !preg_match('/\.(png|jpe?g|webp|gif)$/i',$file->getFilename()))continue;
      $path=str_replace('\\','/',substr($file->getPathname(),strlen(__DIR__)+1));
      if(!visAssetBelongsToPlant($path))continue;
      $basename=pathinfo($file->getFilename(),PATHINFO_FILENAME);
      $normalize=static fn($v):string=>strtoupper(preg_replace('/[^A-Z0-9]/i','',(string)$v));
      $files[]=['path'=>$path,'base'=>$normalize($basename),'dir'=>$normalize(dirname($path)),'filename'=>$basename];
    }
  }
  // Explicit mappings for images whose database labels do not match local filenames.
  $modelRaw=strtoupper(trim((string)($r['Model']??'')));
  $variantRaw=strtoupper(preg_replace('/[^A-Z0-9]/i','',(string)($r['Variant']??'')));
  $tagRaw=strtoupper(trim((string)($r['Tagging Type']??'')));
  $partNameRaw=strtoupper(trim((string)($r['Part Name']??'')));
  $pictureRaw=strtoupper(trim($name ?: (string)($r['Picture Name']??'')));
  $special='';
  // X70 MC3 tagging has fixed local files; spreadsheet uses category labels,
  // not the image filenames. Reuse the same examples for ER, FF and RF.
  if($modelRaw==='X70' && $variantRaw==='MC3' && $kind==='tagging') {
    if(str_contains($tagRaw,'PRODUCTION LINE'))
      $special='assets/X70/X70_TAGGING.png';
    elseif(str_contains($tagRaw,'SUPPLY CHAIN'))
      $special='assets/X70/X70_LOOSE_PARTS.jpg';
  }
  // X50 FL variant comparison: the baseline record has no part number,
  // so its STANDARD picture cannot be found through part-number matching.
  if($modelRaw==='X50' && $variantRaw==='FL' && $kind==='part' &&
     trim((string)($r['Part Number']??''))==='') {
    $special='assets/X50/FL/X50_FL_STANDARD.png';
  }
  // X50 A6 GMY variant-difference images have descriptive filenames rather
  // than part-number filenames. Match by the database's exact part number.
  if($modelRaw==='X50' && $variantRaw==='A6GMY' && $kind==='part') {
    $a6PartFiles=[
      '6608584279'=>'assets/X50/A6_GMY/X50_A6_GMY_PLATE_ASSY_DASH_PANEL.png',
      '8892505372'=>'assets/X50/A6_GMY/X50_A6_GMY_WHEEL_ARCH_COVER_ASSY_LH_FRONT.png',
      '66000045018'=>'assets/X50/A6_GMY/X50_A6_GMY_PLATE_ASSY_RH_FRONT_SIDE_MEMBER_OUTER.png',
      '6600008301'=>'assets/X50/A6_GMY/X50_A6_GMY_SIDE_MEMBER_BODY_ASSY_REAR_FLOOR_RH.png',
      '6608654575'=>'assets/X50/A6_GMY/X50_A6_GMY_MID_TUNNEL_ASSY_CVT.png',
    ];
    $partNumber=trim((string)($r['Part Number']??''));
    $candidate=$a6PartFiles[$partNumber]??'';
    if($candidate!=='' && is_file(__DIR__.'/'.$candidate))return $candidate;
  }
  // X50 A5 LV: the Mexico-specific difference pictures share part numbers
  // with other A5 LV images. Use the explicitly named Mexico files only for
  // records labelled as Mexico-specific; do not guess for other markets.
  if($modelRaw==='X50' && $variantRaw==='A5LV' && $kind==='part' &&
     str_contains(strtoupper((string)($r['Difference Note']??'')),'MEXICO')) {
    $mexicoImages=[
      '6608396081'=>'assets/X50/A5_LV/X50_A5_LV_MEXICO_6608396081.png',
      '6600057641'=>'assets/X50/A5_LV/X50_A5_LV_MEXICO_6600057641.png',
    ];
    $number=trim((string)($r['Part Number']??''));
    if(isset($mexicoImages[$number]) && is_file(__DIR__.'/'.$mexicoImages[$number]))
      return $mexicoImages[$number];
  }
  // X50 FL tagging: the spreadsheet names the use, while local assets use a variant prefix.
  if($modelRaw==='X50' && $variantRaw==='FL' && $kind==='tagging') {
    if(str_contains($tagRaw,'PRODUCTION LINE'))
      $special='assets/X50/FL/X50_FL_TAGGING.png';
    elseif(str_contains($tagRaw,'SUPPLY CHAIN'))
      $special='assets/X50/FL/X50_FL_LOOSE_PARTS.jpg';
  }

  // X50: exact mappings for renamed overview diagrams, market-specific cards and tagging examples.
  // Do not allow a generic part number search to substitute a different market's picture.
  if ($modelRaw==='X50') {
    $gradeRaw=strtoupper(preg_replace('/[^A-Z0-9]/i','',(string)($r['Grade']??'')));
    $labelRaw=strtoupper(preg_replace('/[^A-Z0-9]/i','',$pictureRaw));
    if($kind==='overview') {
      $overviewFiles=[
      'FL|STANDARD|FULLUNDERBODYASSY' => 'assets/X50/FL/X50_FL_UNDERBODY_ASSY_OVERVIEW.png',
      'FL|STANDARD|FRSTRUCTUREOVERVIEW' => 'assets/X50/FL/X50_FL_FRONT_STRUCTURE_OVERVIEW.png',
      'FL|STANDARD|FIREWALLASSY' => 'assets/X50/FL/X50_FL_FIREWALL_ASSY.png',
      'FL|STANDARD|SIDEMBRASSY' => 'assets/X50/FL/X50_FL_SIDE_MEMBER_ASSY.png',
      'FL|STANDARD|REARCOMBINATIONLAMPASSY' => 'assets/X50/FL/X50_FL_REAR_COMBINATION_LAMP_ASSY.png',
      'FL|STANDARD|FRONTFLOORASSY' => 'assets/X50/FL/X50_FL_FRONT_FLOOR_ASSY.png',
      'FL|STANDARD|REARFLOORASSY' => 'assets/X50/FL/X50_FL_REAR_FLOOR_ASSY.png',
      'A3PRG|STANDARD|FULLUNDERBODYASSY' => 'assets/X50/A3_PRG/X50_A3_PRG_UNDERBODY_ASSY_OVERVIEW.png',
      'A3PRG|STANDARD|FRSTRUCTUREOVERVIEW' => 'assets/X50/A3_PRG/X50_A3_PRG_FRONT_STRUCTURE_OVERVIEW.png',
      'A3PRG|STANDARD|FIREWALLASSY' => 'assets/X50/A3_PRG/X50_A3_PRG_FIREWALL_ASSY.png',
      'A3PRG|STANDARD|SIDEMBRASSY' => 'assets/X50/A3_PRG/X50_A3_PRG_SIDE_MEMBER_ASSY.png',
      'A3PRG|STANDARD|REARCOMBINATIONLAMPASSY' => 'assets/X50/A3_PRG/X50_A3_PRG_REAR_COMBINATION_LAMP_ASSY.png',
      'A3PRG|STANDARD|FRONTFLOORASSY' => 'assets/X50/A3_PRG/X50_A3_PRG_FRONT_FLOOR_ASSY.png',
      'A3PRG|STANDARD|REARFLOORASSY' => 'assets/X50/A3_PRG/X50_A3_PRG_REAR_FLOOR_ASSY.png',
      'A5LV|VIETNAM|FULLUNDERBODYASSY' => 'assets/X50/A5_LV/X50_A5_LV_UNDERBODY_ASSY_OVERVIEW.png',
      'A5LV|MEXICO|FULLUNDERBODYASSY' => 'assets/X50/A5_LV/X50_A5_LV_MEXICO_UNDERBODY_ASSY_OVERVIEW.png',
      'A5LV|VIETNAM|FRSTRUCTUREOVERVIEW' => 'assets/X50/A5_LV/X50_A5_LV_FRONT_STRUCTURE_OVERVIEW.png',
      'A5LV|MEXICO|FRSTRUCTUREOVERVIEW' => 'assets/X50/A5_LV/X50_A5_LV_FRONT_STRUCTURE_OVERVIEW.png',
      'A5LV|VIETNAM|FIREWALLASSY' => 'assets/X50/A5_LV/X50_A5_LV_FIREWALL_ASSY.png',
      'A5LV|MEXICO|FIREWALLASSY' => 'assets/X50/A5_LV/X50_A5_LV_FIREWALL_ASSY.png',
      'A5LV|VIETNAM|SIDEMBRASSY' => 'assets/X50/A5_LV/X50_A5_LV_SIDE_MEMBER_ASSY.png',
      'A5LV|MEXICO|SIDEMBRASSY' => 'assets/X50/A5_LV/X50_A5_LV_SIDE_MEMBER_ASSY.png',
      'A5LV|VIETNAM|REARCOMBINATIONLAMPASSY' => 'assets/X50/A5_LV/X50_A5_LV_REAR_COMBINATION_LAMP_ASSY.png',
      'A5LV|MEXICO|REARCOMBINATIONLAMPASSY' => 'assets/X50/A5_LV/X50_A5_LV_REAR_COMBINATION_LAMP_ASSY.png',
      'A5LV|VIETNAM|FRONTFLOORASSY' => 'assets/X50/A5_LV/X50_A5_LV_FRONT_FLOOR_ASSY.png',
      'A5LV|MEXICO|FRONTFLOORASSY' => 'assets/X50/A5_LV/X50_A5_LV_FRONT_FLOOR_ASSY.png',
      'A5LV|VIETNAM|REARFLOORASSY' => 'assets/X50/A5_LV/X50_A5_LV_REAR_FLOOR_ASSY.png',
      'A5LV|MEXICO|REARFLOORASSY' => 'assets/X50/A5_LV/X50_A5_LV_MEXICO_REAR_FLOOR_ASSY.png',
      'A6GMY|STANDARD|FULLUNDERBODYASSY' => 'assets/X50/A6_GMY/X50_A6_GMY_UNDERBODY_ASSY_OVERVIEW.png',
      'A6GMY|STANDARD|FRSTRUCTUREOVERVIEW' => 'assets/X50/A6_GMY/X50_A6_GMY_FRONT_STRUCTURE_OVERVIEW.png',
      'A6GMY|STANDARD|FIREWALLASSY' => 'assets/X50/A6_GMY/X50_A6_GMY_FIREWALL_ASSY.png',
      'A6GMY|STANDARD|SIDEMBRASSY' => 'assets/X50/A6_GMY/X50_A6_GMY_SIDE_MEMBER_ASSY.png',
      'A6GMY|STANDARD|REARCOMBINATIONLAMPASSY' => 'assets/X50/A6_GMY/X50_A6_GMY_REAR_COMBINATION_LAMP_ASSY.png',
      'A6GMY|STANDARD|FRONTFLOORASSY' => 'assets/X50/A6_GMY/X50_A6_GMY_FRONT_FLOOR_ASSY.png',
      'A6GMY|STANDARD|REARFLOORASSY' => 'assets/X50/A6_GMY/X50_A6_GMY_REAR_FLOOR_ASSY.png'
      ];
      $key=$variantRaw.'|'.$gradeRaw.'|'.$labelRaw;
      $target=$overviewFiles[$key]??'';
      if($target!=='' && is_file(__DIR__.'/'.$target))return $target;
    }
    if($kind==='tagging') {
      $tagFiles=[
        'FL'=>['assets/X50/FL/X50_FL_TAGGING.png','assets/X50/FL/X50_FL_LOOSE_PARTS.jpg'],
        'A3PRG'=>['assets/X50/A3_PRG/X50_A3_PRG_TAGGING.png','assets/X50/A3_PRG/X50_A3_PRG_LOOSE_PARTS.png'],
        'A5LV'=>['assets/X50/A5_LV/X50_A5_LV_TAGGING.png','assets/X50/A5_LV/X50_A5_LV_LOOSE_PARTS.jpeg'],
        'A6GMY'=>['assets/X50/A6_GMY/X50_A6_GMY_TAGGING.jpg','assets/X50/A6_GMY/X50_A6_GMY_LOOSE_PARTS.png'],
      ];
      $pair=$tagFiles[$variantRaw]??null;
      if($pair) {
        $target=str_contains($tagRaw,'PRODUCTION LINE')?$pair[0]:(str_contains($tagRaw,'SUPPLY CHAIN')?$pair[1]:'');
        if($target!=='' && is_file(__DIR__.'/'.$target))return $target;
      }
    }
    // X50 A5 LV Front Floor: identify the CMBR MFA #30 OPL by its technical keywords.
    // The database wording may use a hyphen, underscore, or an expanded label.
    if($kind==='opl' && $variantRaw==='A5LV' &&
       strtoupper(trim((string)($r['Module']??'')))==='FF') {
      $oplKey=strtoupper(preg_replace('/[^A-Z0-9]/i','',$pictureRaw.' '.(string)($r['Assembly']??'')));
      if(str_contains($oplKey,'CMBR') && str_contains($oplKey,'MFA30') &&
         str_contains($oplKey,'MIDFLR')) {
        $file='assets/X50/OPL/CMBR MID FLR FRT LWR MFA #30 (A5 LV).png';
        if(is_file(__DIR__.'/'.$file))return str_replace('#','%23',$file);
      }
    }
    // Verified OPL documents for the X50 A5 LV Front Floor (FF) module.
    // The Mexico source image uses a generic filename despite saying MEXICO inside.
    if($kind==='opl' && $variantRaw==='A5LV') {
      $oplTitle=strtoupper(trim(preg_replace('/\s+/',' ',(string)$pictureRaw)));
      $gradeRaw=strtoupper(trim((string)($r['Grade']??'')));
      $verifiedOpl='';
      if($oplTitle==='OPL - MID FLOOR ASSY A5 LV MEXICO' && $gradeRaw==='MEXICO') {
        $verifiedOpl='assets/X50/OPL/MID FLOOR ASSY (A5 LV).png';
      } elseif($oplTitle==='OPL - CMBR MID FLR FRT LWR MFA #30') {
        $verifiedOpl='assets/X50/OPL/CMBR MID FLR FRT LWR MFA #30 (A5 LV).png';
      }
      if($verifiedOpl!=='' && is_file(__DIR__.'/'.$verifiedOpl))return str_replace('#','%23',$verifiedOpl);
    }
    if($kind==='opl') {
      $oplFiles=[
      'OPL - A PILLAR VERTICAL PABL ASSY RH & LH' => 'assets/X50/OPL/A PILLAR VERTICAL  PABL ASSY RH & LH (A5 LV).png',
      'OPL - FIREWALL ASSY A5 LV & A6 GMY' => 'assets/X50/OPL/FIREWALL_ASSY_A5_LV_A6_GMY.png',
      'OPL - FIREWALL ASSY FL & A3 PRG' => 'assets/X50/OPL/FIREWALL_ASSY_FL_A3_PRG.png',
      'OPL - MID FLOOR ASSY A5 LV VIETNAM' => 'assets/X50/OPL/MID_FLOOR_ASSY_A5_LV_VIETNAM.png',
      'OPL - MID FLOOR ASSY FL & PRG' => 'assets/X50/OPL/MID FLOOR ASSY (FL & PRG).png',
      'OPL - CMBR MID FLR FRT LWR MFA #30' => 'assets/X50/OPL/CMBR MID FLR FRT LWR MFA #30 (A5 LV).png',
      'OPL - BEAM BUMPER COMP 8891604203' => 'assets/X50/OPL/BEAM_BUMPER_COMP_A5_LV.png',
      'OPL - REAR END #110' => 'assets/X50/OPL/REAR_END_110_A5_LV_A3_PRG.png',
      "OPL - PLATE FRT COMP'MENT CONNECT ASSY LH 660062934" => 'assets/X50/OPL/PLATE FRT COMP_MENT CONNECT ASSY LH (A5 LV).png',
      'OPL - REAR SIDE MEMBER ASSY RH & LH' => 'assets/X50/OPL/REAR SIDE MEMBER RH & LH.png',
      'OPL - SIDE MEMBER BODY ASSY RR FLOOR RH' => 'assets/X50/OPL/OPL - SIDE MEMBER BODY ASSY RR FLOOR RH.png',
      'OPL - SIDE MEMBER BODY ASSY RR FLOOR LH' => 'assets/X50/OPL/OPL - SIDE MEMBER BODY ASSY RR FLOOR LH.png',
      'OPL - FIREWALL UPPER ASSY A5 LV & A6 GMY' => 'assets/X50/OPL/FIREWALL UPPER ASSY (A5 LV & A6 GMY).png',
      'OPL - PLATE ASSY RH FRT SIDE MEMBER OTR A6 GMY' => 'assets/X50/OPL/OPL - PLATE ASSY-RH FRT SIDE MEMBER OTR A6 GMY.png',
      'OPL - WHEEL ARCH COVER ASSY LH FRT A6 GMY' => 'assets/X50/OPL/OPL - WHEEL ARCH COVER ASSY LH FRT.png',
      'OPL - FIREWALL UPPER ASSY FL & A3 PRG' => 'assets/X50/OPL/FIREWALL UPPER ASSY (FL & A3 PRG).png',
      'OPL - FRONT FLOOR ASSY RH & LH FL & A3 PRG' => 'assets/X50/OPL/FRONT FLOOR ASSY RH & LH (FL & A3 PRG).png',
      'OPL - FRONT FLOOR ASSY RH & LH A5 LV & A6 GMY' => 'assets/X50/OPL/FRONT_FLOOR_ASSY_RH_LH_A5_LV_A6_GMY.png',
      'OPL - FRONT FLOOR ASSY RH & LH PRG 15D' => 'assets/X50/OPL/FRONT FLOOR ASSY RH & LH (PRG 15D).png',
      'OPL - MID TUNNEL ASSY FL A3 PRG & 15D PRG' => 'assets/X50/OPL/MID_TUNNEL_ASSY_FL_A3_PRG.png',
      'OPL - MID TUNNEL ASSY A5 LV & A6 GMY' => 'assets/X50/OPL/MID TUNNEL ASSY (A5 LV & A6 GMY.png',
      'OPL - SIDE REINF BEAM ASSY LH FL & A3 PRG PW937161' => 'assets/X50/OPL/SIDE REINF BEAM LH FL & PRG.png',
      'OPL - SIDE REINF BEAM ASSY RH FL & A3 PRG PW937338' => 'assets/X50/OPL/SIDE_REINF_BEAM_RH_FL_PRG.png',
      'OPL - SIDE REINF BEAM ASSY LH A5 LV VIETNAM PW939200' => 'assets/X50/OPL/SIDE_REINF_BEAM_LH_A5_LV_VIE.png',
      'OPL - SIDE REINF BEAM ASSY RH A5 LV MEX VIE PW939199' => 'assets/X50/OPL/SIDE REINF BEAM RH A5 LV MEX&VIE.png',
      'OPL - D PILLAR GUIDE WATER BOARD RH FL & A5 LV' => 'assets/X50/OPL/D PLR GUIDE WATER BOARD RH FL  .png',
      'OPL - D PILLAR GUIDE WATER BOARD LH FL & A5 LV' => 'assets/X50/OPL/D PLR GUIDE WATER BOARD LH .png',
      'OPL - D PILLAR GUIDE WATER BOARD RH A3 PRG & A6 GMY' => 'assets/X50/OPL/D PLR GUIDE WATER BOARD RH  A3.png',
      'OPL - D PILLAR GUIDE WATER BOARD LH A3 PRG & A6 GMY' => 'assets/X50/OPL/D PLR GUIDE WATER BOARD LH  A3 .png'
      ];
      $target=$oplFiles[$pictureRaw]??'';
      // A5 LV Mexico-specific MID FLOOR OPL has no verified dedicated Mexico asset.
      if($target!=='' && is_file(__DIR__.'/'.$target))return str_replace('#','%23',$target);
    }
  }

  if($modelRaw==='S70') {
    // Assembly overview for S70 AI-MY(LV): use the grade-specific local diagram.
    // The Flagship file was originally named ENGINE_ROOM_ASS (without the Y).
    if($kind==='overview' && $variantRaw==='AIMYLV' &&
       preg_replace('/[^A-Z0-9]/i','',$pictureRaw)==='FRONTSTRUCTUREASSY') {
      $gradeRaw=strtoupper(preg_replace('/[^A-Z0-9]/i','',(string)($r['Grade']??'')));
      $gradeFiles=[
        'EXECUTIVE'=>'assets/S70/AI-MY(LV) EXECUTIVE/S70_AI_MY_LV_EXECUTIVE_ENGINE_ROOM_ASSY.png',
        'PREMIUM'=>'assets/S70/AI-MY(LV) PREMIUM/S70_AI_MY_LV_PREMIUM_ENGINE_ROOM_ASSY.png',
        'FLAGSHIP'=>'assets/S70/AI-MY(LV) FLAGSHIP/S70_AI_MY_LV_FLAGSHIP_ENGINE_ROOM_ASS.png',
      ];
      $candidate=$gradeFiles[$gradeRaw]??'';
      if($candidate!=='' && is_file(__DIR__.'/'.$candidate))return $candidate;
    }
    if($kind==='part' && $variantRaw==='MC' && $partNameRaw==='STANDARD SPECIFICATION')
      $special='assets/S70/Different part/S70_MC_STANDARD.png';
    if($kind==='part' && $variantRaw==='FL' && strtoupper(trim((string)($r['Part Number']??'')))==='8902582068')
      $special='assets/S70/Different part/S70_FL_8902582068.jpg';
    if($kind==='tagging') {
      $vmap=['MC'=>'MC','MC2'=>'MC2','FL'=>'FL'];
      if(isset($vmap[$variantRaw])) {
        if(str_contains($tagRaw,'PRODUCTION LINE')) $special='assets/S70/tagging/S70_'.$vmap[$variantRaw].'_TAGGING.'.($variantRaw==='FL'?'jpg':'png');
        elseif(str_contains($tagRaw,'SUPPLY CHAIN')) $special='assets/S70/tagging/S70_'.$vmap[$variantRaw].'_LOOSE_PARTS.png';
      }
      if($variantRaw==='AIMYLV') {
        if(str_contains($tagRaw,'PRODUCTION LINE')) $special='assets/S70/tagging/S70_AI_MY_LV_AUTO_TAGGING.jpg';
        elseif(str_contains($tagRaw,'SUPPLY CHAIN')) $special='assets/S70/tagging/S70_AI_MY_LV_MANUAL_TAGGING.jpg';
      }
    }
    if($kind==='opl' && $variantRaw==='MC2' && str_contains($pictureRaw,'BRACKET-TCU MOUNTING'))
      $special='assets/S70/MC2/S70_MC2_BRACKET_FULL.png';
  }
  if($special!=='' && is_file(__DIR__.'/'.$special))return $special;
  $norm=static fn($v):string=>strtoupper(preg_replace('/[^A-Z0-9]/i','',(string)$v));
  $model=$norm($r['Model']??''); $variant=$norm($r['Variant']??'');
  $grade=$norm($r['Grade']??''); $part=$norm($r['Part Number']??'');
  $descs=array_filter(array_unique(array_map($norm,[$name,$r['Picture Name']??'',$r['Part Name']??'',$r['Display Name']??'',$r['Tagging Type']??''])));
  if($kind==='opl' && !$part && preg_match('/([A-Z]{0,3}[0-9]{8,12}[A-Z0-9]*)\s*$/i',$name,$m))$part=$norm($m[1]);
  $pool=[];
  foreach($files as $f){
    if($model && !str_starts_with($f['base'],$model) && !str_contains($f['dir'],$model))continue;
    // Exclude dedicated files for other vehicle variants.
    if($model==='X50' && $variant){
      $folder='';
      if(preg_match('~assets/X50/([^/]+)/~i',$f['path'],$m))$folder=$norm($m[1]);
      if($folder && !in_array($folder,[$variant,'OPL','IMAGES'],true))continue;
      if($folder==='OPL' && $kind!=='opl')continue;
    }
    if($model==='S70' && $variant){
      $folder='';if(preg_match('~assets/S70/([^/]+)/~i',$f['path'],$m))$folder=$norm($m[1]);
      if($kind==='opl'){if($folder!=='OPL')continue;}
      elseif($folder==='OPL')continue;
      if($variant==='AIMYLV'){
        if($folder==='DIFFERENTPART' && $kind!=='part')continue;
        if(str_starts_with($folder,'AIMYLV') && $grade && $folder!==$variant.$grade)continue;
        if(in_array($folder,['MC','MC2','FL'],true))continue;
      } elseif($folder && !in_array($folder,[$variant,'TAGGING','DIFFERENTPART','IMAGES'],true))continue;
      if($folder==='DIFFERENTPART' && $kind!=='part')continue;
    }
    $pool[]=$f;
  }
  // Prefer exact part identifiers (including model / grade prefixes).
  if($part){
    $found=[];
    foreach($pool as $f){
      if(preg_match('/(?:^|[^A-Z0-9])'.preg_quote($part,'/').'(?:$|[^A-Z0-9])/i',strtoupper($f['filename'])) ||
         str_ends_with($f['base'],$part) || ($kind==='opl' && str_contains($f['base'],$part)))$found[]=$f;
    }
    if(count($found)===1)return $found[0]['path'];
    if(count($found)>1){
      if($grade){
        $graded=array_values(array_filter($found,static fn($f)=>str_contains($f['base'],$grade)||str_contains($f['dir'],$grade)));
        if(count($graded)===1)return $graded[0]['path'];
      }
      $preferred=array_values(array_filter($found,static fn($f)=>$kind==='opl'?str_contains($f['dir'],'OPL'):!str_contains($f['dir'],'OPL')));
      if(count($preferred)===1)return $preferred[0]['path'];
    }
  }
  $aliases=['FULLUNDERBODYASSY'=>['UNDERBODYASSYOVERVIEW','UNDERBODYASSY','UNDERBODYASSEMBLY'],
    'FRONTSTRUCTUREASSY'=>['ENGINEROOMASSY','FRONTSTRUCTUREOVERVIEW','FRONTSTRUCTUREASSY','ENGINEROOMCOMPLETE'],
    'FRSTRUCTUREOVERVIEW'=>['FRONTSTRUCTUREOVERVIEW'],
    'FRONTFLOORASSY'=>['FRONTFLOORASSY','FRONTFLOORASSEMBLY'],
    'REARFLOORASSY'=>['REARFLOORASSY','REARFLOORASSEMBLY'],
    'SIDEMBRASSY'=>['SIDEMEMBERASSY'],
    'REARCOMBINATIONLAMPASSY'=>['REARCOMBINATIONLAMPASSY']];
  $wanted=[];
  foreach($descs as $d){$wanted[]=$d;foreach($aliases[$d]??[] as $alias)$wanted[]=$alias;}
  $found=[];
  foreach($pool as $f){
    foreach($wanted as $d){
      if(strlen($d)<5)continue;
      if(str_ends_with($f['base'],$d)){$found[$f['path']]=$f;break;}
    }
  }
  if(count($found)===1)return array_key_first($found);
  // If multiple markets or grades share a filename, only match the requested one.
  if(count($found)>1 && $grade){
    $subset=array_filter($found,static fn($f)=>str_contains($f['base'],$grade)||str_contains($f['dir'],$grade));
    if(count($subset)===1)return array_key_first($subset);
  }
  return ''; // Never guess an ambiguous image.
}

function overview(string $model,string $variant,string $grade,string $module):array{$out=[];foreach(matching('OVERVIEW_DATABASE',['Model'=>$model,'Variant'=>$variant,'Grade'=>$grade,'Module'=>$module],false) as $i=>$r){$name=vals($r,'Picture Name');$out[]=['pictureName'=>$name,'partNumber'=>'','assemblyName'=>vals($r,'Assembly',$name),'imageUrl'=>imagePath($r,$name,str_starts_with(strtoupper($name),'OPL')?'opl':'overview'),'originalLink'=>'','sortOrder'=>(float)((isset($r['Sort Order']) && trim((string)$r['Sort Order']) !== '') ? $r['Sort Order'] : 999),'showLegend'=>active(['Active'=>vals($r,'Show Legend')]),'sheetRow'=>$i+2];}order($out);return $out;}

// The grade selection cards must show their dedicated title images,
// not the assembly diagrams used on later pages.
function gradeCardImagePath(string $model,string $variant,string $grade,array $record):string {
  $override=visMappedImage(array_merge($record,['Model'=>$model,'Variant'=>$variant,'Grade'=>$grade]),'GRADE CARD','grade_card');
  if($override!==null)return $override;
  if(visPlant()!=='fif')return ''; // Other plants require their own explicit image assignments.
  $norm=static fn(string $value):string => strtoupper(preg_replace('/[^A-Z0-9]/i','',$value));
  if ($norm($model)==='S70' && $norm($variant)==='AIMYLV') {
    $code=$norm($grade);
    if (in_array($code,['EXECUTIVE','PREMIUM','FLAGSHIP'],true)) {
      $folder='AI-MY(LV) '.$code;
      $path='assets/S70/'.$folder.'/S70_AI_MY_LV_'.$code.'_GRADE.png';
      if (is_file(__DIR__.'/'.$path)) return $path;
    }
  }
  if($norm($model)==='X50' && $norm($variant)==='A5LV') {
    $code=$norm($grade);
    if(in_array($code,['VIETNAM','MEXICO'],true)) {
      $path='assets/X50/A5_LV/X50_A5_LV_'.$code.'_MARKET.png';
      if(is_file(__DIR__.'/'.$path))return $path;
    }
  }
  // Other vehicle/market cards keep their current database image mapping.
  // Do not substitute an unrelated assembly diagram if a card image is missing.
  return imagePath($record);
}

function grades(string $model,string $variant):array{$routes=matching('FRONT_PAGE',['Model'=>$model,'Variant'=>$variant]);$codes=[];foreach($routes as $r)$codes[vals($r,'Grade')]=true;$out=[];foreach(array_keys($codes) as $code){if(!$code)continue;$data=matching('GRADE_SELECTION',['Model'=>$model,'Variant'=>$variant,'Grade'=>$code]);$d=$data[0]??[];$o=overview($model,$variant,$code,'ALL');$out[]=['code'=>$code,'displayName'=>vals($d,'Display Name',$code),'description'=>vals($d,'Description','Select this grade to continue.'),'imageUrl'=>gradeCardImagePath($model,$variant,$code,$d),'originalLink'=>'','sortOrder'=>(float)($d['Sort Order']??999)];}order($out);return $out;}
function getLastUpdated():array{return ['lastUpdated'=>visLastUpdated()];}
function getHomePageSettings():array{$o=['found'=>false,'logoUrl'=>'','logoAlt'=>'Company logo','backgroundUrl'=>''];foreach(matching('SITE_SETTINGS') as $r){$setting=strtoupper(vals($r,'Setting'));$path=imagePath($r);if($setting==='LOGO'){$o['logoUrl']=$path?:'assets/PHN transparent logo.png';$o['logoAlt']=vals($r,'Alt Text','Company logo');}if($setting==='BACKGROUND')$o['backgroundUrl']=$path?:'assets/PHN building photo.jpg';}$o['found']=($o['logoUrl']!==''||$o['backgroundUrl']!=='');return $o;}
function getModelCards():array{$out=[];foreach(matching('MODEL_SELECTION') as $r){$model=vals($r,'Model');$files=glob(__DIR__.'/assets/'.$model.'/*');$path=imagePath($r) ?: (visPlant()!=='fif'?'':(is_file(__DIR__.'/assets/'.$model.' RED.png')?'assets/'.$model.' RED.png':(is_file(__DIR__.'/assets/'.$model.' RED.webp')?'assets/'.$model.' RED.webp':'')));$out[]=['model'=>$model,'displayName'=>vals($r,'Display Name',$model),'imageUrl'=>$path,'originalLink'=>'','sortOrder'=>(float)($r['Sort Order']??999)];}order($out);return ['found'=>(bool)$out,'message'=>$out?'':'No active models were found.','models'=>$out];}
function getVariantPage(string $model):array{$routes=matching('FRONT_PAGE',['Model'=>$model]);$codes=[];foreach($routes as $r)$codes[vals($r,'Variant')]=true;$out=[];foreach(array_keys($codes) as $code){if(!$code)continue;$colour=matching('VARIANT_COLOUR',['Model'=>$model,'Variant'=>$code]);$c=$colour[0]??[];$dif=[];foreach(matching('VARIANT_DIFFERENCE',['Model'=>$model,'Variant'=>$code]) as $d){$dif[]=['partName'=>vals($d,'Part Name'),'partNumber'=>vals($d,'Part Number'),'differenceNote'=>vals($d,'Difference Note'),'imageUrl'=>imagePath($d,'','part'),'originalLink'=>'','sortOrder'=>(float)($d['Sort Order']??999)];}order($dif);$g=grades($model,$code);$color=vals($c,'Custom Hex')?:(['GREEN'=>'#BADCB3','BLUE'=>'#9FC7EE','WHITE'=>'#FFFFFF','RED'=>'#EBC0C0','YELLOW'=>'#F1DC8E'][strtoupper(vals($c,'Colour Name'))]??'#DDE9F7');$out[]=['code'=>$code,'label'=>vals($c,'Display Name',$code),'summary'=>vals($dif[0]??[],'differenceNote','Select to see assembly details.'),'differences'=>$dif,'grades'=>array_column($g,'code'),'gradeCount'=>count($g),'defaultGrade'=>count($g)===1?$g[0]['code']:'','colourName'=>vals($c,'Colour Name'),'accentColour'=>$color,'swatchColour'=>$color,'backgroundColour'=>'#FFFFFF','textColour'=>'#172033','borderColour'=>$color,'sortOrder'=>(float)($c['Sort Order']??999)];}order($out);return ['found'=>(bool)$out,'selection'=>['model'=>$model],'variants'=>$out,'paperColours'=>matching('PAPER_COLOUR_INFO'),'message'=>$out?'':'No active variants found.'];}
function comparison(string $model,string $variant,array $gradeCodes):array{
 $rows=matching('GRADE_COMPARISON',['Model'=>$model,'Variant'=>$variant]);$columns=[];
 foreach($gradeCodes as $g){$matched=false;foreach($rows as $r){foreach(['Part Name','Part Number','Image Link'] as $suffix){if(array_key_exists(ucwords(strtolower($g)).' '.$suffix,$r)||array_key_exists(strtoupper($g).' '.$suffix,$r)||array_key_exists(ucfirst(strtolower($g)).' '.$suffix,$r))$matched=true;}}if($matched)$columns[]=['code'=>$g,'label'=>ucwords(strtolower($g))];}
 $out=[];foreach($rows as $r){$items=[];foreach($columns as $c){$prefix=ucwords(strtolower($c['code']));$n=vals($r,$prefix.' Part Name');$num=vals($r,$prefix.' Part Number');$name=$n?:vals($r,'Part Name');$items[]=['code'=>$c['code'],'partName'=>$name,'partNumber'=>$num,'imageUrl'=>imagePath(array_merge($r,['Part Number'=>$num,'Grade'=>$c['code']]),$name,'part'),'originalLink'=>''];}$out[]=['partName'=>vals($r,'Part Name','Different Part'),'items'=>$items,'sortOrder'=>(float)($r['Sort Order']??999)];}order($out);return ['columns'=>$columns,'rows'=>$out];
}
function getGradePage(string $model,string $variant):array{$g=grades($model,$variant);$market=count(array_intersect(['VIETNAM','MEXICO'],array_column($g,'code')))>0;$type=$market?'market':'grade';$comparison=comparison($model,$variant,array_column($g,'code'));return ['found'=>(bool)$g,'selection'=>['model'=>$model,'variant'=>$variant],'selectionType'=>$type,'selectionLabel'=>$market?'Market':'Grade','selectionHeading'=>$market?'Choose Market':'Choose Grade','selectionDescription'=>$market?'Select your market specification.':'Select a grade to view its underbody and modules.','comparisonTitle'=>'What Is Different Between '.$model.' '.$variant.' Grades?','comparisonDescription'=>'Compare available grades.','comparisonColumns'=>$comparison['columns'],'comparisons'=>$comparison['rows'],'grades'=>$g,'message'=>$g?'':'No active grades found.'];}
function getVariantSelection(string $model,string $variant,string $grade):array{$routes=matching('FRONT_PAGE',['Model'=>$model,'Variant'=>$variant,'Grade'=>$grade]);$under=overview($model,$variant,$grade,'ALL');if(!$under)$under=overview($model,$variant,$grade,'UNDERBODY');$moduleLabels=['ER'=>'Engine Room','FF'=>'Front Floor','RF'=>'Rear Floor'];$codes=[];foreach($routes as $r){$code=vals($r,'Module');if($code)$codes[$code]=true;}$mods=[];foreach(array_keys($codes) as $code)$mods[]=['code'=>$code,'label'=>$moduleLabels[$code]??$code];usort($mods,fn($a,$b)=>array_search($a['code'],['ER','FF','RF'])<=>array_search($b['code'],['ER','FF','RF']));return ['found'=>(bool)$routes,'selection'=>['model'=>$model,'variant'=>$variant,'grade'=>$grade],'underbody'=>$under[0]??null,'modules'=>$mods,'message'=>$routes?'':'No active route found.'];}
function getParts(string $model,string $variant,string $grade,string $module):array{$items=overview($model,$variant,$grade,$module);$assemblies=[];foreach($items as $item){$key=$item['assemblyName'];if(!isset($assemblies[$key]))$assemblies[$key]=['assemblyName'=>$key,'overview'=>[],'sortOrder'=>$item['sortOrder']];$assemblies[$key]['overview'][]=$item;} $tags=[];foreach(matching('VARIANT_TAGGING',['Model'=>$model,'Variant'=>$variant,'Module'=>$module]) as $r){$tags[]=['taggingType'=>vals($r,'Tagging Type'),'displayName'=>vals($r,'Display Name'),'description'=>vals($r,'Description'),'imageUrl'=>imagePath($r,'','tagging'),'originalLink'=>'','sortOrder'=>(float)($r['Sort Order']??999)];}order($tags);$c=matching('VARIANT_COLOUR',['Model'=>$model,'Variant'=>$variant]);$color=vals($c[0]??[],'Colour Name');$paper=matching('PAPER_COLOUR_INFO',['Colour Name'=>$color]);$p=$paper[0]??[];$req=['found'=>(bool)$p,'model'=>$model,'variant'=>$variant,'variantLabel'=>vals($c[0]??[],'Display Name',$variant),'colourName'=>$color,'brand'=>vals($p,'Brand'),'gsm'=>vals($p,'GSM'),'colourCode'=>vals($p,'Colour Code'),'imageUrl'=>imagePath($p)];return ['found'=>(bool)$items,'selection'=>compact('model','variant','grade','module'),'assemblyCount'=>count($assemblies),'totalPictures'=>count($items),'taggingCount'=>count($tags),'taggingExamples'=>$tags,'tagPaperRequirement'=>$req,'assemblies'=>array_values($assemblies),'message'=>$items?'':'No overview information found.'];}
if(!defined('VIS_LIBRARY_MODE'))try{
 if($_SERVER['REQUEST_METHOD']!=='POST')throw new Exception('POST required');
 $data=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);$plant=$data['plant']??'fif';if(!is_string($plant))throw new InvalidArgumentException('Unknown plant');visSetPlant($plant);$a=$data['action']??'';$args=$data['args']??[];
 if(!is_string($a)||!in_array($a,['getHomePageSettings','getModelCards','getVariantPage','getGradePage','getVariantSelection','getParts','getLastUpdated'],true))throw new Exception('Invalid action');
 $reflection=new ReflectionFunction($a);if(count($args)!==$reflection->getNumberOfRequiredParameters())throw new Exception('Incorrect number of parameters');
 foreach($args as $v)if(!is_scalar($v)||strlen((string)$v)>200)throw new Exception('Invalid argument');
 echo json_encode($a(...$args),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
}catch(Throwable $e){http_response_code($e instanceof InvalidArgumentException?400:500);echo json_encode(['error'=>$e->getMessage()]);}
