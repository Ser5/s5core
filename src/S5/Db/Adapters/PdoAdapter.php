<?
namespace S5\Adapters;

class PdoAdapter implements IAdapter {
	protected \PDO $pdo;
	protected int  $affectedRows = 0;

	public function __construct (\PDO $pdo) {
		$this->pdo = $pdo;
	}



	public function escape (string $query): string {
		return $this->pdo->quote($query);
	}



	public function query (string $query) {
		$this->pdo->query($query);
	}



	public function getAssoc (string $query): array {
		$sth = $this->pdo->query($query);
		$r   = $sth->fetch(\PDO::FETCH_ASSOC);
		$this->affectedRows = $sth->rowCount();
		return $r;
	}



	public function getAffectedRows (): int {
		return $this->affectedRows;
	}
}
