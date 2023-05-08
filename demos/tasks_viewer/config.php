<?
require __DIR__.'/../../tests/phpunit_bootstrap.php';

$GLOBALS['DIC'] = new \S5\Pimple();

$d = $GLOBALS['phpUnitParams']['db'];



$DIC['pdo'] = new \PDO("mysql:host=$d[host];dbname=$d[name];charset=UTF8", $d['login'], $d['password']);

$DIC['dbAdapter'] = new \S5\Db\Adapters\PdoAdapter($DIC['pdo']);

$DIC['dbUtils'] = new \S5\Db\DbUtils($DIC['dbAdapter']);

$DIC['tasksManager'] = new \S5\TasksManager\TasksManager(
	$DIC('dbAdapter', 'dbUtils') + [
		'lockFilePath' => __DIR__.'/.lock',
	]
);

$DIC['tasksViewer'] = new \S5\TasksManager\Viewer\TasksViewer(
	$DIC('tasksManager')
);



$DIC['dbFile'] = new \S5\IO\File(__DIR__.'/bg_workers/file/db.php');
