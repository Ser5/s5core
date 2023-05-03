<?
namespace S5\TasksManager;
use S5\Db\Adapters\IAdapter;
use S5\Db\DbUtils;
use S5\IO\File;
use Respect\Validation\Validator as v;
use Respect\Validation\Validatable;



class TasksManager {
	const FUNCTION     = 1;
	const CLASS_METHOD = 2;
	const HASH_METHOD  = 3;

	const NEW     = 1;
	const RUNNING = 2;
	const PAUSED  = 3;
	const ERROR   = 4;
	const DONE    = 5;

	protected IAdapter $dbAdapter;
	protected DbUtils  $dbUtils;
	protected int      $progressUpdateDelay;
	protected int      $oldTasksKeepPeriod;
	protected File     $lockFile;
	protected mixed    $runTimeGetter;

	protected Validatable $createValidator;
	protected Validatable $editValidator;
	protected Validatable $listValidator;
	protected Validatable $countValidator;
	protected Validatable $getLogsListValidator;
	protected Validatable $createTypeValidator;
	protected Validatable $editTypeValidator;

	protected string $callbackTypes = 'callback_types';
	protected string $taskTypes     = 'task_types';
	protected string $taskStates    = 'task_states';
	protected string $tasksQueue    = 'tasks_queue';
	protected string $taskLogs      = 'task_logs';


	public function __construct (array $params) {
		$p = $params + [
			'dbAdapter'           => false,
			'dbUtils'             => false,
			'tableNamesPrefix'    => '',
			'progressUpdateDelay' => 1,
			'oldTasksKeepPeriod'  => 86400*30,
			'lockFilePath'        => false,
			'runTimeGetter'       => fn()=>time(),
		];

		if (!is_a($p['dbAdapter'], IAdapter::class)) {
			throw new \InvalidArgumentException("dbAdapter не является объектом \S5\Db\Adapters\IAdapter");
		}
		if (!is_a($p['dbUtils'], DbUtils::class)) {
			throw new \InvalidArgumentException("DbUtils не является объектом \S5\Db\DbUtils");
		}
		if (!is_string($p['lockFilePath'])) {
			throw new \InvalidArgumentException("lockFilePath - не передан путь к lock-файлу");
		}
		$lockDirPath = dirname($p['lockFilePath']);
		if (!is_dir($lockDirPath) and !is_writable($lockDirPath)) {
			throw new \InvalidArgumentException("Директория lock-файла недоступна для записси: $lockDirPath");
		}

		$this->dbAdapter           = $p['dbAdapter'];
		$this->dbUtils             = $p['dbUtils'];
		$this->progressUpdateDelay = $p['progressUpdateDelay'];
		$this->oldTasksKeepPeriod  = $p['oldTasksKeepPeriod'];
		$this->lockFile            = new File($p['lockFilePath']);
		$this->runTimeGetter       = $p['runTimeGetter'];

		$this->initValidators();
		$this->initTableNamePrefixes($p['tableNamesPrefix']);
	}



	protected function initValidators () {
		$min0     = fn() => v::intVal()->min(0);
		$min1     = fn() => v::intVal()->min(1);
		$string   = fn() => v::stringVal();
		$string1  = fn() => v::stringVal()->length(1);
		$ids      = fn() => v::anyOf(v::falseVal(), $min1(), v::each($min1()), $string());
		$progress = fn() => v::intVal()->min(0)->max(100);

		$this->createValidator = (new v())
			->key('type_id',  $min1())
			->key('state_id', $min1(),     false)
			->key('progress', $progress(), false)
			->key('params',   $string(),   false)
		;

		$this->editValidator = (new v())
			->key('type_id',  $min1(),     false)
			->key('state_id', $min1(),     false)
			->key('progress', $progress(), false)
			->key('params',   $string(),   false)
		;

		$this->listValidator = (new v())
			->key('ids',       $ids(),    false)
			->key('type_ids',  $ids(),    false)
			->key('state_ids', $ids(),    false)
			->key('order_by',  $string(), false)
			->key('limit',     v::anyOf(v::stringVal(), v::arrayVal()), false)
		;

		$this->countValidator = (new v())
			->key('type_ids',  $ids(), false)
			->key('state_ids', $ids(), false)
		;

		$this->getLogsListValidator = (new v())
			->key('ids',      $ids(), false)
			->key('type_ids', $ids(), false)
			->key('limit',    v::anyOf(v::falseVal(), v::intVal(), v::stringVal()), false)
		;

		$this->createTypeValidator = (new v())
			->key('code',             $string1())
			->key('name',             $string(), false)
			->key('description',      $string(), false)
			->key('callback_type_id', $min1())
			->key('callback_source',  v::anyOf(v::falseVal(), $string1()), false)
			->key('callback_method',  $string1())
			->key('sort',             $min0(), false)
		;

		$this->editTypeValidator = (new v())
			->key('code',        $string1(), false)
			->key('name',        $string(),  false)
			->key('description', $string(),  false)
			->key('sort',        $min0(),    false)
		;
	}



	protected function initTableNamePrefixes (string $tableNamesPrefix) {
		if ($tableNamesPrefix) {
			foreach (['callbackTypes', 'taskTypes','taskStates','tasksQueue', 'taskLogs'] as $tableName) {
				$this->$tableName = $tableNamesPrefix . $this->$tableName;
			}
		}
	}



	/**
	 * Добавление задания в очередь.
	 *
	 * @param array{
	 *    type_id:   int,
	 *    state_id?: int,
	 *    progress?: int,
	 *    params?:   string,
	 * } $data
	 * @return int
	 */
	public function create (array $data): int {
		$this->assert($data, $this->createValidator);
		$data += [
			'state_id' => static::NEW,
			'progress' => 0,
			'params'   => '',
		];
		$this->dbAdapter->query($this->dbUtils->getInsert($this->tasksQueue, $data));
		return $this->dbAdapter->getInsertId();
	}



	/**
	 * Редактирование задания.
	 *
	 * @param array{
	 *    type_id?:  int,
	 *    state_id?: int,
	 *    progress?: int,
	 *    params?:   string,
	 * } $data
	 */
	public function edit (int $taskId, array $data) {
		if ($data) {
			$this->assert($data, $this->editValidator);
			$this->dbAdapter->query($this->dbUtils->getUpdate($this->tasksQueue, 'id', "$taskId", $data));
		}
	}



	public function get (int $id): ?object {
		return $this->getList(['ids' => $id])[0] ?? null;
	}



	/**
	 * Получение списка заданий.
	 *
	 * @param array{
	 *    ids?:       false|int|array<int>|string,
	 *    type_ids?:  false|int|array<int>|string,
	 *    state_ids?: false|int|array<int>|string,
	 *    order_by?:  false|string,
	 *    limit?:     false|int|array<int>|string,
	 * } $params
	 */
	public function getList (array $params = []): array {
		$whereString = $this->getListWhereString($params, true);

		$p = $params + [
			'order_by' => 'q.id',
			'limit'    => false,
		];

		$limitString = !$p['limit'] ? '' : 'LIMIT ' . $this->dbUtils->getLimitString($p['limit']);

		//Основные данные
		$query =
			"SELECT
				q.*,
				t.code AS _type_code,  t.name AS _type_name,
				s.code AS _state_code, s.name AS _state_name
			FROM       $this->tasksQueue q
			INNER JOIN $this->taskTypes  t ON t.id = q.type_id
			INNER JOIN $this->taskStates s ON s.id = q.state_id
			WHERE $whereString 1
			ORDER BY $p[order_by]
			$limitString
			";

		$list = $this->dbAdapter->getObjectsList($query);

		//Логи
		$logsList = $this->getLogsList([
			'task_ids' => array_map(fn($t)=>$t->id, $list),
		]);
		$logsHash = [];
		foreach ($logsList as $e) {
			$logsHash[$e->task_id][] = $e;
		}

		//Обрабатываем данные и цепляем дополнительные данные
		foreach ($list as $task) {
			//Приведение к int
			$task->id        = (int)$task->id;
			$task->type_id   = (int)$task->type_id;
			$task->state_id  = (int)$task->state_id;
			$task->progress  = (int)$task->progress;
			//Прогресс
			$task->_progress = new \S5\Progress(['startTime' => strtotime($task->started_at), 'progress' => $task->progress]);
			//Логи
			$task->_logs = $logsHash[$task->id] ?? [];
		}

		return $list;
	}



	/**
	 * Подсчёт заданий.
	 *
	 * @param array{
	 *    type_ids?:  false|int|array<int>|string,
	 *    state_ids?: false|int|array<int>|string,
	 * } $params
	 */
	public function count (array $params = []): int {
		$this->assert($params, $this->countValidator);

		$whereString = $this->getListWhereString($params, true);

		$query =
			"SELECT COUNT(*) AS count
			FROM       $this->tasksQueue q
			INNER JOIN $this->taskTypes  t ON t.id = q.type_id
			INNER JOIN $this->taskStates s ON s.id = q.state_id
			WHERE $whereString 1
			";

		return intval($this->dbAdapter->getObject($query)->count);
	}



	/**
	 * Удаление списка заданий.
	 *
	 * @param array{
	 *    ids?:       false|int|array<int>|string,
	 *    type_ids?:  false|int|array<int>|string,
	 *    state_ids?: false|int|array<int>|string,
	 * } $params
	 * @return int
	 */
	public function deleteList (array $params = []): int {
		$whereString = $this->getListWhereString($params, false);

		$query = "DELETE FROM $this->tasksQueue WHERE $whereString 1";
		$this->dbAdapter->query($query);

		return $this->dbAdapter->getAffectedRows();
	}



	protected function getListWhereString (array $params, bool $isUseAlias): string {
		$this->assert($params, $this->listValidator);
		$p = $params + [
			'ids'       => false,
			'type_ids'  => false,
			'state_ids' => false,
		];

		$whereString = '';
		$t = ($isUseAlias ? 'q.' : '');
		if ($p['ids']) {
			$whereString .= "{$t}id IN (" . $this->dbUtils->getIntsString($p['ids']) . ') AND ';
		}
		if ($p['type_ids']) {
			$whereString .= "{$t}type_id IN (" . $this->dbUtils->getIntsString($p['type_ids']) . ') AND ';
		}
		if ($p['state_ids']) {
			$whereString .= "{$t}state_id IN (" . $this->dbUtils->getIntsString($p['state_ids']) . ') AND ';
		}

		return $whereString;
	}



	public function createLogsList (int $taskId, array $logStringsList) {
		if ($logStringsList) {
			$query = "INSERT INTO $this->taskLogs (task_id, text) VALUES\n";
			foreach ($logStringsList as $logString) {
				$query .= ("($taskId, ". $this->dbAdapter->quote($logString) ."),\n");
			}
			$query = mb_substr($query, 0, -2, 'UTF-8');
			$this->dbAdapter->query($query);
		}
	}



	/**
	 * Добыча списка логов.
	 *
	 * @param array{
	 *    ids?:      false|int|string|array,
	 *    task_ids?: false|int|string|array,
	 *    limit?:    false|int|string|array,
	 * } $params
	 */
	public function getLogsList (array $params = []): array {
		$p = $params + [
			'ids'      => false,
			'task_ids' => false,
			'limit'    => false,
		];
		$this->assert($p, $this->getLogsListValidator);

		//WHERE
		$whereString = '';
		if ($p['ids']) {
			$whereString .= ('id IN (' . $this->dbUtils->getIntsString($p['ids']) . ') AND ');
		}
		if ($p['task_ids']) {
			$whereString .= ('task_id IN (' . $this->dbUtils->getIntsString($p['task_ids']) . ') AND ');
		}

		//LIMIT
		$limitString = (!$p['limit'] ? '' : 'LIMIT '.$this->dbUtils->getLimitString($p['limit']));

		$query =
			"SELECT *
			FROM $this->taskLogs
			WHERE $whereString 1
			ORDER BY task_id, id
			$limitString
			";

		$logsList = $this->dbAdapter->getObjectsList($query);

		return $logsList;
	}



	public function getLogTextsList (array $params = []): array {
		return array_map(fn($l)=>$l->id, $this->getLogsList(...$params));
	}



	/**
	 * Запуск задач - если очередь свободна и есть задачи для запуска.
	 *
	 * @param array{
	 *    callbacks_hash?: array|false,
	 * } $params
	 * @return array ID обработанных задач
	 */
	public function run (array $params = []): array {
		$p = $params + [
			'callbacks_hash' => [],
		];

		$tasksList = [];

		if (
			$this->lockFile->lock()       and
			!$this->isRunningTaskExists() and
			$tasksList = $this->getNewTasksList()
		) {
			foreach ($tasksList as $task) {
				$this->runOneTask($p['callbacks_hash'], $task);
			}
			$this->lockFile->unlock();
		}

		return array_map(fn($t)=>$t->id, $tasksList);
	}



	protected function isRunningTaskExists (): bool {
		$query = "SELECT id FROM $this->tasksQueue WHERE state_id = ".static::RUNNING;
		return boolval($this->dbAdapter->getObject($query));
	}



	protected function getNewTasksList (): array {
		$query =
			"SELECT
				queue.*,
				types.callback_type_id AS _callback_type_id,
				types.callback_source  AS _callback_source,
				types.callback_method  AS _callback_method
			FROM $this->tasksQueue      AS queue
			INNER JOIN $this->taskTypes AS types ON types.id = queue.type_id
			WHERE state_id = ".static::NEW.".
			ORDER BY id
			";
		return $this->dbAdapter->getObjectsList($query);
	}



	protected function runOneTask (array $callbacksHash, object $task) {
		$dbAdapter  = $this->dbAdapter;
		$tasksQueue = $this->tasksQueue;
		$timeGetter = $this->runTimeGetter;

		//Берём новую задачу в работу - ставим ей статус "Выполняется"
		$nowString = date('Y-m-d H:i:s', $timeGetter());
		$query     = "UPDATE $tasksQueue SET state_id = ".static::RUNNING.", progress = 0, started_at = '$nowString' WHERE id = $task->id";
		$dbAdapter->query($query);

		$lastUpdateTs   = $timeGetter();
		$logStringsList = [];

		$taskUpdater = function ($progress, string $addLogString, ?bool $isDone = null) use ($timeGetter, $task, &$lastUpdateTs, &$logStringsList) {
			$nowTs            = $timeGetter();
			$progress         = min(100, (int)round($progress));
			$logStringsList[] = $addLogString;
			if (is_null($isDone)) {
				$isDone = ($progress == 100);
			} elseif ($isDone === true) {
				$progress = 100;
			}
			if ($isDone or $nowTs >= $lastUpdateTs + $this->progressUpdateDelay) {
				$this->setProgressAndDoneState($task->id, $progress, $isDone);
				$this->createLogsList($task->id, $logStringsList);
				$logStringsList = [];
				$lastUpdateTs   = $nowTs;
			}
		};

		switch ($task->_callback_type_id) {
			case static::FUNCTION:
				$callback = $task->_callback_method;
			break;
			case static::CLASS_METHOD:
				$callback = [$task->_callback_source, $task->_callback_method];
			break;
			case static::HASH_METHOD:
				if (!$callbacksHash) {
					throw new \InvalidArgumentException("Не передан callbacks_hash");
				}
				$callback = [$callbacksHash[$task->_callback_source], $task->_callback_method];
			break;
			default:
				throw new \InvalidArgumentException("Неизвестный тип функции обратного вызова, выполняющей обработку задачи: [$task->_callback_type_id]");
		}

		$taskParamsList = $task->params ? [unserialize($task->params)] : [];
		try {
			call_user_func($callback, $taskUpdater, $taskParamsList);
		} catch (\Throwable $e) {
			$query = "UPDATE $this->tasksQueue SET state_id = ".static::ERROR." WHERE id = $task->id";
			$dbAdapter->query($query);
			$this->createLogsList($task->id, [$e->getMessage()]);
		}
	}



	protected function setProgressAndDoneState (int $taskId, int $progress, bool $isDone) {
		$setString = "progress = $progress";
		if ($isDone) {
			$setString .=
				", state_id    = ".static::DONE.
				", finished_at = '".date('Y-m-d H:i:s', ($this->runTimeGetter)())."'"
			;
		}
		$query = "UPDATE $this->tasksQueue SET $setString WHERE id = $taskId";
		$this->dbAdapter->query($query);
	}



	/**
	 * Удаление старых выполненных задач.
	 */
	protected function deleteOldList (): int {
		$deleteTs = date('Y-m-d H:i:s', time() - $this->oldTasksKeepPeriod);
		$query    = "DELETE FROM $this->tasksQueue WHERE state_id IN (".static::ERROR.','.static::DONE.") AND created_at <= '$deleteTs'";
		$this->dbAdapter->query($query);
		return $this->dbAdapter->getAffectedRows();
	}



	/**
	 * Добавление типа задания.
	 *
	 * @param array{
	 *    code:             string,
	 *    name?:            string,
	 *    description?:     string,
	 *    callback_type_id: int,
	 *    callback_source?: string|false,
	 *    callback_method:  string,
	 *    sort?:            int,
	 * } $data
	 */
	public function createType (array $data): int {
		$this->assert($data, $this->createTypeValidator);
		$this->dbAdapter->query($this->dbUtils->getInsert($this->taskTypes, $data));
		return $this->dbAdapter->getInsertId();
	}



	/**
	 * Редактирование типа задания.
	 *
	 * @param array{
	 *    code:             string,
	 *    name?:            string,
	 *    description?:     string,
	 *    callback_type_id: int,
	 *    callback_source?: string|false,
	 *    callback_method:  string,
	 *    sort?:            int,
	 * } $data
	 */
	public function editType (int $typeId, array $data) {
		$this->assert($data, $this->editTypeValidator);
		$this->dbAdapter->query($this->dbUtils->getUpdate($this->taskTypes, 'id', "$typeId", $data));
	}



	protected function assert (mixed $data, Validatable $validator) {
		try {
			$validator->assert($data);
		} catch (\Respect\Validation\Exceptions\NestedValidationException $e) {
			throw new \InvalidArgumentException(join("\n", $e->getMessages()));
		} catch (\Exception $e) {
			throw new $e;
		}
	}



	public function initStorage () {
		$dbAdapter = $this->dbAdapter;

		$query =
			"CREATE TABLE $this->callbackTypes (
				id   int auto_increment primary key,
				code varchar(32)  not null,
				name varchar(255) not null default '',
				unique index code_uq (code(32))
			) ENGINE=InnoDB;
			";
		$dbAdapter->query($query);

		$query =
			"INSERT INTO $this->callbackTypes
			(code, name)
			VALUES
			('function',     'Функция'),
			('class_method', 'Статический метод класса'),
			('hash_method',  'Метод объекта, хранящегося в ассоциативном массиве')
			";
		$dbAdapter->query($query);

		$query =
			"CREATE TABLE $this->taskTypes (
				id               int auto_increment primary key,
				code             varchar(32)  not null,
				name             varchar(255) not null default '',
				description      text         not null default '',
				callback_type_id int          not null,
				callback_source  varchar(255) not null default '',
				callback_method  varchar(255) not null,
				sort             int          not null default 1,
				created_at       datetime     not null default   current_timestamp,
				updated_at       datetime     not null on update current_timestamp,
				unique index code_uq (code(32)),
				foreign key (callback_type_id) references $this->callbackTypes(id) on delete restrict
			) ENGINE=InnoDB;
			";
		$dbAdapter->query($query);

		$query =
			"CREATE TABLE $this->taskStates (
				id   int auto_increment primary key,
				code varchar(32)  not null,
				name varchar(255) not null default '',
				unique index code_uq (code(32))
			) ENGINE=InnoDB;
			";
		$dbAdapter->query($query);

		$query =
			"INSERT INTO $this->taskStates
			(code, name)
			VALUES
			('new',     'Новая'),
			('running', 'В работе'),
			('paused',  'Запущена'),
			('error',   'Ошибка'),
			('done',    'Готово')
			";
		$dbAdapter->query($query);

		$query =
			"CREATE TABLE $this->tasksQueue (
				id          int auto_increment primary key,
				type_id     int      not null,
				state_id    int      not null default 1,
				progress    int      not null default 0,
				params      text     not null default '',
				created_at  datetime not null default current_timestamp,
				updated_at  datetime not null default current_timestamp on update current_timestamp,
				started_at  datetime null,
				finished_at datetime null,
				index type_ix  (type_id),
				index state_ix (state_id),
				foreign key (type_id)  references $this->taskTypes(id)  on delete cascade,
				foreign key (state_id) references $this->taskStates(id) on delete restrict
			) ENGINE=InnoDB;
			";
		$dbAdapter->query($query);

		$query =
			"CREATE TABLE $this->taskLogs (
				id      int auto_increment primary key,
				task_id int  not null,
				text    text not null,
				index task_ix (task_id),
				foreign key (task_id) references $this->tasksQueue(id) on delete cascade
			) ENGINE=InnoDB;
			";
		$dbAdapter->query($query);
	}



	public function deleteStorage () {
		$this->dbAdapter->query("DROP TABLE IF EXISTS $this->taskLogs");
		$this->dbAdapter->query("DROP TABLE IF EXISTS $this->tasksQueue");
		$this->dbAdapter->query("DROP TABLE IF EXISTS $this->taskTypes");
		$this->dbAdapter->query("DROP TABLE IF EXISTS $this->taskStates");
		$this->dbAdapter->query("DROP TABLE IF EXISTS $this->callbackTypes");
	}
}
