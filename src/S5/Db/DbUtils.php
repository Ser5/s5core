<?
namespace S5\Db;
use S5\Db\Adapters\IAdapter;


class DbUtils {
	protected IAdapter $dbAdapter;

	protected int $savepointNumber = 0;


	public function __construct (IAdapter $dbAdapter) {
		$this->dbAdapter = $dbAdapter;
	}



	public function getInsert (string $tableName, array $valuesHash): string {
		if (!$valuesHash) {
			throw new \InvalidArgumentException("\$valuesHash пуст");
		}

		$fieldsString = '';
		$valuesString = '';
		foreach ($valuesHash as $field => $value) {
			$fieldsString .= $field.',';
			$valuesString .= $this->dbAdapter->quote($value).",";
		}
		$fieldsString = substr($fieldsString, 0, -1);
		$valuesString = substr($valuesString, 0, -1);

		$tableName = $this->dbAdapter->escape($tableName);

		$query =
			"INSERT INTO $tableName
			($fieldsString)
			VALUES
			($valuesString)
			";
		return $query;
	}



	public function getUpdate (string $tableName, string $idFieldName, string $id, array $valuesHash): string {
		if (!$valuesHash) {
			throw new \InvalidArgumentException("\$valuesHash пуст");
		}

		$setString = '';
		foreach ($valuesHash as $field => $value) {
			$setString .= "$field = ".$this->dbAdapter->quote($value).",\n";
		}
		$setString = substr($setString, 0, -2);

		$tableName   = $this->dbAdapter->escape($tableName);
		$idFieldName = $this->dbAdapter->escape($idFieldName);
		$id          = $this->dbAdapter->quote($id);

		$query =
			"UPDATE $tableName
			SET
			$setString
			WHERE $idFieldName = $id
			";
		return $query;
	}



	/**
	 * Возвращает массив целых чисел.
	 *
	 * Можно вызывать следующим образом:
	 * - $utils->getIntsList([1,2,3])
	 * - $utils->getIntsList('1,2,3')
	 * - $r = mysql_query("SELECT id FROM table");
	 *   $utils->getIntsList($r, 'id');
	 *
	 * Во всех трёх случаях вернёт [1,2,3]
	 *
	 * @param  array<int>|string|object $ints
	 * @param  string|false             $dbFieldName
	 * @return array
	 */
	public function getIntsList ($ints, $dbFieldName = false): array {
		if (is_object($ints)) {
			$ints = $this->getListFromResource($ints, $dbFieldName);
		} elseif (is_array($ints)) {
			foreach ($ints as $int) {
				if (!ctype_digit((string)$int)) {
					throw new \InvalidArgumentException("Массив содержит нечисловое значение: [$int]");
				}
			}
			$intsList = $ints;
		} else {
			if (preg_match('/[^\d\s,]/', $ints)) {
				throw new \InvalidArgumentException("Строка содержит нечисловое значение: [$ints]");
			}
			$intsList = explode(',', preg_replace('/[^\d,]/', '', $ints));
		}
		return $intsList;
	}



	/**
	 * Возвращает строку целых чисел вида '1,2,3'.
	 *
	 * @see getIntsList()
	 *
	 * @param  array<int>|string|object $ints
	 * @param  string|false             $dbFieldName
	 * @return string
	 */
	public function getIntsString ($ints, $dbFieldName = false): string {
		if (is_object($ints)) {
			$ints = self::getListFromResource($ints, $dbFieldName);
		} elseif (is_array($ints)) {
			$intsString = join(',', $ints);
		} else {
			$intsString = $ints;
		}
		if (preg_match('/[^\d\s,]/', $intsString)) {
			throw new \InvalidArgumentException("Источник \$int должен содержать число или массив чисел: [$ints]");
		}
		return $intsString;
	}



	public function getStringsString ($strings/*, $dbFieldName = false*/): string {
		if (is_object($strings)) {
			$return = self::getListFromResource($strings, $dbFieldName);
		} elseif (is_array($strings)) {
			$return = '';
			foreach ($strings as $s) {
				$return .= "'".$this->dbAdapter->escape($s)."',";
			}
			$return = substr($return, 0, -1);
		} else {
			@$return = "'".$this->dbAdapter->escape((string)$strings)."'";
		}
		return $return;
	}



	protected function getListFromResource (object $r, string $dbFieldName): array {
		if ($dbFieldName === false or $dbFieldName === '') {
			throw new \InvalidArgumentException("\$dbFieldName не указан");
		}
		$list = [];
		while ($row = $this->dbAdapter->fetchObject($r)) {
			$list[] = $row->$dbFieldName;
		}
		return $list;
	}



	/**
	 * @param  array|int|string $limit
	 * @return array|false
	 */
	public function getLimitList ($limit) {
		$limitString = $this->getLimitString($limit);
		if (!$limitString) {
			return false;
		}
		if (ctype_digit($limitString)) {
			return [0, (int)$limitString];
		}
		$list = explode(', ', $limitString);
		return [(int)$list[0], (int)$list[1]];
	}



	/**
	 * Возвращает строку, подходящую для подстановки после LIMIT.
	 *
	 * Примеры:
	 * - 10             -> '10'
	 * - '10'           -> '10'
	 * - [10]           -> '10'
	 * - [false, 10]    -> '10'
	 * - [10, false]    -> '10, 18446744073709551615'
	 * - [10, 10]       -> '10, 10'
	 * - ''             -> ''
	 * - false          -> ''
	 * - [false]        -> ''
	 * - [false, false] -> ''
	 *
	 * @param  array|int|string $limit
	 * @return string
	 */
	public function getLimitString ($limit) {
		if (is_array($limit)) {
			$count = count($limit);
			if ($count == 0) {
				$limitString = '';
			} elseif ($count == 1) {
				$limitString = $limit[0];
			} elseif ($count == 2) {
				if ($limit[0] and $limit[1]) {
					$limitString = "$limit[0], $limit[1]";
				} elseif ($limit[0] and !$limit[1]) {
					$limitString = "$limit[0], 18446744073709551615";
				} elseif (!$limit[0] and $limit[1]) {
					$limitString = $limit[1];
				} else {
					$limitString = '';
				}
			} else {
				throw new \InvalidArgumentException("More than 2 values in array");
			}
		} elseif (ctype_digit((string)$limit)) {
			$limitString = $limit;
		} elseif (preg_match('/^\s*\d+\s*,\s*\d+\s*$/', $limit)) {
			$limitString = $limit;
		} else {
			$limitString = '';
		}
		if (preg_match('/[^\d\s,]/', $limitString)) {
			throw new \InvalidArgumentException("Wrong limit result: [$limitString]");
		}
		return $limitString;
	}



	/**
	 * Возвращает строку, подходящую для поиска диапазонов.
	 *
	 * Допустим, мы вызвали метод как:<br>
	 * `$utils->getRangeString($range, 'width');`
	 *
	 * Тогда, в зависимости от значения $range, результаты будут такие:
	 * - [false, false] - ""
	 * - [10,    false] - "width >= 10"
	 * - [false, 100  ] - "width <= 100"
	 * - [10,    100  ] - "(width >= 10 AND width <= 100)"
	 *
	 * Вызов в произвольном режиме:<br>
	 * `$utils->getRangeString($range, ["(width > # OR is_wide = 1)", "AND", "width < #"]);`
	 *
	 * Вместо решётки # подставляется соответствующее значение из диапазона.
	 *
	 * Результаты:
	 * - [false, false] - ""
	 * - [10,    false] - "(width > 10 OR is_wide = 1)"
	 * - [false, 100  ] - "width < 100"
	 * - [10,    100  ] - "((width > 10 OR is_wide = 1) AND width < 100)"
	 *
	 * При работе метода дополнительно проводятся проверки и преобразования указанного диапазона. Например:
	 * - [7, 4]      -> [false, false]
	 * - [10]        -> [10, false]
	 * - "10"        -> [10, false]
	 * - 10          -> [10, false]
	 * - "10-100"    -> [10, 100]
	 * - " 10, 100 " -> [10, 100]
	 *
	 * @param  string|int|array $range
	 * @param  string|array     $params
	 * @return string
	 */
	public function getRangeString ($range, $params) {
		//Проверка аргументов
		if (is_array($params) and count($params) != 3) {
			throw new \InvalidArgumentException("\$params передан как массив - должен содержать 3 элемента");
		}
		//Преобразование аргументов
		//$range
		$range = $this->getRangeList($range);
		//$params
		if (is_string($params)) {
			$params = ["$params >= #", "AND", "$params <= #"];
		}
		//Сборка ответа
		$return = '';
		$isFrom = ($range[0] !== false);
		$isTo   = ($range[1] !== false);
		if ($isFrom) {
			$return .= str_replace('#', $range[0], $params[0]);
		}
		if ($isFrom and $isTo) {
			$return .= " $params[1] ";
		}
		if ($isTo) {
			$return .= str_replace('#', $range[1], $params[2]);
		}
		if ($isFrom and $isTo) {
			$return = "($return)";
		}
		return $return;
	}



	public function getRangeList ($range): array {
		if (is_string($range) or is_int($range)) {
			//Это строка?
			$matches = [];
			if (ctype_digit("$range")) {
				$range = [$range, false];
			}
			elseif (preg_match('/(\d+)\D+(\d+)/', $range, $matches)) {
				$range = [$matches[1], $matches[2]];
			}
		} else {
			if (!is_array($range) or !count($range)) {
				//Это бред?
				$range = [false, false];
			} else {
				//Это массив?
				$range = array_slice($range, 0, 2);
				foreach ($range as &$value) {
					$value = trim($value);
					if (!ctype_digit("$value")) {
						$value = false;
					}
				}
				unset($value);
				if (count($range) == 1) {
					$range[] = false;
				}
			}
		}
		if ($range[0] and $range[1] and $range[0] > $range[1]) {
			$range = [false, false];
		}
		return $range;
	}



	public function getCrudActionsToIdsData (array $existingIdsList, array $newIdsList): array {
		return [
			'delete' => array_diff($existingIdsList, $newIdsList),
			'create' => array_diff($newIdsList, $existingIdsList),
			'edit'   => array_intersect($existingIdsList, $newIdsList),
		];
	}



	public function begin () {
		if ($this->savepointNumber == 0) {
			$this->dbAdapter->query("begin");
		} else {
			$this->dbAdapter->query("savepoint s5qb".$this->savepointNumber);
			$this->savepointNumber++;
		}
	}

	public function commit ($isFull = false) {
		if ($this->savepointNumber == 0 or $isFull) {
			$this->dbAdapter->query("commit");
			$this->savepointNumber = 0;
		} else {
			$this->dbAdapter->query("release savepoint s5qb".$this->savepointNumber);
			$this->savepointNumber--;
		}
	}

	public function rollback ($isFull = false) {
		if ($this->savepointNumber == 0 or $isFull) {
			$this->dbAdapter->query("rollback");
			$this->savepointNumber = 0;
		} else {
			$this->dbAdapter->query("rollback to savepoint s5qb".$this->savepointNumber);
			$this->savepointNumber--;
		}
	}
}