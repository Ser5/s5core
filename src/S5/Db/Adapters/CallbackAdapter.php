<?
namespace S5\Db\Adapters;

class CallbackAdapter extends AbstractAdapter {
	protected $callback;

	public function __construct ($callback) {
		$this->callback = $callback;
	}



	public function escape (string $string): string {
		return call_user_func($this->callback, 'escape', [$string]);
	}



	public function query (string $query) {
		return call_user_func($this->callback, 'query', [$query]);
	}



	public function fetchObject ($r) {
		return call_user_func($this->callback, 'fetchObject', [$r]);
	}

	public function fetchAssoc ($r) {
		return call_user_func($this->callback, 'fetchAssoc', [$r]);
	}



	public function getObject (string $query) {
		return call_user_func($this->callback, 'getObject', [$query]);
	}

	public function getObjectsList (string $query): array {
		return call_user_func($this->callback, 'getObjectsList', [$query]);
	}



	public function getAssoc (string $query) {
		return call_user_func($this->callback, 'getAssoc', [$query]);
	}

	public function getAssocList (string $query): array {
		return call_user_func($this->callback, 'getAssocList', [$query]);
	}



	public function getInsertId (): int {
		return call_user_func($this->callback, 'getInsertId', []);
	}

	public function getAffectedRows (): int {
		return call_user_func($this->callback, 'getAffectedRows', []);
	}
}
