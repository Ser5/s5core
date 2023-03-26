<?
namespace S5\Db\BatchDb;
use S5\Db\Adapters\IAdapter;

/**
 * Основа для пакетного изменения данных.
 */
abstract class BatchBase {
	protected IAdapter $dbAdapter;
	protected string   $tableName;
	protected array    $colParams = [];

	protected int    $maxQueryLength = 0;
	protected int    $maxBatchLength = 0;
	protected array  $dataList;
	protected string $queryBase;
	protected string $queryValues;
	protected int    $totalAffectedRowsNumber = 0;

	/**
	 * Ctor.
	 *
	 * $params:
	 * - dbAdapter
	 * - tableName      - по-любому надо передать
	 * - maxQueryLength - Ограничение длины запроса по символам. Вычисляется самостоятельно, но можно и принудительно передать.
	 * - maxBatchLength - Максимальное количество записей в пакете. Тоже вычисляется, только про количество записей в одном пакете.
	 * - colParams
	 *
	 * @param array $params
	 */
	public function __construct (array $params) {
		foreach ($params as $k => $v) {
			$this->{$k} = $v;
		}
		$this->calcLimits();
		$this->clearQueryData();
	}



	protected function calcLimits () {
		if (!$this->maxQueryLength) {
			$maxAllowedPacket     = (int)$this->dbAdapter->getAssoc("show variables like 'max_allowed_packet'")['Value'];
			$this->maxQueryLength = (int)($maxAllowedPacket - round($maxAllowedPacket / 10));
		}
	}



	protected function clearQueryData () {
		$this->dataList    = [];
		$this->queryBase   = '';
		$this->queryValues = '';
	}



	/**
	 * Добавляет строки в запрос, выполняет его при достижении лимита.
	 *
	 * @param  array $dataList  Массив с новыми данными
	 * @return int              Если упёрлись в лимит - то количество вставленных строк из выполненного запроса, иначе ноль
	 */
	public function more (array $dataList): int {
		if (!$dataList) {
			return 0;
		}

		//За один more() может выполниться несколько запросов,
		//в конце вернём итоговую сумму вставленных строк.
		$affectedRowsNumber = 0;

		//Добавим новые данные в очередь
		$this->dataList = array_merge($this->dataList, array_values($dataList));

		//За один more() может выполниться несколько запросов, один запрос или ни одного - в зависимости
		//от того, сколько данных передано и каково ограничение.
		//Будем крутить while, пока есть данные в очереди.
		//Наткнулись на ограничение - выполняем запрос и продолжаем крутить.
		while ($this->dataList) {
			//Если запрос пустой - это может значить, что:
			//- или объект только что инициализирован и это будет первый его запрос
			//- или предыдущий запрос был выполнен и очищен
			//В любом случае, начнём составлять новый запрос.
			if (!$this->queryValues) {
				$this->queryBase = $this->getBaseQueryString($this->dataList[0]);
			}
			//Составляем следующую строку запроса из данных, лежащих в очереди первыми
			$fieldValuesString = $this->getValuesString($this->dataList[0]);
			//Если мы прилепим новую строку к имеющемуся запросу - мы не выйдем за ограничение?
			if ($this->isLimitReached($fieldValuesString)) {
				//Мы вышли за ограничение - тогда прилеплять новую строку не будем,
				//выполним тот запрос, что уже есть
				$affectedRowsNumber += $this->runQuery();
			} else {
				//За ограничение ещё не вышли.
				//Из массива данных удалим отработанную строку, а к запросу прилепим ещё часть.
				array_shift($this->dataList);
				$this->queryValues .= $fieldValuesString;
			}
		}

		return $affectedRowsNumber;
	}



	abstract protected function getBaseQueryString (array $data): string;

	abstract protected function getValuesString (array $data): string;



	protected function isLimitReached (string $rowValuesString): bool {
		return (strlen($this->queryBase) + strlen($this->queryValues) + strlen($rowValuesString) > $this->maxQueryLength);
	}



	/**
	 * Принудительное выполнение запроса.
	 *
	 * Ситуация, когда упёрлись в лимит, вообще труднодостижима.
	 * А это значит, что вызов только лишь more() не вставит никаких данных.
	 * Тогда мы должны самостоятельно сказать объекту,
	 * что нужно выполнить запрос как он есть вызовом этого метода.
	 *
	 * ```
	 * $batchInsert->more([...]);
	 * $batchInsert->end();
	 * ```
	 *
	 * @return int
	 */
	public function end (): int {
		$affectedRowsNumber = $this->runQuery();
		$this->clearQueryData();
		return $affectedRowsNumber;
	}



	abstract protected function assembleQuery (): string;



	protected function runQuery (): int {
		if ($this->queryValues) {
			$query = $this->assembleQuery();
			$this->dbAdapter->query($query);
			$affectedRowsNumber = $this->dbAdapter->getAffectedRows();
			$this->totalAffectedRowsNumber += $affectedRowsNumber;
			$this->queryBase   = '';
			$this->queryValues = '';
		} else {
			$affectedRowsNumber = 0;
		}
		return $affectedRowsNumber;
	}
}
