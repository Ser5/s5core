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
					throw new \Exception("Не удалось обработать .. - путь слишком короткий");
				}
			} else {
				$finalPathPartsList[] = $part;
			}
		}

		return join('/', $finalPathPartsList);
	}



	/**
	 * Определяет, является ли путь сложным.
	 *
	 * Возвращает true, если путь состоит из двух частей или более - разделённых символами "/" или "\".
	 *
	 * Простые пути:
	 * - dirname
	 * - /dirname/
	 * - filename.txt
	 *
	 * Сложные пути:
	 * - dir/subdir/
	 * - dir/file.txt
	 */
	public function isComplex (): bool {
		$pathString = trim($this->pathString, '/\\');
		return (strpos($pathString, '/') !== false or strpos($pathString, '\\') !== false);
	}



	public function isEndsWithSlash () {
		return preg_match('~[/\\\\]$~ui', $this->pathString);
	}



	/**
	 * Возвращает тип в зависимости от наличия в пути папок и файла.
	 *
	 * - file.txt     - simple_file
	 * - dir/         - simple_dir
	 * - dir/file.txt - complex_file
	 * - dir/subdir/  - complex_dir
	 */
	public function getComplexityType (): string {
		$type =
			($this->isComplex() ? 'complex' : 'simple') .
			'_' .
			($this->isEndsWithSlash() ? 'dir' : 'file')
		;

		return $type;
	}



	public function __toString (): string {
		return $this->pathString;
	}
}
