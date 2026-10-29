<?php
if(PHP_SAPI!=='cli-server'){http_response_code(404);exit;}
define('VIS_DATA_ROOT',__DIR__.'/.runtime/data');
ini_set('session.save_path',__DIR__.'/.runtime/sessions');
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(!in_array($path,['/admin-api.php','/api.php'],true)){http_response_code(404);exit;}
require dirname(__DIR__).$path;
