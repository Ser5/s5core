<?
namespace S5\Adapters;

class MysqliAdapter implements IAdapter {
	protected \mysqli $mysqli;

	public function __construct (\mysqli $mysqli) {
		$this->mysqli = $mysqli;
	}



	public function escape (string $query): string {
		return $this->mysqli->real_escape_string($query);
	}



	public function query (string $query) {
		$this->mysqli->query($query);
	}



	public function getAssoc (string $query): array {
		return $this->mysqli->query($query)->fetch_assoc();
	}



	public function getAffectedRows (): int {
		return $this->mysqli->affected_rows;
	}
}
