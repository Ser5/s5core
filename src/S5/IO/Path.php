<?
namespace S5\IO;

class Path implements IStringablePath {
	protected string $pathString;

	public function __construct ($pathString) {
		$this->pathString = $this->normalizePathString($pathString);
	}



	protected function normalizePathString ($pathString) {
		$pathString         = str_replace('\\',       '/', $pathString);
		$pathString         = preg_replace('|/{2,}|', '/', $pathString);
		$pathPartsList      = explode('/', $pathString);
		$finalPathPartsList = [];

		foreach ($pathPartsList as $part) {
			if ($part == '.') {
				continue;
			} elseif ($part == '..') {
				if (count($finalPathPartsList) > 0) {
					array_pop($finalPathPartsList);
				} else {
					throw new \Exception("Cannot process .. - path too short");
				}
			} else {
				$finalPathPartsList[] = $part;
			}
		}

		return join('/', $finalPathPartsList);
	}



	public function isComplex (): bool {
		return (strpos($this->pathString, '/') !== false or strpos($this->pathString, '\\') !== false);
	}



	public function __toString () {
		return $this->pathString;
	}
}
