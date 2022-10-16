<?
namespace S5\TasksManager;
use S5\Db\Adapters\IAdapter;
use S5\Db\DbUtils;
use Respect\Validation\Validator as v;



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
	protected int      $oldTasksKeepPeriod;

	protected \Respect\Validation\Validator $createValidator;
	protected \Respect\Validation\Validator $editValidator;

	protected string $callbackTypes = 'callback_types';
	protected string $taskTypes     = 'task_types';
	protected string $taskStates    = 'task_states';
	protected string $tasksQueue    = 'tasks_queue';


	public function __construct (array $params) {
		$p = $params + [
			'tableNamesPrefix'    => '',
			'progressUpdateDelay' => 1,
			'oldTasksKeepPeriod'  => 86400*30,
		];

		$this->dbAdapter           = $p['dbAdapter'];
		$this->dbUtils             = $p['dbUtils'];
		$this->progressUpdateDelay = $p['progressUpdateDelay'];
		$this->oldTasksKeepPeriod  = $p['oldTasksKeepPeriod'];

		$this->initValidators();
		$this->initTableNamePrefixes($p['tableNamesPrefix']);
	}



	protected function initValidators () {
		$min0     = v::intVal()->min(0);
		$min1     = v::intVal()->min(1);
		$string   = v::stringVal();
		$string1  = v::stringVal()->length(1);
		$ids      = v::anyOf(v::falseVal(), $min1, v::each($min1), $string);
		$progress = v::intVal()->min(0)->max(100);

		$this->createValidator = (new v())
			->key('type_id',  $min1)
			->key('state_id', $min1,     false)
			->key('progress', $progress, false)
			->key('params',   $string,   false)
			->key('log',      $string,   false)
		;

		$this->editValidator = (new v())
			->key('type_id',  $min1,     false)
			->key('state_id', $min1,     false)
			->key('progress', $progress, false)
			->key('params',   $string,   false)
			->key('log',      $string,   false)
			->key('add_log',  $string,   false)
		;

		$this->listValidator = (new v())
			->key('ids',       $ids, false)
			->key('type_ids',  $ids, false)
			->key('state_ids', $ids, false)
		;

		$this->createTypeValidator = (new v())
			->key('code',             $string1)
			->key('name',             $string, false)
			->key('description',      $string, false)
			->key('callback_type_id', $min1)
			->key('callback_source',  v::anyOf(v::falseVal(), $string1), false)
			->key('callback_method',  $string1)
			->key('sort',             $min0, false)
		;

		$this->editTypeValidator = (new v())
			->key('code',        $string1, false)
			->key('name',        $string,  false)
			->key('description', $string,  false)
			->key('sort',        $min0,    false)
		;
	}



	protected function initTableNamePrefixes (string $tableNamesPrefix) {
		if ($tableNamesPrefix) {
			foreach (['callbackTypes', 'taskTypes','taskStates','tasksQueue'] as $tableName) {
				$this->$tableName = $tableNamesPrefix . $this->$tableName;
			}
		}
	}



	/**
	 * Добавление задания в очередь.
	 *
	 * @param array{
	 *    type_id:  int,
	 *    state_id: int,
	 *    progress: int,
	 *    params:   string,
	 *    log:      string,
	 * } $data
	 * @return int
	 */
	public function create (array $data): int {
		$this->assert($data, $this->createValidator);
		$this->dbAdapter->query($this->dbUtils->getInsert($this->tasksQueue, $data));
		return $this->dbAdapter->getInsertId();
	}



	/**
	 * Редактирование задания.
	 *
	 * @param array{
	 *    type_id:  int,
	 *    state_id: int,
	 *    progress: int,
	 *    params:   string,
	 *    log:      string,
	 *    add_log:  string,
	 * } $data
	 * @return int
	 */
	public function edit (int $taskId, array $data) {
		$this->assert($data, $this->editValidator);
		if (isset($data['add_log']) and !isset($data['log'])) {
			$addLog = $this->dbAdapter->escape($data['add_log']);
			$query  = "UPDATE $this->tasksQueue SET log = CONCAT(log, '$addLog') WHERE id = $taskId";
			$this->dbAdapter->query($query);
		}
		unset($data['add_log']);
		if ($data) {
			$this->dbAdapter->query($this->dbUtils->getUpdate($this->tasksQueue, 'id', $taskId, $data));
		}
	}



	public function get (int $id): ?object {
		return $this->getList(['ids' => $id])[0] ?: null;
	}



	/**
	 * Получение списка заданий.
	 *
	 * @param array{
	 *    ids:       false|int|array<int>|string,
	 *    type_ids:  false|int|array<int>|string,
	 *    state_ids: false|int|array<int>|string,
	 * } $data
	 * @return array
	 */
	public function getList (array $params = []): array {
		$whereString = $this->getListWhereString($params);

		$query =
			"SELECT *
			FROM $this->tasksQueue
			WHERE $whereString 1
			ORDER BY id
			";

		return $this->dbAdapter->getObjectsList($query);
	}



	/**
	 * Удаление списка заданий.
	 *
	 * @param array{
	 *    ids:       false|int|array<int>|string,
	 *    type_ids:  false|int|array<int>|string,
	 *    state_ids: false|int|array<int>|string,
	 * } $data
	 * @return int
	 */
	public function deleteList (array $params = []): int {
		$whereString = $this->getListWhereString($params);

		$query = "DELETE FROM $this->tasksQueue WHERE $whereString 1";
		$this->dbAdapter->query($query);

		return $this->dbAdapter->getAffectedRows();
	}



	protected function getListWhereString (array $params = []): string {
		$p = $params + [
			'ids'       => false,
			'type_ids'  => false,
			'state_ids' => false,
		];
		$this->assert($p, $this->listValidator);

		$whereString = '';
		if ($p['ids']) {
			$whereString .= 'id IN (' . $this->dbUtils->getIntsString($p['ids']) . ') AND ';
		}
		if ($p['type_ids']) {
			$whereString .= 'type_id IN (' . $this->dbUtils->getIntsString($p['type_ids']) . ') AND ';
		}
		if ($p['state_ids']) {
			$whereString .= 'state_id IN (' . $this->dbUtils->getIntsString($p['state_ids']) . ') AND ';
		}

		return $whereString;
	}



	/**
	 * Запуск задачи - если очередь свободна и есть задачи для запуска.
	 *
	 * @param array{
	 *    callbacks_hash: array|false,
	 *    db_adapter:     IAdapter|null,
	 *    time_getter:    callable|false,
	 * } $params
	 * @return int|false
	 */
	public function run (array $params = []) {
		$p = $params + [
			'callbacks_hash' => false,
			'db_adapter'     => null,
			'time_getter'    => false,
		];
		if (!$p['db_adapter']) {
			$p['db_adapter'] = $this->dbAdapter;
		}

		$dbUtils   = $this->dbUtils;
		$ranTaskId = false;

		$dbUtils->begin();
		try {
			$ranTaskId = $this->runInTransaction($p);
			$dbUtils->commit();
		} catch (\Exception $e) {
			$dbUtils->rollback();
		}

		return $ranTaskId;
	}



	/**
	 * Логика работы с задачей, запускается в run() внутри транзакции.
	 *
	 * @param  array $params
	 * @return int|false
	 */
	protected function runInTransaction (array $params) {
		$dbAdapter  = $params['db_adapter'];
		$tasksQueue = $this->tasksQueue;
		$timeGetter = $params['time_getter'] ?: fn()=>time();

		//Запущено уже чё?
		$query = "SELECT id FROM $tasksQueue WHERE state = ".static::RUNNING." FOR UPDATE";
		if ($dbAdapter->getObject($query)) {
			return false;
		}

		//Есть новые задачи?
		$query =
			"SELECT
				queue.*,
				types.callback_type_id AS _callback_type_id,
				types.callback_source  AS _callback_source,
				types.callback_method  AS _callback_method
			FROM $tasksQueue
			INNER JOIN $this->taskTypes AS types ON types.id = queue.task_type_id
			WHERE state = ".static::NEW.".
			ORDER BY id
			LIMIT 1
			FOR UPDATE
			";
		$task = $dbAdapter->getObject($query);
		if (!$task) {
			return false;
		}

		//Берём новую задачу в работу - ставим ей статус "Выполняется"
		$query = "UPDATE $tasksQueue SET state = ".static::RUNNING.", progress = 0 WHERE id = $task->id";
		$dbAdapter->query($query);

		$lastUpdateTs = $timeGetter();
		$logString    = '';
		$taskUpdater  = function ($progress, string $addLogString, ?bool $isDone = null) use ($dbAdapter, $tasksQueue, $timeGetter, $task, &$lastUpdateTs, &$logString) {
			$nowTs      = $timeGetter();
			$progress   = (int)round($progress);
			$logString .= $addLogString;
			if (is_null($isDone)) {
				$isDone = ($progress == 100);
			} elseif ($isDone === true) {
				$progress = 100;
			}
			if ($isDone or $nowTs >= $lastUpdateTs + $this->progressUpdateDelay) {
				$logString = $dbAdapter->quote($logString);
				$setString = "progress = $progress, log = CONCAT(log, $logString)";
				if ($isDone) {
					$setString .= ", state = ".static::DONE;
				}
				$query = "UPDATE $tasksQueue SET $setString WHERE id = $task->id";
				$dbAdapter->query($query);
				$logString    = '';
				$lastUpdateTs = $nowTs;
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
				if (!$p['callbacks_hash']) {
					throw new \InvalidArgumentException("Не передан callbacks_hash");
				}
				$callback = [$p['callbacks_hash'][$task->_callback_source], $task->_callback_method];
			break;
		}

		$taskParamsList = $task->params ? [unserialize($task->params)] : [];
		call_user_func($callback, $taskUpdater, $taskParamsList);

		return $task->id;
	}



	/**
	 * Удаление старых выполненных задач.
	 * @return int
	 */
	protected function deleteOldList (): int {
		$deleteTs = date('Y-m-d H:i:s', time() - $this->oldTasksKeepPeriod);
		$query    = "DELETE FROM $tasksQueue WHERE state IN (".static::ERROR.','.static::DONE.") AND created_at <= '$deleteTs'";
		$this->dbAdapter->query($query);
		return $this->dbAdapter->getAffectedRows();
	}



	/**
	 * Добавление типа задания.
	 *
	 * @param array{
	 *    code:             string,
	 *    name:             string,
	 *    description:      string,
	 *    callback_type_id: int,
	 *    callback_source:  string|false,
	 *    callback_method:  string,
	 *    sort:             int,
	 * } $data
	 * @return int
	 */
	public function createType (array $data): int {
		$this->assert($data, $this->createTypeValidator);
		$this->dbAdapter->query($this->dbUtils->getInsert($this->taskTypes, $data));
		return $this->dbAdapter->getInsertId();
	}



	/**
	 * Добавление типа задания.
	 *
	 * @param array{
	 *    code:             string,
	 *    name:             string,
	 *    description:      string,
	 *    callback_type_id: int,
	 *    callback_source:  string|false,
	 *    callback_method:  string,
	 *    sort:             int,
	 * } $data
	 * @return int
	 */
	public function editType (array $data): int {
		$this->assert($data, $this->editTypeValidator);
		$this->dbAdapter->query($this->dbUtils->getUpdate($data));
	}



	protected function assert (mixed $data, \Respect\Validation\Validator $validator) {
		try {
			$validator->assert($data);
		} catch (\Respect\Validation\Exceptions\Exception $e) {
			throw new \InvalidArgumentException(join("\n", $e->getMessages()));
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
			('new',     'Новое'),
			('running', 'Выполняется'),
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
				log         text     not null default '',
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
	}



	public function deleteStorage () {
		$this->dbAdapter->query("DROP TABLE IF EXISTS $this->tasksQueue");
		$this->dbAdapter->query("DROP TABLE IF EXISTS $this->taskTypes");
		$this->dbAdapter->query("DROP TABLE IF EXISTS $this->taskStates");
		$this->dbAdapter->query("DROP TABLE IF EXISTS $this->callbackTypes");
	}
}
