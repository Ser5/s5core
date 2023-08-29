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
	 * @param string      $path Путь к сущности
	 * @param array|false $params
	 */
	protected function construct ($path, $params = false) {
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
			throw new \InvalidArgumentException("Путь содержит недопустимые символы: [$path]");
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



	/**
	 * Время последнего доступа.
	 * @return int|false
	 */
	public function getAtime () {
		return fileatime($this->path);
	}

	/**
	 * Время последнего изменения свойств.
	 * @return int|false
	 */
	public function getCtime () {
		return filectime($this->path);
	}

	/**
	 * Время последнего изменения содержимого.
	 * @return int|false
	 */
	public function getMtime () {
		return filemtime($this->path);
	}



	public function getMtimeDiff ($file): int {
		$thisMtimeTs = (int)$this->getMtime();
		$fileMtimeTs = ($file instanceof Item) ? (int)$file->getMtime() : (int)filemtime($file);
		return ($thisMtimeTs - $fileMtimeTs);
	}

	public function isMtimeNewer ($file): bool {
		return ($this->getMtimeDiff($file) > 0);
	}

	public function isMtimeOlder ($file): bool {
		return ($this->getMtimeDiff($file) < 0);
	}

	public function isMtimeSame ($file): bool {
		return ($this->getMtimeDiff($file) == 0);
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
			throw new \Exception("Путь не существует: $path");
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
	 */
	protected function initPath (string $path): Path {
		return new Path($path);
	}



	public function __toString (): string {
		return $this->getPath();
	}
}
