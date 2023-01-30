<?
namespace S5\IO;

class Directory extends Item {
	/**
	 * @param string $path
	 */
	protected function setPath ($path) {
		parent::setPath("$path/");
	}



	/**
	 * @param  string|false $dirPath
	 * @param  string       $prefix
	 * @return Directory
	 */
	public static function initTemp ($dirPath = false, string $prefix = '') {
		if (!$dirPath) {
			$dirPath = sys_get_temp_dir();
		}
		@$path = tempnam($dirPath, $prefix);
		if (!$path) {
			throw new \Exception("Can't create temp dir");
		}
		unlink($path);
		$dir = new static($path);
		$dir->create();
		return $dir;
	}



	public function isExists (): bool {
		return is_dir($this->getPath());
	}

	public function isFile (): bool {
		return false;
	}

	public function isDirectory (): bool {
		return true;
	}



	public function create (): bool {
		$path = $this->getPath();

		if (is_dir($path)) {
			return false;
		}
		if (is_file($path)) {
			throw new \Exception("File with the same path already exists: $path");
		}
		if (!mkdir($path, $this->params['dirs_mod'], true)) {
			throw new \Exception("Can't create directory $path");
		}
		return true;
	}



	/**
	 * @param  string|false $name
	 * @param  bool|false   $isOverwrite
	 * @return bool
	 */
	public function rename ($name, $isOverwrite = false) {
		$currentPath = $this->getPath();

		if (!$this->isExists()) {
			return false;
		}

		$name = new Path($name);

		if ($name->isComplex()) {
			if ($name != $currentPath) {
				$newPath = $name;
			}
		} else {
			$parentDir = new Directory(dirname($currentPath));
			if (!$parentDir->isExists()) {
				$parentDir->create();
			}
			$newPath = "$parentDir/$name";
		}

		if (file_exists($newPath) and !$isOverwrite) {
			throw new \Exception("File with the same path already exists: ".$currentPath);
		}

		$r = rename($currentPath, $newPath);
		if (!$r) {
			throw new \Exception("Can't rename \"$this\" to \"$name\"");
		}

		$this->setPath((string)(new Path($newPath)));
		return true;
	}



	/**
	 * Удаление директории.
	 */
	public function delete () {
		if ($this->isExists()) {
			$this->clear();
			rmdir($this->getPath());
		}
	}



	/**
	 * Очистка директории от содержимого.
	 */
	public function clear () {
		if (!$this->isExists()) {
			return;
		}
		//Получение списка файлов и подкаталогов.
		$itemsList = array_diff(scandir($this->getPath()), ['.', '..']);
		foreach ($itemsList as $e) {
			$fullItemPath = $this->getPath()."/$e";
			if (is_dir($fullItemPath)) {
				(new static($fullItemPath))->delete();
			} else {
				unlink($fullItemPath);
			}
		}
	}



	public function getItemsList (int $order = SCANDIR_SORT_ASCENDING): ItemsList {
		if (!is_dir($this->getPath())) {
			throw new \InvalidArgumentException("Path not found: ".$this->getPath());
		}
		$list = new ItemsList();
		foreach (scandir($this->getPath(), $order) as $name) {
			if ($name == '.' or $name == '..') continue;
			$path = $this->getPath()."/$name";
			$list->append($this->initItem($path));
		}
		return $list;
	}



	/**
	 * Первая найденная папка или файл - или null, если ничего не найдено.
	 *
	 * @param  string|false $type   'd', 'f', false
	 * @return Item|null
	 */
	public function getFirstItem ($type = false) {
		$firstItem = null;
		$dh        = $this->_open();

		while (false !== ($name = readdir($dh))) {
			if ($name != '.' and $name != '..') {
				$path = $this->getPath()."/$name";
				if     (is_dir($path))  $foundType = 'd';
				elseif (is_file($path)) $foundType = 'f';
				else                    $foundType = false;
				if ($type = 'd' and $foundType = 'd') {
					$firstItem = new Directory($path);
					break;
				} elseif ($type = 'f' and $foundType = 'f') {
					$firstItem = new File($path);
					break;
				} elseif ($type == false) {
					$firstItem = ($foundType == 'd')
						? new Directory($path)
						: new File($path);
					break;
				}
			}
		}
		closedir($dh);

		return $firstItem;
	}



	public function deleteOldFilesList (string $olderThen) {
		preg_match('/^(\d+)([smhdw])?$/', $olderThen, $matches);

		$t = $matches[1];
		if (!ctype_digit((string)$t)) {
			throw new \InvalidArgumentException("Invalid numerid part: [$t]");
		}

		if (!isset($matches[2])) {
			$olderThen = $t;
		} else {
			switch ($matches[2]) {
				case 's': $olderThen = $t;          break;
				case 'm': $olderThen = $t * 60;     break;
				case 'h': $olderThen = $t * 3600;   break;
				case 'd': $olderThen = $t * 86400;  break;
				case 'w': $olderThen = $t * 604800; break;
				default: throw new \InvalidArgumentException("Unknown modifier [$matches[1]]");
			}
		}

		$dh = $this->_open();

		$deleteTime = time() - $olderThen;
		while ($fileName = readdir($dh)) {
			if ($fileName == '.' or $fileName == '..') {
				continue;
			}
			$filePath = "$this/$fileName";
			if (is_file($filePath) and filemtime($filePath) <= $deleteTime) {
				unlink($filePath);
			}
		}

		closedir($dh);
	}



	private function _open () {
		if (!$dh = opendir($this->getPath())) {
			throw new \Exception("This directory doesn't exist: $this");
		}
		return $dh;
	}
}
