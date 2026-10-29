<?php
declare(strict_types=1);
require_once __DIR__.'/plants.php';
try{
    $plant=$_GET['plant']??'fif';
    if(!is_string($plant))throw new InvalidArgumentException('Unknown plant');
    visSetPlant($plant);
}catch(InvalidArgumentException $e){
    http_response_code(400);exit('Unknown plant. Please return to index.php and select a plant.');
}
