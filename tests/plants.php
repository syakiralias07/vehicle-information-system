<?php
require __DIR__.'/../json-store.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS $message\n";}
check(function_exists('visPlants'),'plant registry exists');
check(count(visPlants())===7,'seven plants');
check(visPlant()==='fif','legacy default FiF');
visSetPlant('pekan');
check(str_contains(visDataFile('tables/MODEL_SELECTION'),'/plants/pekan/'),'catalogue path scoped');
check(!str_contains(visDataFile('admins'),'/plants/'),'admin accounts shared');
try{visSetPlant('../fif');throw new RuntimeException('accepted traversal');}catch(InvalidArgumentException $e){echo "PASS reject invalid plant\n";}
visSetPlant('fif');
check(visAssetBelongsToPlant('assets/admin-uploads/abc.png'),'legacy upload belongs to FiF');
visSetPlant('pekan');
check(!visAssetBelongsToPlant('assets/admin-uploads/abc.png'),'legacy upload hidden elsewhere');
check(visAssetBelongsToPlant('assets/admin-uploads/pekan/abc.png'),'own upload allowed');
check(!visAssetBelongsToPlant('assets/admin-uploads/fif/abc.png'),'other upload rejected');
