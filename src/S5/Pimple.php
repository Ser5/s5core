<?
namespace S5;

class Pimple extends \Pimple\Container {
	public function getHash (...$keysList): array {
		$hash = [];
		foreach ($keysList as $key) {
			if (strpos($key,'-') === false) {
				$hash[$key] = $this[$key];
			} else {
				$keys = explode('-',$key);
				$hash[$keys[1]] = $this[$keys[0]];
			}
		}
		return $hash;
	}



	/**
	 * То же, что getHash(), только короче.
	 */
	public function __invoke (...$args): array {
		return $this->getHash(...$args);
	}
}
