<?
namespace S5\Db\Adapters;

class PdoAdapter extends AbstractAdapter {
	protected \PDO $pdo;
	protected int  $affectedRows = 0;

	public function __construct (\PDO $pdo) {
		$this->pdo = $pdo;
	}



	public function escape (string $query): string {
		return trim($this->pdo->quote($query), "'");
	}



	public function query (string $query) {
		try {
			$r = $this->checkQueryResult($this->pdo->query($query), $this->pdo->errorInfo());
			$this->affectedRows = $r->rowCount();
		} catch (\Exception $e) {
			$this->affectedRows = 0;
			throw $e;
		}
		return $r;
	}



	public function fetchObject ($r) {
		return $r->fetch(\PDO::FETCH_OBJ);
	}

	public function fetchAssoc ($r) {
		return $r->fetch(\PDO::FETCH_ASSOC);
	}



	public function getInsertId (): int {
		return (int)$this->pdo->lastInsertId();
	}

	public function getAffectedRows (): int {
		return $this->affectedRows;
	}
}
