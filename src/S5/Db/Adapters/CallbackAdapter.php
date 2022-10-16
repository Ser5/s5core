<?
namespace S5\Db\Adapters;

class CallbackAdapter extends AbstractAdapter {
	protected $callback;

	public function __construct ($callback) {
		$this->callback = $callback;
	}



	public function escape (string $string): string {
		return call_user_func($this->callback, 'escape', compact('string'));
	}



	public function query (string $query) {
		return call_user_func($this->callback, 'query', compact('query'));
	}



	public function fetchObject ($r) {
		return call_user_func($this->callback, 'fetchObject', ['r' => null]);
	}

	public function fetchAssoc ($r) {
		return call_user_func($this->callback, 'fetchAssoc', ['r' => null]);
	}



	public function getObject (string $query) {
		return call_user_func($this->callback, 'getObject', compact('query'));
	}

	public function getObjectsList (string $query): array {
		return call_user_func($this->callback, 'getObjectsList', compact('query'));
	}



	public function getAssoc (string $query) {
		return call_user_func($this->callback, 'getAssoc', compact('query'));
	}

	public function getAssocList (string $query): array {
		return call_user_func($this->callback, 'getAssocList', compact('query'));
	}



	public function getInsertId (): int {
		return call_user_func($this->callback, 'getInsertId');
	}

	public function getAffectedRows (): int {
		return call_user_func($this->callback, 'getAffectedRows');
	}
}
