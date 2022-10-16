<?
namespace S5\Db\Adapters;

class MysqlAdapter extends AbstractAdapter {
	/*public function __construct (array $tableNamesPrefix = []) {
		parent::construct($tableNamesPrefix);
	}



	public function withTableNamesPrefix (array $tableNamesPrefix): MysqlAdapter {
		return new static($tableNamesPrefix);
	}*/



	public function escape (string $string): string {
		return mysql_real_escape_string($string);
	}



	public function query (string $query) {
		return $this->checkQueryResult(mysql_query($query), mysql_error());
	}



	public function fetchObject ($r) {
		return mysql_fetch_object($r);
	}

	public function fetchAssoc ($r) {
		return mysql_fetch_assoc($r);
	}



	public function getInsertId (): int {
		return mysql_insert_id();
	}

	public function getAffectedRows (): int {
		return mysql_affected_rows();
	}
}
