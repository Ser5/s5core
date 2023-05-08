<?
require_once __DIR__.'/config.php';
require_once __DIR__.'/bg_workers/db/db_manager.php';
use \S5\IO\{Directory, File};

$tm        = $DIC['tasksManager'];
$tv        = $DIC['tasksViewer'];

/*$tasksList = $tm->getList(['order_by' => 'q.id DESC']);

foreach ($tasksList as $task) {
	foreach ($task->_logs as &$e) {
		$e = json_decode($e);
		if (!is_object($e)) {
			$e = (object)['message' => $e, 'type' => false, 'level' => 0];
		}
	}
	unset($e);
}*/
$tasksList = [];

$viewerAssetsDirPath = __DIR__.'/../../src/S5/TasksManager/Viewer/html/assets/';
foreach (['vue.global.js', 'script.js', 'styles.css'] as $fileName) {
	copy("$viewerAssetsDirPath/$fileName", __DIR__."/assets/$fileName");
}



?><!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Просмотр задач</title>
	<script src="/assets/vue.global.js"></script>
	<link rel="stylesheet" href="/assets/main.css">
	<link rel="stylesheet" href="/assets/styles.css">
</head>
<body>

<?$tv->show(['tasks_list' => $tasksList])?>

<script src="/assets/script.js"></script>
<script>
let viewer = new S5.TasksViewer({
	//ajaxUrl: '/bg_workers/file/ajax.php',
	ajaxUrl: '/bg_workers/db/ajax.php',
	states: {
		NEW:     <?=$tm::NEW?>,
		RUNNING: <?=$tm::RUNNING?>,
		DONE:    <?=$tm::DONE?>,
	},
});
</script>

</body>
</html>
