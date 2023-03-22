<?
require_once 'config.php';
$tm = $DIC['tasksManager'];



function initDb () {
	global $dbFile, $tm;
	$nowString = date('Y-m-d H:i:s');

	$dbData = [
		(object)[
			'id'          => 1,
			'_type_id'    => 1,
			'_type_name'  => 'Тут тип задачи',
			'state_id'    => $tm::RUNNING,
			'_state_code' => 'running',
			'_state_name' => 'В работе',
			'progress'    => 0,
			'_progress' => (object)[
				'left_time_data' => (object)[
					'hms' => '−:−:−',
				],
			],
			'_logs_list'  => [],
			'created_at'  => $nowString,
			'updated_at'  => $nowString,
			'started_at'  => $nowString,
			'finished_at' => null,
		]
	];

	$dbFile->putPhpReturn($dbData);

	return $dbData;
}



//При запуске скрипта всегда инициализируем БД
$dbData = initDb();


for ($progress = 0; $progress <= 100; $progress += 5) {
	//Если вдруг обнаружится, что файла БД нет, то пересоздаём его
	if (!$dbFile->isExists()) {
		$dbData = initDb();
	}

	//Это мы типа поработали
	sleep(1);
	//Обновим данные по тому, чего наработали
	$task             = &$dbData[0];
	$progress         = min($progress, 100);
	$task->progress   = $progress;
	$task->updated_at = date('Y-m-d H:i:s');
	$secondsLeft      = (100 - $task->progress) / 5;
	$task->_progress->left_time_data->hms = "0:00:$secondsLeft";
	$task->_logs_list[] = (object)['message' => "Лог $progress", 'type' => false, 'level' => 1];
	if ($progress == 100) {
		$task->finished_at = $task->updated_at;
		$task->_state_id   = $tm::DONE;
		$task->_state_code = 'done';
		$task->_state_name = 'Завершена';
	}
	echo "$progress\n";

	//Записываем для дальнейшего считывания через ajax.php
	$dbFile->putPhpReturn($dbData);
}
