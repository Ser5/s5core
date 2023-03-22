<?
require_once 'config.php';
$tm = $DIC['tasksManager'];



//$limitString = $_REQUEST['limit']  ?? false;
//$logId       = $_REQUEST['log_id'] ?? 0;



//$data = [];
//$tm->getList(['limit' => $limitString, '' => $logId]);



$dbData = include $dbFile->getPath() ?: [];
echo json_encode($dbData);
