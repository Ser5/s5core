<?
namespace S5\IO;

class File extends Item {
	/** @var resource */
	private $_handle   = false;

	/** @var bool */
	private $_isLocked = false;



	/**
	 * Constructor.
	 *
	 * @param string|resource $file Путь к файлу или ресурс файла
	 * @param array|false     $params
	 */
	public function __construct ($file, $params = false) {
		if (is_resource($file)) {
			$resourceType = get_resource_type($file);
			if ($resourceType != 'stream') {
				throw new \InvalidArgumentException("Invalid resource type: $resourceType");
			}
			$meta = stream_get_meta_data($file);
			$path = $meta['uri'];
			$this->_handle = $file;
		} else {
			$path = $file;
		}
		parent::__construct($path, $params);
	}



	public static function initTemp ($dirPath = false, $prefix = ''): File {
		if (!$dirPath) {
			$dirPath = sys_get_temp_dir();
		}
		$filePath = tempnam($dirPath, $prefix);
		if (!$filePath) {
			throw new \Exception("Can't create temp file");
		}
		return new static($filePath);
	}



	public function getExtension (): string {
		return pathinfo($this->getPath(), PATHINFO_EXTENSION);
	}



	public function putContents (string $content, int $flags = 0) {
		$this->_createDir();
		if (@file_put_contents($this->getPath(), $content, $flags) === false) {
			throw new \Exception("Can't write to ".$this->getPath());
		}
	}



	public function getContents (): string {
		@$content = file_get_contents($this->getPath());
		if ($content === false) {
			throw new \Exception("Can't read ".$this->getPath());
		}
		return $content;
	}



	public function touch () {
		$this->_createDir();
		touch($this->getPath());
	}



	public function isExists (): bool {
		return is_file($this->getPath());
	}

	public function isFile (): bool {
		return true;
	}

	public function isDirectory (): bool {
		return false;
	}



	public function delete () {
		if ($this->isExists()) {
			unlink($this->getPath());
		}
	}



	/**
	 * Переименование или перемещение файла.
	 *
	 * Для переименования:
	 * $f->rename('newname.txt');
	 *
	 * Для перемещения:
	 * $f->rename('/other/directory/newname.txt');
	 *
	 * При перемещении отсутствующие папки создаются автоматически.
	 *
	 * @param string $name
	 */
	public function rename ($name) {
		$r = true;
		if ($this->isExists()) {
			$testPath = new Path($name);
			if (!$testPath->isComplex()) {
				if ($name != $this->getName()) {
					$dir     = $this->getDirectory();
					$newPath = "$dir/$name";
					$r       = rename($this->getPath(), $newPath);
				}
			} else {
				$newPath   = $name;
				$targetDir = new Directory(dirname($newPath));
				if (!$targetDir->isExists()) {
					$targetDir->create();
				}
				$r = rename($this->getPath(), $newPath);
			}
		}
		if (!$r) {
			throw new \Exception("Can't rename \"$this\" to \"$name\"");
		}
		$this->setPath($newPath);
	}



	/**
	 * Перемещение файла с сохранением имени.
	 *
	 * @param string $dirPath
	 */
	public function move ($dirPath) {
		$thisDir   = $this->getDirectory();   //Получаем объекты директорий,
		$targetDir = new Directory($dirPath); //чтобы сравнивать нормализованные пути
		if ($targetDir->getPath() != $thisDir->getPath()) {
			$targetPath = $targetDir.'/'.$this->getName();
			$this->rename($targetPath);
		}
	}



	/**
	 * @param  string $openMode
	 * @return resource
	 */
	public function open ($openMode) {
		if ($this->_handle === false) {
			$this->_createDir();
			$this->_handle = fopen($this->getPath(), $openMode);
			if (!$this->_handle) {
				throw new \Exception("Can't open ".$this->getPath());
			}
		}
		return $this->_handle;
	}



	public function close () {
		if ($this->_handle !== false and is_resource($this->_handle)) {
			fclose($this->_handle);
			$this->_handle = false;
		}
	}



	public function lock (string $openMode, int $lockOperation, int &$wouldBlock = 0) {
		if ($lockOperation == LOCK_UN) {
			$this->unlock();
			return;
		}
		if ($this->_isLocked) {
			return;
		}
		$this->_createDir();
		$this->open($openMode);
		if (flock($this->_handle, $lockOperation, $wouldBlock)) {
			$this->_isLocked = true;
			return true;
		} else {
			return false;
		}
	}



	public function unlock () {
		if (!$this->_isLocked) {
			return;
		}
		if (!flock($this->_handle, LOCK_UN)) {
			throw new \Exception("Can't unlock ".$this->getPath());
		}
		$this->_isLocked = false;
	}



	public function isLocked () {
		return $this->_isLocked;
	}



	private function _createDir () {
		$d = $this->initDirectory(
			$this->getDirPath(),
			array('dirs_mod', $this->params['dirs_mod'])
		);
		$d->create();
	}



	public function __destruct () {
		$this->unlock();
		$this->close();
	}
}
