<?php
function check(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message.PHP_EOL;}
