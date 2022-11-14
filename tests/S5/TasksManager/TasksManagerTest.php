<?
namespace S5\TasksManager;
use S5\Db\Adapters\PdoAdapter;
use S5\Db\Adapters\CallbackAdapter;
use S5\Db\DbUtils;
use S5\Progress;



class TasksManagerTest extends \S5\TestCase {
	private PdoAdapter $_dbAdapter;
	private DbUtils    $_dbUtils;



	public function __construct (...$params) {
		parent::__construct(...$params);

		$d = $GLOBALS['phpUnitParams']['db'];

		$pdo              = new \PDO("mysql:dbname=$d[name];host=$d[host];charset=UTF8", $d['login'], $d['password']);
		$this->_dbAdapter = new PdoAdapter($pdo);
		$this->_dbUtils   = new DbUtils($this->_dbAdapter);
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
		foreach ($this->_dbAdapter->getAssocList("SHOW TABLES") as $e) {
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
			'created_at'  => $gotTask->created_at,
			'updated_at'  => $gotTask->updated_at,
			'started_at'  => null,
			'finished_at' => null,
			'_type_name'  => 'Тест',
			'_state_name' => 'Новая',
			'_progress'   => new Progress(['start_time' => $gotTask->started_at]),
		];
		$this->assertEquals($expectedTask, $gotTask);
		$this->assertEquals($gotTask->created_at, $gotTask->updated_at);

		//С параметрами, определёнными вручную
		$paramsString = serialize(['a' => 1]);
		$taskId       = $tm->create(['type_id' => $typeId, 'state_id' => $tm::DONE, 'progress' => 100, 'params' => $paramsString]);
		$gotTask      = $tm->get($taskId);
		$expectedTask = (object)[
			'id'          => $taskId,
			'type_id'     => $typeId,
			'state_id'    => $tm::DONE,
			'progress'    => 100,
			'params'      => $paramsString,
			'created_at'  => $gotTask->created_at,
			'updated_at'  => $gotTask->updated_at,
			'started_at'  => null,
			'finished_at' => null,
			'_type_name'  => 'Тест',
			'_state_name' => 'Готово',
			'_progress'   => new Progress(['start_time' => $gotTask->started_at, 'progress' => 100]),
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
			'created_at'  => $gotTask->created_at,
			'updated_at'  => $gotTask->updated_at,
			'started_at'  => null,
			'finished_at' => null,
			'_type_name'  => 'Тест',
			'_state_name' => 'В работе',
			'_progress'   => new Progress(['start_time' => $gotTask->started_at]),
		];
		$this->assertEquals($expectedTask, $gotTask);

		//Редактируем много данных
		$paramsString = serialize(['a' => 1]);
		$tm->edit($taskId, ['type_id' => $typeId, 'state_id' => $tm::DONE, 'progress' => 100, 'params' => $paramsString]);
		$gotTask      = $tm->get($taskId);
		$expectedTask = (object)[
			'id'          => $taskId,
			'type_id'     => $typeId,
			'state_id'    => $tm::DONE,
			'progress'    => 100,
			'params'      => $paramsString,
			'created_at'  => $gotTask->created_at,
			'updated_at'  => $gotTask->updated_at,
			'started_at'  => null,
			'finished_at' => null,
			'_type_name'  => 'Тест',
			'_state_name' => 'Готово',
			'_progress'   => new Progress(['start_time' => $gotTask->started_at, 'progress' => 100]),
		];
		$this->assertEquals($expectedTask, $gotTask);

		//Добавляем логи
		$tm->createLogsList($taskId, ['done']);
		$tm->createLogsList($taskId, ['doing', 'job']);
		$this->assertEquals(['done', 'doing', 'job'], $tm->getLogTextsList(['task_id' => $taskId]));
		$this->assertEquals(['doing', 'job'],         $tm->getLogTextsList(['task_id' => $taskId, 'limit' => '1, 2']));

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



	public function testNormalRun () {
		//Будем выполнять каждую задачу в 10 шагов. Каждый шаг - 1 сек.
		//Обновление прогресса - каждые 2 секунды.
		//Соответственно, обновление прогресса и лога будет происходить каждый второй шаг.
		$progressUpdatesAmount = 0;
		$dbAdapter = new CallbackAdapter(function ($type, $p) use (&$progressUpdatesAmount) {
			$r = $this->_dbAdapter->$type(...$p);
			if ($type == 'query' and preg_match('/SET\s+progress\s*=\s*\d+/sui', $p[0])) {
				$progressUpdatesAmount++;
			}
			return $r;
		});

		$nowTs         = time();
		$runTimeGetter = function () use (&$nowTs) {
			return $nowTs++;
		};


		$tm = $this->_initStorage(compact('dbAdapter', 'runTimeGetter'));


		$functionTypeId = $this->_createType($tm, [
			'code'             => 'function',
			'callback_type_id' => $tm::FUNCTION,
			'callback_method'  => '\S5\TasksManager\tasksCallbackFunction',
		]);
		$classTypeId = $this->_createType($tm, [
			'code'             => 'class_method',
			'callback_type_id' => $tm::CLASS_METHOD,
			'callback_source'  => '\S5\TasksManager\TasksCallbackStaticClass',
			'callback_method'  => 'run',
		]);
		$hashTypeId = $this->_createType($tm, [
			'code'             => 'hash_method',
			'callback_type_id' => $tm::HASH_METHOD,
			'callback_source'  => 'object',
			'callback_method'  => 'run',
		]);


		//Сформируем очередь:
		//4.) задача с методом объекта
		//3.) задача со статическим методом
		//2.) задача с функцией
		//1.) выполненная задача
		//
		//Выполняться они будут в порядке 2, 3, 4.
		//Задача №1, самая старая, будет пропущена.
		$doneTask = $tm->create([
			'type_id'  => $functionTypeId,
			'state_id' => $tm::DONE,
		]);
		$functionTask = $tm->create([
			'type_id' => $functionTypeId,
		]);
		$classTask = $tm->create([
			'type_id' => $classTypeId,
		]);
		$hashTask = $tm->create([
			'type_id' => $hashTypeId,
		]);

		//Запускаем выполнение всех задач со своим time_getter
		$tm->run([
			'callbacks_hash' => [
				'object' => new TasksCallbackClass(),
			],
		]);

		//Проверяем, что вышло
		$expectedLogStringsList = [];
		for ($a = 10; $a <= 100; $a+=10) {
			$expectedLogStringsList[] = "$a;";
		}

		$tasksList = $tm->getList();

		//Каждая из трёх задач обновляется 5 раз - итого 15
		$this->assertEquals(15, $progressUpdatesAmount);

		//В итоге в БД должно быть 4 задачи с прогрессом 100, в статусе DONE.
		//У наших выполненных - по 5 записей в логе.
		foreach ($tasksList as $task) {
			$this->assertEquals($tm::DONE, $task->state_id);
			if ($task->id > 1) {
				$this->assertEquals($expectedLogStringsList, $tm->getLogTextsList(['task_ids' => $task->id]));
			}
		}
	}



	public function testRunWithException () {
		$tm     = $this->_initStorage();
		$typeId = $this->_createType($tm, [
			'code'             => 'function',
			'callback_type_id' => $tm::FUNCTION,
			'callback_method'  => '\S5\TasksManager\tasksExceptionFunction',
		]);
		$taskId = $tm->create(['type_id' => $typeId]);

		$tm->run();

		$this->assertEquals(['bug'], $tm->getLogTextsList());
	}



	private function _initStorage (array $params = []): TasksManager {
		$tm = new TasksManager(
			$params + [
			'dbAdapter'           => $this->_dbAdapter,
			'dbUtils'             => $this->_dbUtils,
			'progressUpdateDelay' => 2,
			'lockFilePath'        => __DIR__.'/files/.lock',
		]);
		$tm->deleteStorage();
		$tm->initStorage();
		return $tm;
	}



	private function _createType (TasksManager $tm, array $params = []): int {
		$p = $params + [
			'code'             => 'test',
			'name'             => 'Тест',
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
					$taskId = $tm->create(['type_id' => $typeId, 'state_id' => $stateId]);
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



function tasksExceptionFunction (\Closure $taskUpdater, array $params) {
	throw new \Exception('bug');
}
