<?
require_once 'config.php';
$tm = $DIC['tasksManager'];

$tm->deleteStorage();
$tm->initStorage();



$berriesTypeId = $tm->createType([
	'code'             => 'berries',
	'name'             => 'Собираем ягоды',
	'callback_type_id' => $tm::FUNCTION,
	'callback_method'  => 'collectBerries',
	'sort'             => 1,
]);

$cabbagesTypeId = $tm->createType([
	'code'             => 'cabbages',
	'name'             => 'Квасим капусту',
	'callback_type_id' => $tm::FUNCTION,
	'callback_method'  => 'sourCabbages',
	'sort'             => 2,
]);



$tm->create(['type_id' => $berriesTypeId,  'params' => '']);
$tm->create(['type_id' => $berriesTypeId,  'params' => '']);
$tm->create(['type_id' => $cabbagesTypeId, 'params' => '']);
$tm->create(['type_id' => $cabbagesTypeId, 'params' => '']);



/*$tm->createLogsList(
	4,
	array_map(
		fn($d) => json_encode(['message'=>$d[0], 'type'=>$d[1], 'level'=>$d[2]]),
		[
			['айн',      false,     1],
			['цвай',     'ok',      1],
			['полицай',  'info',    1],
			['драй',     'warning', 2],
			['фир',      'error',   2],
			['гренадир', 'error',   3],
			['всё!',     false,     false],
		]
	)
);*/



$tm->run();
$tm->deleteStorage();



function collectBerries (\Closure $taskUpdater, array $paramsList) {
	if ($paramsList) {
		$name = 'Неизвестная ягода';
	} else {
		$name = $paramsList[0];
	}
	static $typesList = [false, 'ok', 'info', 'warning', 'error'];
	for ($progress = 1; $progress <= 100; $progress++) {
		sleep(1);
		$message = "Прогресс $progress";
		$type    = $typesList[random_int(0, 4)];
		$level   = floor($progress / 20);
		$taskUpdater($progress, json_encode(compact('message', 'type', 'level')));
	}
}



function sourCabbages (\Closure $taskUpdater, array $paramsList) {
	collectBerries($taskUpdater, $paramsList);
}
