<?
require_once 'config.php';
$tm = $DIC['tasksManager'];



function initDb () {
	global $dbFile, $tm;
	$nowString = date('Y-m-d H:i:s');

	$dbData = [
		(object)[
			'id'          => 10,
			'_type_id'    => 3,
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
			'started_at'  => $nowString,
			'updated_at'  => $nowString,
			'finished_at' => null,
		],
	];
	for ($id = 9; $id >= 2; $id--) {
		$dbData[] = (object)[
			'id'          => $id,
			'_type_id'    => $id,
			'_type_name'  => 'Завершённая задача',
			'state_id'    => $tm::DONE,
			'_state_code' => 'done',
			'_state_name' => 'Завершена',
			'progress'    => 100,
			'_progress' => (object)[
				'left_time_data' => (object)[
					'hms' => '0:00:00',
				],
			],
			'_logs_list'  => [],
			'created_at'  => '2023-03-01 00:00:00',
			'started_at'  => '2023-03-01 00:10:00',
			'updated_at'  => '2023-03-01 00:20:00',
			'finished_at' => '2023-03-01 00:20:00',
		];
	}
	$dbData[] = (object)[
		'id'          => 1,
		'_type_id'    => 1,
		'_type_name'  => 'Багованная задача',
		'state_id'    => $tm::ERROR,
		'_state_code' => 'error',
		'_state_name' => 'Ошибка',
		'progress'    => 50,
		'_progress' => (object)[
			'left_time_data' => (object)[
				'hms' => '−:−:−',
			],
		],
		'_logs_list'  => [],
		'created_at'  => '2023-01-01 00:00:00',
		'started_at'  => '2023-01-01 00:10:00',
		'updated_at'  => '2023-01-01 00:20:00',
		'finished_at' => null,
	];

	$dbFile->putPhpReturn($dbData);

	return $dbData;
}



//При запуске скрипта всегда инициализируем БД
$dbData = initDb();



$progress = new \S5\Progress();
$progStep = 5;
for ($progValue = $progStep; $progValue <= 100; $progValue += $progStep) {
	echo "$progValue\n";

	//Это мы типа поработали
	sleep(1);
	//Обновим данные по тому, чего наработали
	$task             = &$dbData[0];
	$task->progress   = $progValue;
	$task->updated_at = date('Y-m-d H:i:s');
	$progress->set($progValue);
	$leftTimeData = $progress->getLeftTimeData();
	$task->_progress->left_time_data->hms = ($leftTimeData ? $leftTimeData->hms : '-:-:-');
	$task->_logs_list[] = (object)['message' => "Лог $progValue", 'type' => false, 'level' => 1];
	if ($progValue == 100) {
		$task->finished_at = $task->updated_at;
		$task->state_id    = $tm::DONE;
		$task->_state_code = 'done';
		$task->_state_name = 'Завершена';
	}

	//Записываем для дальнейшего считывания через ajax.php
	$dbFile->putPhpReturn($dbData);
}
