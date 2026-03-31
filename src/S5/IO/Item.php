<?
namespace S5\IO;

use S5\IO\Permissions\Permissions;
use S5\System;



abstract class Item {
	protected string $path;

	protected array $params = [
		'dirs_mod' => 0777,
	];



	protected function construct (string $path, array $params = []) {
		$this->setPath($path);
		$this->setParams($params);
	}

	protected function setParams (array $params) {
		$this->params = array_merge($this->params, $params);
	}



	protected function setPath (string $path) {
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



	public function isExists (): bool {
		return file_exists($this->getPath());
	}

	public abstract function isFile (): bool;

	public abstract function isDirectory (): bool;

	public function isSymlink () {
		return is_link($this);
	}

	public function isReadable (): bool {
		return is_readable($this->path);
	}

	public function isWritable (): bool {
		return is_writable($this->path);
	}



	public abstract function delete ();



	protected function baseChown (string|int $user = '', string|int $group = '', bool $isRecursive = false) {
		if (!$isRecursive) {
			if ($user)  chown($this, $user);
			if ($group) chgrp($this, $group);
		} else {
			if ($user and $group) {
				$userGroupString = "$user:$group";
				$recursiveFlag   = (!$isRecursive ? '' : '-R');
				System::exec("chown $recursiveFlag $userGroupString '$this'");
			}
		}
	}

	protected function baseChmod (string|int $mode, bool $isRecursive) {
		if (!$isRecursive and is_numeric($mode)) {
			chmod($this, $mode);
		} else {
			$recursiveFlag = (!$isRecursive ? '' : '-R');
			System::exec("chmod $recursiveFlag $mode '$this'");
		}
	}



	/**
	 * Возвращает восьмеричное значение прав доступа, типа 0777, 0755 итп.
	 */
	public function getPermissionsNumber (): int|false {
		return (fileperms($this) & 0777);
	}

	public function getPermissions (): Permissions {
		return new Permissions($this->getPermissionsNumber());
	}

	/**
	 * Строка прав: типа "rwxrw-r--".
	 */
	public function getPermissionsString (): string {
		$string   = '';
		$number   = $this->getPermissionsNumber();
		$bitsList = [0400,0200,0100, 0040,0020,0010, 0004,0002,0001];
		$rwxList  = ['r', 'w', 'x'];
		$rwxIndex = 0;
		foreach ($bitsList as $bit) {
			$string .= ($number & $bit ? $rwxList[$rwxIndex] : '-');
			if (++$rwxIndex == 3) {
				$rwxIndex = 0;
			}
		}
		return $string;
	}



	public function symlink (string $path) {
		if (!$this->isExists()) {
			throw new \Exception("Текущий файл не существует: $this");
		}

		(new File($path))->getDirectory()->tryCreate();

		try {
			symlink($this, $path);
		} catch (\Throwable $th) {
			throw new \Exception(
				"Не удалось создать символическую ссылку:\n" .
				"$this\n"      .
				"->\n"         .
				"$path\n"      .
				"----------\n" .
				$th->getMessage()
			);
		}
	}



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



	public function setTime (int|string|null $mtime = null, int|string|null $atime = null): bool {
		if (file_exists($this->path)) {
			static $varNamesList = ['mtime', 'atime'];
			foreach ($varNamesList as $varName) {
				if (is_string($$varName) and !ctype_digit("$$varName")) {
					$$varName = strtotime($$varName);
					if (!$$varName) {
						throw new \InvalidArgumentException("Неверный $varName: {$$varName}");
					}
				}
			}
			return touch($this->path, $mtime, $atime);
		} else {
			return false;
		}
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
	 */
	protected function initFile (string $path, $params = []): File {
		return new File($path, $params);
	}

	/**
	 * Возвращает новый объект директории.
	 *
	 * Можно переопределять в наследниках, если нужно инициализировать объект другого класса.
	 */
	protected function initDirectory (string $path, $params = []): Directory {
		return new Directory($path, $params);
	}

	/**
	 * По пути определяет, объект какого типа инициализировать - файла или директории, и возвращает этот объект.
	 *
	 * Можно переопределять в наследниках, если нужно инициализировать объекты других классов.
	 * Файл/директория должны существовать - иначе определять будет не по чему.
	 * Если это не файл и не папка - возвращает файл, для простоты.
	 */
	protected function initItem (string $path, $params = []): Item {
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
