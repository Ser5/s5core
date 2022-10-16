<?
namespace S5\Db\Adapters;

class MysqliAdapter extends AbstractAdapter {
	protected \mysqli $mysqli;

	public function __construct (\mysqli $mysqli, array $tableNamesPrefix = []) {
		//parent::construct($tableNamesPrefix);
		$this->mysqli = $mysqli;
	}



	/*public function withTableNamesPrefix (array $tableNamesPrefix): MysqliAdapter {
		return new static($this->mysqli, $tableNamesPrefix);
	}*/



	public function escape (string $query): string {
		return $this->mysqli->real_escape_string($query);
	}



	public function query (string $query) {
		return $this->checkQueryResult($this->mysqli->query($query), $this->mysqli->error);
	}



	public function fetchObject ($r) {
		return $r->fetch_object();
	}

	public function fetchAssoc ($r) {
		return $r->fetch_assoc();
	}



	public function getInsertId (): int {
		return $this->mysqli->insert_id;
	}

	public function getAffectedRows (): int {
		return $this->mysqli->affected_rows;
	}
}
