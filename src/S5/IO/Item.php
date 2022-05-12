<?
namespace S5\IO;

abstract class Item implements IStringablePath {
	protected string $path;

	protected array $params = [
		'dirs_mod' => 0777,
	];



	/**
	 * Constructor.
	 *
	 * @param string $path Путь к сущности
	 * @param array  $params
	 */
	public function __construct ($path, $params = false) {
		$this->setPath($path);
		$this->setParams($params);
	}

	/**
	 * @param array|false $params
	 */
	protected function setParams ($params) {
		if (is_array($params)) {
			$this->params = array_merge($this->params, $params);
		}
	}



	/**
	 * @param string $path
	 */
	protected function setPath ($path) {
		if (preg_match('/[+*?]/', $path)) {
			throw new \InvalidArgumentException("Disallowed characters in the path: [$path]");
		}
		$this->path = $this->initPath($path);
	}



	public function getName (): string {
		return basename($this->path);
	}

	public function getPath (): string {
		return $this->path;
	}

	public function getDirPath (): string {
		return dirname($this->path);
	}



	public function getDirectory (): Directory {
		return $this->initDirectory($this->getDirPath());
	}



	public abstract function isExists (): bool;

	public abstract function isFile (): bool;

	public abstract function isDirectory (): bool;

	public function isReadable (): bool {
		return is_readable($this->path);
	}

	public function isWritable (): bool {
		return is_writable($this->path);
	}



	public abstract function delete ();



	public function getAtime (): \DateTime {
		return new \DateTime('@'.fileatime($this->path));
	}

	public function getCtime (): \DateTime {
		return new \DateTime('@'.filectime($this->path));
	}

	public function getMtime (): \DateTime {
		return new \DateTime('@'.filemtime($this->path));
	}



	/**
	 * @param  string $documentRoot
	 * @return string
	 */
	public function getRelativeUrl ($documentRoot) {
		$url = str_replace($documentRoot, '', $this->getPath());
		if (strpos($url, '/') !== 0) {
			$url = '/'.$url;
		}
		return $url;
	}



	/**
	 * Возвращает новый объект файла.
	 *
	 * Можно переопределять в наследниках, если нужно инициализировать объект другого класса.
	 *
	 * @param  string      $path
	 * @param  array|false $params
	 * @return File
	 */
	protected function initFile ($path, $params = false) {
		return new File($path, $params);
	}

	/**
	 * Возвращает новый объект директории.
	 *
	 * Можно переопределять в наследниках, если нужно инициализировать объект другого класса.
	 *
	 * @param  string      $path
	 * @param  array|false $params
	 * @return Directory
	 */
	protected function initDirectory (string $path, $params = false) {
		return new Directory($path, $params);
	}

	/**
	 * По пути определяет, объект какого типа инициализировать - файла или директории, и возвращает этот объект.
	 *
	 * Можно переопределять в наследниках, если нужно инициализировать объекты других классов.
	 * Файл/директория должны существовать - иначе определять будет не по чему.
	 * Если это не файл и не папка - возвращает файл, для простоты.
	 *
	 * @param  string      $path
	 * @param  array|false $params
	 * @return Item
	 */
	protected function initItem ($path, $params = false) {
		if (!file_exists($path)) {
			throw \Exception("Path does not exist: $path");
		}
		if (is_dir($path)) {
			return new Directory($path, $params);
		} else {
			return new File($path, $params);
		}
	}

	/**
	 * По пути определяет, объект какого типа инициализировать - файла или директории, и возвращает этот объект.
	 *
	 * Можно переопределять в наследниках, если нужно инициализировать объекты других классов.
	 * Файл/директория должны существовать - иначе определять будет не по чему.
	 * Если это не файл и не папка - возвращает файл, для простоты.
	 *
	 * @param  string      $path
	 * @param  array|false $params
	 * @return Item
	 */
	protected function initPath (string $path): Path {
		return new Path($path);
	}



	public function __toString (): string {
		return $this->getPath();
	}
}
