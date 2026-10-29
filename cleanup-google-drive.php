<?php
/** One-time VIS legacy Google Drive URL cleanup. CLI only. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__ . '/config.php';
$apply = in_array('--apply', $argv, true);
$confirmed = in_array('--backup-confirmed', $argv, true);
if ($apply && !$confirmed) { fwrite(STDERR, "First export your MySQL database; then use --apply --backup-confirmed.\n"); exit(2); }
$pdo = new PDO('mysql:host='.VIS_DB_HOST.';dbname='.VIS_DB_NAME.';charset=utf8mb4', VIS_DB_USER, VIS_DB_PASS,
  [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$tables=['FRONT_PAGE','SITE_SETTINGS','MODEL_SELECTION','VARIANT_DIFFERENCE','OVERVIEW_DATABASE','GRADE_SELECTION','GRADE_COMPARISON','VARIANT_COLOUR','PAPER_COLOUR_INFO','VARIANT_TAGGING'];
function purgeLegacyLinks(mixed $v, int &$cleared): mixed {
    if (is_array($v)) { foreach ($v as $k=>$sub) $v[$k]=purgeLegacyLinks($sub,$cleared); return $v; }
    if (is_string($v) && preg_match('~(?:drive\.google\.com|docs\.google\.com|googleusercontent\.com)~i',$v)) {
        // Only blank fields consisting of a legacy URL; never erase general descriptive text.
        if (preg_match('~^\s*https?://(?:drive\.google\.com|docs\.google\.com|[a-z0-9.-]*googleusercontent\.com)/\S*\s*$~i',$v)) {$cleared++;return '';}
    }
    return $v;
}
$rowsChanged=0;$urlsCleared=0;$remaining=[];
try {
 $pdo->beginTransaction();
 foreach ($tables as $table) {
   $stmt=$pdo->query('SELECT id,data FROM `'.$table.'` ORDER BY id');
   $upd=$pdo->prepare('UPDATE `'.$table.'` SET data=? WHERE id=?');
   $tableCount=0;
   foreach ($stmt as $entry) {
      $row=json_decode($entry['data'],true,512,JSON_THROW_ON_ERROR);$count=0;
      $clean=purgeLegacyLinks($row,$count);
      if($count){$urlsCleared+=$count;$rowsChanged++;$tableCount++;
        if($apply)$upd->execute([json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR),$entry['id']]);
      }
      $check=$apply?$clean:$row;
      if(str_contains(strtolower(json_encode($clean,JSON_UNESCAPED_UNICODE)),'drive.google.com'))
        $remaining[]=$table.' #'.$entry['id'].' (not a standalone Drive URL; review manually)';
   }
   if($tableCount)echo "$table: $tableCount records with legacy URLs\n";
 }
 if ($apply) {$pdo->commit();echo "APPLIED: ";}
 else {$pdo->rollBack();echo "DRY RUN ONLY: ";}
 echo "$urlsCleared Drive links eligible for removal across $rowsChanged records.\n";
 if($remaining){echo "Remaining ambiguous references (not altered):\n".implode("\n",array_slice($remaining,0,30))."\n";}
 if(!$apply)echo "Export the database in phpMyAdmin, then rerun with --apply --backup-confirmed.\n";
} catch(Throwable $e) {if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,"FAILED: ".$e->getMessage()."\n");exit(1);}
