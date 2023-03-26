<?
namespace S5\Db\Adapters;

abstract class AbstractAdapter implements IAdapter {
	/*protected array $tableNamesPrefix = [];

	protected function construct (array $tableNamesPrefix = []) {
		if ($tableNamesPrefix) {
			$this->tableNamesPrefix = $tableNamesPrefix;
		}
	}



	protected function preprocessQuery (string $query): string {
		if ($this->tableNamesPrefix) {
			$query = str_replace($this->tableNamesPrefix[0], $this->tableNamesPrefix[1], $query);
		}
		return $query;
	}*/



	public function quote ($string): string {
		if (ctype_digit((string)$string)) {
			return (string)$string;
		} else {
			return "'".$this->escape($string)."'";
		}
	}



	public function getObject (string $query) {
		return $this->fetchObject($this->query($query));
	}



	public function getObjectsList (string $query): array {
		$r    = $this->query($query);
		$list = [];
		while ($e = $this->fetchObject($r)) {
			$list[] = $e;
		}
		return $list;
	}



	public function getAssoc (string $query) {
		return $this->fetchAssoc($this->query($query));
	}



	public function getAssocList (string $query): array {
		$r    = $this->query($query);
		$list = [];
		while ($e = $this->fetchAssoc($r)) {
			$list[] = $e;
		}
		return $list;
	}



	protected function checkQueryResult ($r, $errorMessage) {
		if (!$r) {
			throw new \Exception(is_string($errorMessage) ? $errorMessage : join("\n",$errorMessage));
		}
		return $r;
	}
}
