<?
namespace S5\IO;

use S5\System;



/**
 * @phpstan-consistent-constructor
 */
class Directory extends Item {
	public function __construct (string $path, array $params = []) {
		parent::construct($path, $params);
	}



	protected function setPath (string $path) {
		parent::setPath("$path/");
	}



	public static function initTemp (string|false $dirPath = false, string $prefix = ''): Directory {
		if (!$dirPath) {
			$dirPath = sys_get_temp_dir();
		}
		@$path = tempnam($dirPath, $prefix);
		if (!$path) {
			throw new \Exception("Не удалось создать временную папку внутри [$dirPath] с префиксом [$prefix]");
		}
		unlink($path);
		$dir = new static($path);
		$dir->create();
		return $dir;
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
			throw new \Exception("Уже существует файл с таким путём: $path");
		}
		if (@!mkdir($path, $this->params['dirs_mod'], true)) {
			throw new \Exception("Не удалось создать папку $path");
		}
		return true;
	}



	public function tryCreate (): bool {
		if (!$this->isExists()) {
			return $this->create();
		}
		return false;
	}



	public function rename (string $name, $isOverwrite = false): bool {
		if (!$this->isExists()) {
			return false;
		}

		$currentPath = $this->getPath();

		$name = new Path($name);

		if ($name->isComplex()) {
			$newPath = ($name == $currentPath ? $currentPath : $name);
		} else {
			$parentDir = new Directory(dirname($currentPath));
			if (!$parentDir->isExists()) {
				$parentDir->create();
			}
			$newPath = "$parentDir/$name";
		}

		if (file_exists($newPath)) {
			if (!$isOverwrite) {
				throw new \Exception("Уже существует файл с таким путём: ".$currentPath);
			}
			unlink($newPath);
		}

		$r = rename($currentPath, $newPath);
		if (!$r) {
			throw new \Exception("Не удалось переименовать \"$this\" в \"$name\"");
		}

		$this->setPath((string)(new Path($newPath)));
		return true;
	}



	public function copy (string $pathString, bool $isOverwrite = false) {
		$targetDir = new static($pathString);

		if ($this->getPath() == $targetDir->getPath()) {
			throw new \InvalidArgumentException("Новый путь совпадает с текущим: $pathString");
		}

		$copy = function (Directory $sourceDir, Directory $targetDir) use (&$copy, $isOverwrite) {
			if (!$targetDir->isExists()) {
				$targetDir->create();
			} else {
				if (!$targetDir->isWritable()) {
					throw new \Exception("Папка недоступна для записи: $targetDir");
				}
			}
			$itemsList = $sourceDir->getItemsList();
			/** @var array<int, Directory[]> */
			$nestedDirsList = [];
			foreach ($itemsList as $item) {
				if ($item->isFile()) {
					/** @var File $item */
					$item->copy($targetDir, $isOverwrite);
				}
			}
			foreach ($itemsList as $item) {
				if ($item->isDirectory()) {
					/** @var Directory $item */
					$sourceNestedDir = new static($sourceDir . $item->getName());
					$targetNestedDir = new static($targetDir . $item->getName());
					$targetNestedDir->tryCreate();
					$nestedDirsList[] = [$sourceNestedDir, $targetNestedDir];
				}
			}
			foreach ($nestedDirsList as $e) {
				$copy($e[0], $e[1]);
			}
		};

		$copy($this, $targetDir);
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



	public function chown (string|int $user = '', string|int $group = '', bool $isRecursive = false) {
		parent::baseChown($user, $group, $isRecursive);
	}

	public function chmod (string|int $mode, bool $isRecursive = false) {
		parent::baseChmod($mode, $isRecursive);
	}



	public function getItemsList (int $order = SCANDIR_SORT_ASCENDING): ItemsList {
		if (!is_dir($this->getPath())) {
			throw new \InvalidArgumentException("Папка не найдена: ".$this->getPath());
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
	 * @param $type   'd', 'f', false
	 */
	public function getFirstItem (string|false $type = false): ?Item {
		$firstItem = null;
		$dh        = $this->_open();

		while (false !== ($name = readdir($dh))) {
			if ($name != '.' and $name != '..') {
				$path = $this->getPath()."/$name";
				if     (is_dir($path))  $foundType = 'd';
				elseif (is_file($path)) $foundType = 'f';
				else                    $foundType = false;
				if ($type == 'd' and $foundType == 'd') {
					$firstItem = new Directory($path);
					break;
				} elseif ($type == 'f' and $foundType == 'f') {
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



	/**
	 * Удаление старых файлов.
	 *
	 * `$directory->deleteOldFilesList('7d')`
	 *
	 * Здесь 7 это количество единиц, d - тип единиц. В данном случае это 7 дней.
	 *
	 * Допустимые типы:
	 * s - секунды
	 * m - минуты
	 * h - часы
	 * d - дни
	 * w - недели
	 */
	public function deleteOldFilesList (string $olderThan) {
		preg_match('/^(\d+)([smhdw])?$/', $olderThan, $matches);

		if (!ctype_digit((string)$matches[1])) {
			throw new \InvalidArgumentException("Неверно указанное число: [$matches[1]]");
		}
		$t = (int)$matches[1];

		if (!isset($matches[2])) {
			$olderThan = $t;
		} else {
			switch ($matches[2]) {
				case 's': $olderThan = $t;          break;
				case 'm': $olderThan = $t * 60;     break;
				case 'h': $olderThan = $t * 3600;   break;
				case 'd': $olderThan = $t * 86400;  break;
				case 'w': $olderThan = $t * 604800; break;
				default: throw new \InvalidArgumentException("Неизвестный тип [$matches[1]]");
			}
		}

		$dh = $this->_open();

		$deleteTime = time() - $olderThan;
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



	public function walk (\Closure $callback) {
		$walk = function (Item $item) use (&$walk, $callback) {
			$callback($item);
			if ($item->isDirectory()) {
				/** @var Directory $item */
				foreach ($item->getItemsList() as $subitem) {
					$walk($subitem);
				}
			}
		};
		$walk($this);
	}



	/** @return resource Результат opendir() */
	private function _open () {
		if (!$dh = opendir($this->getPath())) {
			throw new \Exception("Папка не существует: $this");
		}
		return $dh;
	}
}
