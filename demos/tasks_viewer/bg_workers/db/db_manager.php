<?
require_once __DIR__.'/../../config.php';
$dbAdapter = $DIC['dbAdapter'];
$tm        = $DIC['tasksManager'];



function initStorage () {
	global $dbAdapter, $tm;
	//$dbAdapter->query('DROP DATABASE IF EXISTS tasks_viewer_demo');
	//$dbAdapter->query('CREATE DATABASE tasks_viewer_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci');
	//$dbAdapter->query('USE tasks_viewer_demo');
	$tm->deleteStorage();
	$tm->initStorage();

	$taskTypeId = $tm->createType([
		'code'             => 'task',
		'name'             => 'Обычная задача',
		'callback_type_id' => $tm::FUNCTION,
		'callback_method'  => 'tasksProcessor',
	]);

	$buggyTaskTypeId = $tm->createType([
		'code'             => 'bug',
		'name'             => 'Багованная задача',
		'callback_type_id' => $tm::FUNCTION,
		'callback_method'  => 'tasksProcessor',
	]);

	//Отвалилась с ошибкой
	$tm->create([
		'type_id'  => $buggyTaskTypeId,
		'state_id' => $tm::ERROR,
		'progress' => 50,
	]);
	//Завершены
	for ($a = 0; $a < 7; $a++) {
		$tm->create([
			'type_id'  => $taskTypeId,
			'state_id' => $tm::DONE,
			'progress' => 100,
		]);
	}
	//Новые
	for ($a = 1; $a <= 2; $a++) {
		$tm->create([
			'type_id' => $taskTypeId,
			'params'  => serialize($a),
		]);
	}
}



function initStorageIfNotExists () {
	$dbAdapter = $GLOBALS['DIC']['dbAdapter'];
	if (!$dbAdapter->getObject("SHOW TABLES")) {
		initStorage();
	}
}



$progValue    = 1;
function tasksProcessor ($taskUpdater, array $taskParamsList) {
	global $tm, $progValue, $prevTaskHint;
	$taskHint = $taskParamsList[0];
	if ($taskHint == 1) {
		$progStep = 5;
	} else {
		$progStep = 1;
	}
	for ($progValue = $progStep; $progValue <= 100; $progValue += $progStep) {
		echo "$progValue\n";
		sleep(1);
		$taskUpdater($progValue, "Лог $progValue");
	}
}
