<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/json-store.php';
if(count($argv)!==3 || strlen($argv[2])<12){fwrite(STDERR,"Usage: php admin-create-user.php <username> <password of 12+ characters>\n");exit(1);}
$username=$argv[1];$password=$argv[2];
if(!preg_match('/^[A-Za-z0-9_.-]{1,80}$/',$username)){fwrite(STDERR,"Invalid username\n");exit(1);}
visUpdate('admins',[],static function(&$users)use($username,$password){$users[$username]=password_hash($password,PASSWORD_DEFAULT);});
echo "Admin account created or updated.\n";
