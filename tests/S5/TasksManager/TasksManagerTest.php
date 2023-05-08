<?
namespace S5\TasksManager;

use S5\IO\File;
use S5\Db\DbUtils;
use S5\Progress;
use S5\Db\Adapters\PdoAdapter;
use S5\Db\Adapters\CallbackAdapter;



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
			'_type_code'  => 'test',
			'_type_name'  => 'Тест',
			'_state_code' => 'new',
			'_state_name' => 'Новая',
			'_progress'   => new Progress(['start_time' => $gotTask->started_at]),
			'_logs'       => [],
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
			'_type_code'  => 'test',
			'_type_name'  => 'Тест',
			'_state_code' => 'done',
			'_state_name' => 'Готово',
			'_progress'   => new Progress(['start_time' => $gotTask->started_at, 'progress' => 100]),
			'_logs'       => [],
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
			'_type_code'  => 'test',
			'_type_name'  => 'Тест',
			'_state_code' => 'running',
			'_state_name' => 'В работе',
			'_progress'   => new Progress(['start_time' => $gotTask->started_at]),
			'_logs'       => [],
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
			'_type_code'  => 'test',
			'_type_name'  => 'Тест',
			'_state_code' => 'done',
			'_state_name' => 'Готово',
			'_progress'   => new Progress(['start_time' => $gotTask->started_at, 'progress' => 100]),
			'_logs'       => [],
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

		$testData = [
			[8, []],
			[8, ['type_ids'  => $typeIdsList]],
			[8, ['type_ids'  => false, 'state_id' => false, 'ids' => false]],
			[4, ['type_ids'  => 2]],
			[8, ['state_ids' => $stateIdsList]],
			[4, ['state_ids' => $tm::RUNNING]],
			[3, ['ids'       => '1,2,3']],
			[1, ['ids'       => 1]],
			[0, ['ids'       => 100]],
		];
		foreach ($testData as $e) {
			$expectedAmount = $e[0];
			$params         = $e[1];
			$this->assertEquals($expectedAmount, $tm->count($params));
			$this->assertCount($expectedAmount,  $tm->getList($params));
		}

		$taskId = 1;
		foreach ($typeIdsList as $typeId) {
			foreach ($stateIdsList as $stateId) {
				for ($a = 1; $a <= 2; $a++) {
					$params = ['type_ids' => $typeId, 'state_id' => $stateId, 'ids' => $taskId++];
					$this->assertEquals(1, $tm->count($params));
					$this->assertCount(1,  $tm->getList($params));
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

		$createTimeString = '2000-01-01 00:00:00';
		$runTimeString    = '2000-01-02 00:00:00';
		$ts               = strtotime($createTimeString);
		$runTimeGetter    = function () use (&$ts) { return $ts; };
		$runTimeInc       = function () use (&$ts) { $ts++; };


		$tm = $this->_initStorage([
			'progressUpdateDelay' => 0,
			'runTimeGetter'       => $runTimeGetter,
		]);


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
		$taskIdsList = [];

		$taskIdsList[] = $tm->create([
			'type_id'  => $functionTypeId,
			'state_id' => $tm::DONE,
		]);
		$taskIdsList[] = $tm->create([
			'type_id' => $functionTypeId,
		]);
		$taskIdsList[] = $tm->create([
			'type_id' => $classTypeId,
		]);
		$taskIdsList[] = $tm->create([
			'type_id' => $hashTypeId,
		]);

		//Проверяем, что начальные данные задач правильные
		foreach ($taskIdsList as $taskId) {
			$task = $tm->get($taskId);
			$this->assertEquals($createTimeString, $task->created_at);
			$this->assertEquals($createTimeString, $task->updated_at);
			$this->assertNull($task->started_at);
			$this->assertNull($task->finished_at);
			if ($taskId > 1) {
				$this->assertEquals($tm::NEW, $task->state_id);
				$this->assertEquals(0,        $task->progress);
			}
		}

		//Запускаем выполнение всех задач со своим time_getter,
		//типа через сутки после создания
		$ts = strtotime($runTimeString);

		TasksProcessor::$tm                   = $tm;
		TasksProcessor::$runTimeGetter        = $runTimeGetter;
		TasksProcessor::$runTimeInc           = $runTimeInc;
		TasksProcessor::$taskId               = 2;
		TasksProcessor::$tasksRuntimeDataHash = [];

		$tm->run([
			'callbacks_hash' => [
				'object' => new TasksCallbackClass(),
			],
		]);

		//Проверяем, каков был ход выполнения задач.
		//Внутри TasksProcessor::run() делать $this->assert*() толку нет:
		//исключения PHPUnit не будут выводиться на экран, а будут писаться в логи задачи.
		$this->assertCount(3, TasksProcessor::$tasksRuntimeDataHash);
		foreach (TasksProcessor::$tasksRuntimeDataHash as $taskId => $taskDataList) {
			foreach ($taskDataList as $recordIndex => $taskData) {
				$this->assertEquals($taskData['expected'], $taskData['got'], "Task #$taskId, record $recordIndex");
			}
		}

		//Проверяем, что вышло
		$tasksList = $tm->getList();

		$expectedLogStringsList = [];
		for ($a = 10; $a <= 100; $a+=10) {
			$expectedLogStringsList[] = "$a;";
		}

		//В итоге в БД должно быть 4 задачи с прогрессом 100, в статусе DONE.
		//У наших выполненных - по 5 записей в логе.
		$tenSeconds = 0;
		foreach ($tasksList as $task) {
			$this->assertEquals($createTimeString, $task->created_at);
			$this->assertEquals($tm::DONE,         $task->state_id, "Expected state: done, got $task->_state_code");
			if ($task->id > 1) {
				$this->assertEquals(100, $task->progress);
				$this->assertEquals($expectedLogStringsList, $tm->getLogTextsList(['task_ids' => $task->id]));
				$startedAtSecondsString  = str_pad($tenSeconds, 2, '0', STR_PAD_RIGHT);
				$finishedAtSecondsString = $tenSeconds.'9';
				$this->assertEquals("2000-01-02 00:00:$startedAtSecondsString",  $task->started_at,  "Task #$task->id");
				$this->assertEquals("2000-01-02 00:00:$finishedAtSecondsString", $task->updated_at,  "Task #$task->id");
				$this->assertEquals("2000-01-02 00:00:$finishedAtSecondsString", $task->finished_at, "Task #$task->id");
				$tenSeconds++;
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



	public function testLockedFileRun () {
		$tm     = $this->_initStorage();
		$typeId = $this->_createType($tm);
		$taskId = $tm->create(['type_id' => $typeId]);

		$lockFile = new File(__DIR__.'/files/.lock');

		$lockFile->lock();
		$this->assertEquals([], $tm->run());

		$lockFile->unlock();
		$this->assertEquals([1], $tm->run());
	}



	private function _initStorage (array $params = []): TasksManager {
		$tm = new TasksManager(
			$params + [
				'dbAdapter'           => $this->_dbAdapter,
				'dbUtils'             => $this->_dbUtils,
				'progressUpdateDelay' => 2,
				'lockFilePath'        => __DIR__.'/files/.lock',
			]
		);
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



class TasksProcessor {
	public static TasksManager $tm;
	public static \Closure     $runTimeGetter;
	public static \Closure     $runTimeInc;
	public static int          $taskId;
	public static array        $tasksRuntimeDataHash;

	public static function run (\Closure $taskUpdater, array $params) {
		for ($progress = 10; $progress <= 100; $progress += 10) {
			$taskUpdater($progress, "$progress;");
			$task = static::$tm->get(static::$taskId);
			static::$tasksRuntimeDataHash[static::$taskId][] = [
				'expected' => [
					'updated_at'       => date('Y-m-d H:i:s', (static::$runTimeGetter)()),
					'progress'         => $progress,
					'elapsed_percents' => $progress,
				],
				'got' => [
					'updated_at'       => $task->updated_at,
					'progress'         => $task->progress,
					'elapsed_percents' => $task->_progress->getElapsedPercents(),
				],
			];
			(static::$runTimeInc)();
		}
		static::$taskId++;
	}
}



function tasksCallbackFunction (\Closure $taskUpdater, array $params) {
	TasksProcessor::run($taskUpdater, $params);
}

class TasksCallbackStaticClass {
	public static function run (\Closure $taskUpdater, array $params) {
		TasksProcessor::run($taskUpdater, $params);
	}
}

class TasksCallbackClass {
	public function run (\Closure $taskUpdater, array $params) {
		TasksProcessor::run($taskUpdater, $params);
	}
}



function tasksExceptionFunction (\Closure $taskUpdater, array $params) {
	throw new \Exception('bug');
}
