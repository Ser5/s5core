<?
namespace S5\BatchDb;

/**
 * Пакетное обновление данных.
 *
 * ```
 * $batchUpdate = new \BatchDb\BatchUpdate(['table_name' => 'users', 'join_on' => 'data.code = t.code', 'rows_limit' => 100]);
 * $batchUpdate->more([...]);
 * $batchUpdate->more([...]);
 * $batchUpdate->more([...]);
 * $batchUpdate->end();
 * ```
 */
class BatchUpdate extends BatchBase {
	protected string $joinOn = 'data.id = t.id';

	private int $_batchLength = 0;



	protected function calcLimits () {
		parent::calcLimits();
		if (!$this->maxBatchLength) {
			//Достаём значение переменной thread_stack
			$threadStack          = (int)$this->adapter->getAssoc("show variables like 'thread_stack'")['Value'];
			//Расчитываем максимальное количество UNION по Формуле Серёжи
			$this->maxBatchLength = (int)floor((($threadStack - 24576 - 1280) / 64) - 1);
			//Чтоб наверняка - ополовиниваем расчитанное количество
			$this->maxBatchLength = (int)floor($this->maxBatchLength / 2);
		}
	}



	protected function getBaseQueryString (array $data): string {
		$selectString = '';
		$setString    = '';
		foreach (array_keys($data) as $fieldName) {
			$selectString .= "0 $fieldName, ";
			$setString    .= "t.$fieldName = data.$fieldName,\n";
		}
		$selectString = substr($selectString, 0, -2);
		$setString    = substr($setString,    0, -2);

		$query =
			"UPDATE $this->tableName t\n".
			"INNER JOIN (\n".
				"SELECT $selectString\n".
				"#values#\n".
			") data ON $this->joinOn\n".
			"SET\n".
				"$setString";

		return $query;
	}



	protected function getValuesString (array $data): string {
		$this->_batchLength++;

		$fieldValuesString = '';
		foreach ($data as $k => $v) {
			$v = $this->adapter->escape($v);
			if (isset($this->colParams[$k]['is_binary']) and $this->colParams[$k]['is_binary']) {
				$fieldValuesString .= "BINARY '$v', ";
			} else {
				$fieldValuesString .= "'$v', ";
			}
		}
		$fieldValuesString = substr($fieldValuesString, 0, -2);

		return
			"UNION ALL\n".
			"SELECT $fieldValuesString\n";
	}



	protected function isLimitReached (string $fieldValuesString): bool {
		return (
			parent::isLimitReached($fieldValuesString) or
			$this->_batchLength > $this->maxBatchLength
		);
	}



	protected function assembleQuery (): string {
		$this->_batchLength = 0;
		$this->queryValues  = substr($this->queryValues, 0, -1);
		$query              = str_replace('#values#', $this->queryValues, $this->queryBase);
		return $query;
	}
}
