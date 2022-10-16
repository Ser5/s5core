<?
namespace S5\TasksManager;
use S5\Db\Adapters\PdoAdapter;
use S5\Db\Adapters\CallbackAdapter;
use S5\Db\DbUtils;



class TasksManagerTest extends \S5\TestCase {
	private PdoAdapter $_pdoAdapter;
	private DbUtils    $_dbUtils;



	public function __construct (...$params) {
		parent::__construct(...$params);
		$pdo                 = new \PDO('mysql:dbname=test;host=127.0.0.1;charset=UTF8', 'root', '');
		$this->_pdoAdapter   = new PdoAdapter($pdo);
		$this->_dbUtils      = new DbUtils($this->_pdoAdapter);
	}



	public function testTableNamesWithoutPrefix () {
		$this->_testTableNamePrefixes('');
	}

	public function testTableNamesWithPrefix () {
		$this->_testTableNamePrefixes('prefix_');
	}



	private function _testTableNamePrefixes (string $prefix) {
		$tm = $this->_initStorage(['tableNamesPrefix' => $prefix]);

		$tableNamesHash = [];
		foreach ($this->_pdoAdapter->getAssocList("SHOW TABLES") as $e) {
			$tableNamesHash[current($e)] = true;
		}
		foreach (["{$prefix}task_types", "{$prefix}task_states", "{$prefix}tasks_queue"] as $tableName) {
			$this->assertArrayHasKey($tableName, $tableNamesHash);
		}

		$tm->deleteStorage();
	}



	public function testCreate () {
		$tm     = $this->_initStorage();
		$typeId = $this->_createType($tm);

		//Косячные
		$this->assertException(fn() => $tm->create(['type_id' => 0, 'progress' => 0]));
		$this->assertException(fn() => $tm->create(['type_id' => $typeId, 'state_id' => 0, 'progress' => 0]));
		$this->assertException(fn() => $tm->create(['type_id' => $typeId, 'progress' => 123]));

		//С параметрами по умолчанию
		$taskId       = $tm->create(['type_id' => $typeId]);
		$gotTask      = $tm->get($taskId);
		$expectedTask = (object)[
			'id'          => $taskId,
			'type_id'     => $typeId,
			'state_id'    => $tm::NEW,
			'progress'    => 0,
			'params'      => '',
			'log'         => '',
			'created_at'  => $gotTask->created_at,
			'updated_at'  => $gotTask->updated_at,
			'started_at'  => null,
			'finished_at' => null,
		];
		$this->assertEquals($expectedTask, $gotTask);
		$this->assertEquals($gotTask->created_at, $gotTask->updated_at);

		//С параметрами, определёнными вручную
		$taskId       = $tm->create(['type_id' => $typeId, 'state_id' => $tm::DONE, 'progress' => 100, 'params' => '{"a":1}', 'log' => 'done']);
		$gotTask      = $tm->get($taskId);
		$expectedTask = (object)[
			'id'          => $taskId,
			'type_id'     => $typeId,
			'state_id'    => $tm::DONE,
			'progress'    => 100,
			'params'      => '{"a":1}',
			'log'         => 'done',
			'created_at'  => $gotTask->created_at,
			'updated_at'  => $gotTask->updated_at,
			'started_at'  => null,
			'finished_at' => null,
		];
		$this->assertEquals($expectedTask, $gotTask);

		$tm->deleteStorage();
	}




	public function testEdit () {
		$tm     = $this->_initStorage();
		$typeId = $this->_createType($tm);
		$taskId = $tm->create(['type_id' => $typeId]);

		//Косячные
		$this->assertException(fn() => $tm->edit('string', ['progress' => 50]));
		$this->assertException(fn() => $tm->edit($taskId,  ['type_id' => 0, 'progress' => 0]));
		$this->assertException(fn() => $tm->edit($taskId,  ['type_id' => $typeId, 'state_id' => 0, 'progress' => 0]));
		$this->assertException(fn() => $tm->edit($taskId,  ['type_id' => $typeId, 'progress' => 123]));

		//Редактируем немножко данных
		$tm->edit($taskId, ['state_id' => $tm::RUNNING]);
		$gotTask      = $tm->get($taskId);
		$expectedTask = (object)[
			'id'          => $taskId,
			'type_id'     => $typeId,
			'state_id'    => $tm::RUNNING,
			'progress'    => 0,
			'params'      => '',
			'log'         => '',
			'created_at'  => $gotTask->created_at,
			'updated_at'  => $gotTask->updated_at,
			'started_at'  => null,
			'finished_at' => null,
		];
		$this->assertEquals($expectedTask, $gotTask);

		//Редактируем много данных
		$tm->edit($taskId, ['type_id' => $typeId, 'state_id' => $tm::DONE, 'progress' => 100, 'params' => '{"a":1}', 'log' => 'done']);
		$gotTask      = $tm->get($taskId);
		$expectedTask = (object)[
			'id'          => $taskId,
			'type_id'     => $typeId,
			'state_id'    => $tm::DONE,
			'progress'    => 100,
			'params'      => '{"a":1}',
			'log'         => 'done',
			'created_at'  => $gotTask->created_at,
			'updated_at'  => $gotTask->updated_at,
			'started_at'  => null,
			'finished_at' => null,
		];
		$this->assertEquals($expectedTask, $gotTask);

		//add_log
		$tm->edit($taskId, ['add_log' => 'doing']);
		$tm->edit($taskId, ['add_log' => 'job']);
		$this->assertEquals('donedoingjob', $tm->get($taskId)->log);

		$tm->deleteStorage();
	}



	public function testGetList () {
		$tm = $this->_initStorage();

		extract($this->_createListData($tm));

		$this->assertCount(8, $tm->getList());
		$this->assertCount(8, $tm->getList(['type_ids'  => $typeIdsList]));
		$this->assertCount(8, $tm->getList(['type_ids'  => false, 'state_id' => false, 'ids' => false]));
		$this->assertCount(4, $tm->getList(['type_ids'  => 2]));
		$this->assertCount(8, $tm->getList(['state_ids' => $stateIdsList]));
		$this->assertCount(4, $tm->getList(['state_ids' => $tm::RUNNING]));
		$this->assertCount(3, $tm->getList(['ids'       => '1,2,3']));
		$this->assertCount(1, $tm->getList(['ids'       => 1]));
		$this->assertCount(0, $tm->getList(['ids'       => 100]));

		$taskId = 1;
		foreach ($typeIdsList as $typeId) {
			foreach ($stateIdsList as $stateId) {
				for ($a = 1; $a <= 2; $a++) {
					$this->assertCount(1, $tm->getList(['type_ids' => $typeId, 'state_id' => $stateId, 'ids' => $taskId++]));
				}
			}
		}
	}



	public function testDeleteList () {
		$tm = $this->_initStorage();

		$this->_createListData($tm);

		$this->assertCount(8, $tm->getList());

		$tm->deleteList(['ids' => 1]);
		$this->assertCount(7, $tm->getList());

		$tm->deleteList(['ids' => [2,3]]);
		$this->assertCount(5, $tm->getList());

		$tm->deleteList();
		$this->assertCount(0, $tm->getList());
	}



	public function testRunWithFunction () {
		$this->_testRun([
			'code'             => 'function',
			'callback_type_id' => TasksManager::FUNCTION,
			'callback_method'  => '\S5\TasksManager\tasksCallbackFunction',
		]);
	}

	public function testRunWithClassMethod () {
		$this->_testRun([
			'code'             => 'class_method',
			'callback_type_id' => TasksManager::CLASS_METHOD,
			'callback_source'  => '\S5\TasksManager\TasksCallbackStaticClass',
			'callback_method'  => 'run',
		]);
	}

	public function testRunWithHashMethod () {
		$this->_testRun([
			'code'             => 'hash_method',
			'callback_type_id' => TasksManager::HASH_METHOD,
			'callback_source'  => 'object',
			'callback_method'  => 'run',
		]);
	}



	private function _testRun (array $typeData) {
		$tm = $this->_initStorage();

		$typeId = $this->_createType($tm, [
			'code'             => 'function',
			'callback_type_id' => $tm::FUNCTION,
			'callback_method'  => '\S5\TasksManager\tasksCallbackFunction',
		]);

		$nowTs     = time();
		$nowString = date('Y-m-d H:i:s', $nowTs);

		$mockTask = (object)[
			'id'                => 1,
			'type_id'           => $typeId,
			'state_id'          => $tm::NEW,
			'progress'          => 0,
			'params'            => '',
			'log'               => '',
			'created_at'        => $nowString,
			'updated_at'        => $nowString,
			'started_at'        => null,
			'finished_at'       => null,
			'_callback_type_id' => $typeId,
			'_callback_source'  => null,
			'_callback_method'  => '\S5\TasksManager\tasksCallbackFunction',
		];

		$dbUpdatesAmount = 0;
		$logStringsList  = [];

		$dbMock = function (string $type, array $params = []) use ($tm, &$mockTask, &$dbUpdatesAmount, &$logStringsList) {
			$value   = array_shift($params);
			$matches = [];
			if ($type == 'escape') {
				return $value;
			}
			//Активных задач нет
			if ($type == 'getObject' and preg_match('/state[^\d]+'.$tm::RUNNING.'/ui', $value)) {
				return null;
			}
			//Это - задача на запуск
			if ($type == 'getObject' and preg_match('/state[^\d]+'.$tm::NEW.'/ui', $value)) {
				return $mockTask;
			}
			//Установка статуса задачи в "работает"
			if ($type == 'query' and preg_match('/state[^\d]+'.$tm::RUNNING.'/ui', $value)) {
				$mockTask->state = $tm::RUNNING;
			}
			//Обновляем прогресс и лог
			if ($type == 'query' and preg_match("/SET.+?progress.+?(\d+).+?log.+?CONCAT\\(log, '([^']+)'\\)/ui", $value, $matches)) {
				$mockTask->progress = $matches[1];
				$mockTask->log     .= $matches[2];
				$dbUpdatesAmount++;
				$logStringsList[] = $matches[2];
				if ($dbUpdatesAmount == 5 and preg_match("/SET.+?state.+?".$tm::DONE."/ui", $value)) {
					$mockTask->state = $tm::DONE;
				}
			}
		};

		//Выполняем задачу в 10 шагов. Каждый шаг - 0,5 сек.
		//Соответственно, дополнение лога будет происходить каждый второй шаг.
		$isFirstTime = true;
		$timeGetter = function () use (&$nowTs, &$isFirstTime) {
			if ($isFirstTime) {
				$isFirstTime = false;
				return $nowTs;
			}
			$nowTs += 0.5;
			return floor($nowTs);
		};

		$tm->run([
			'callbacks_hash' => ['object' => new TasksCallbackClass()],
			'db_adapter'     => new CallbackAdapter($dbMock),
			'time_getter'    => $timeGetter,
		]);

		$expectedDbUpdatesAmount = 5;
		$expectedLogStringsList  = [
			'10;20;',
			'30;40;',
			'50;60;',
			'70;80;',
			'90;100;',
		];

		$this->assertEquals($expectedDbUpdatesAmount, $dbUpdatesAmount);
		$this->assertEquals(100,                      $mockTask->progress);
		$this->assertEquals($expectedLogStringsList,  $logStringsList);
		$this->assertEquals($tm::DONE,                $mockTask->state);
	}



	private function _initStorage (array $params = []): TasksManager {
		$tm = new TasksManager(
			$params + [
			'dbAdapter' => $this->_pdoAdapter,
			'dbUtils'   => $this->_dbUtils,
		]);
		$tm->deleteStorage();
		$tm->initStorage();
		return $tm;
	}



	private function _createType (TasksManager $tm, array $params = []): int {
		$p = $params + [
			'code'             => 'test',
			'callback_type_id' => $tm::FUNCTION,
			'callback_method'  => 'func'
		];
		return $tm->createType($p);
	}



	private function _createListData (TasksManager $tm): array {
		$typeIdsList = [];
		$typeIdsList['test'] = $this->_createType($tm);
		$typeIdsList['dead'] = $this->_createType($tm, ['code' => 'dead']);

		$stateIdsList = [$tm::NEW, $tm::RUNNING];

		foreach ($typeIdsList as $typeId) {
			foreach ($stateIdsList as $stateId) {
				for ($a = 1; $a <= 2; $a++) {
					$taskId = $tm->create(['type_id' => $typeId, 'state_id' => $stateId, 'log' => $a]);
				}
			}
		}

		return compact('typeIdsList', 'stateIdsList');
	}
}



function tasksCallbackFunction (\Closure $taskUpdater, array $params) {
	for ($progress = 10; $progress <= 100; $progress+=10) {
		$taskUpdater($progress, "$progress;");
	}
}



class TasksCallbackStaticClass {
	public static function run (\Closure $taskUpdater, array $params) {
		tasksCallbackFunction($taskUpdater, $params);
	}
}



class TasksCallbackClass {
	public function run (\Closure $taskUpdater, array $params) {
		tasksCallbackFunction($taskUpdater, $params);
	}
}
