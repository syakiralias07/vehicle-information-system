<?php
declare(strict_types=1);

function visPlants():array {
    return ['shah-alam-1'=>'Shah Alam 1','shah-alam-2'=>'Shah Alam 2','fif'=>'FiF',
        'tanjung-malim'=>'Tanjung Malim','rasa'=>'Rasa','pegoh'=>'Pegoh','pekan'=>'Pekan'];
}
function visSetPlant(string $plant):void {
    if(!array_key_exists($plant,visPlants()))throw new InvalidArgumentException('Unknown plant');
    $GLOBALS['vis_plant']=$plant;
}
function visPlant():string {return $GLOBALS['vis_plant']??'fif';}
function visDataRoot():string {return defined('VIS_DATA_ROOT')?VIS_DATA_ROOT:__DIR__.'/data';}
function visPlantStorageReady():bool {return is_file(visDataRoot().'/plants/.ready');}
function visAssetBelongsToPlant(string $path):bool {
    if(!str_starts_with($path,'assets/admin-uploads/'))return true;
    $relative=substr($path,strlen('assets/admin-uploads/'));
    if(!str_contains($relative,'/'))return visPlant()==='fif';
    return str_starts_with($relative,visPlant().'/');
}
function visUploadPrefix():string {return 'assets/admin-uploads/'.visPlant().'/';}
