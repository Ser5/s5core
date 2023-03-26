<?
namespace S5\Db\BatchDb;

/**
 * Пакетная вставка данных.
 *
 * ```
 * $batchInsert = new \BatchDb\BatchInsert(['table_name' => 'users']);
 * $batchInsert->more([...]);
 * $batchInsert->more([...]);
 * $batchInsert->more([...]);
 * $batchInsert->end();
 * ```
 */
class BatchInsert extends BatchBase {
	protected bool $isReplace = false;

	protected function getBaseQueryString (array $data): string {
		$fieldNamesString = '(' . join(', ', array_keys($data)) . ')';
		$action = (!$this->isReplace ? 'INSERT' : 'REPLACE');
		$query  =
			"$action INTO `$this->tableName`\n".
			"$fieldNamesString\n".
			"VALUES\n".
			"#values#";
		return $query;
	}



	protected function getValuesString (array $data): string {
		$fieldValuesString = '';
		foreach ($data as $k => $v) {
			$v = $this->dbAdapter->escape($v);
			$fieldValuesString .= "'$v', ";
		}
		$fieldValuesString = mb_substr($fieldValuesString, 0, -2, 'UTF-8');
		return "($fieldValuesString),\n";
	}



	protected function assembleQuery (): string {
		$this->queryValues = mb_substr($this->queryValues, 0, -2, 'UTF-8');
		$query             = str_replace('#values#', $this->queryValues, $this->queryBase);
		return $query;
	}
}
