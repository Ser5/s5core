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



	/**
	 * Запись данных в файл для дальнейшего подключения через `$data = require $filePath`.
	 *
	 * Допустим, есть у нас данные:
	 * ```
	 * [
	 *    'a' => 1,
	 *    'b' => 2,
	 * ]
	 * ```
	 *
	 * Этот метод запишет в файл следующее:
	 * ```
	 * <?
	 * return array(
	 *    'a' => 1,
	 *    'b' => 2,
	 * );
	 * ```
	 *
	 * Далее это добро можно считать через простой `$data = require $filePath`;
	 */
	public function putPhpReturn ($data) {
		$this->putContents("<?\nreturn ".var_export($data,1).";\n");
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
	 * Для перемещения с новым названием:
	 * $f->rename('/other/directory/newname.txt');
	 *
	 * Для перемещения с сохранением названия (обязателен слэш на конце):
	 * $f->rename('/other/directory/');
	 *
	 * При перемещении отсутствующие папки создаются автоматически.
	 */
	public function rename ($name) {
		return $this->renameOrCopy($name, 'rename');
	}



	/**
	 * Перемещение файла с сохранением имени.
	 */
	public function move ($dirPath) {
		$this->rename("$dirPath/");
	}



	/**
	 * Копирование файла.
	 *
	 * Копирование в ту же папку с новым названием:
	 * $f->copy('file_copy.txt');
	 *
	 * Для копирования в другую папку с новым названием:
	 * $f->copy('/other/directory/file_copy.txt');
	 *
	 * Для копирования в другую папку с сохранением названия (обязателен слэш на конце):
	 * $f->copy('/other/directory/');
	 *
	 * При копировании отсутствующие папки создаются автоматически.
	 */
	public function copy ($dest) {
		$this->renameOrCopy($dest, 'copy');
	}



	protected function renameOrCopy ($name, $mode) {
		$r = true;
		if ($this->isExists()) {
			$functionName = ($mode == 'rename') ? 'rename' : 'copy';
			$type         = (new Path($name))->getComplexityType();
			switch ($type) {
				//Переименование/копирование в эту же папку с другим названием файла
				case 'simple_file':
					if ($this->getName() != $name) {
						$dir     = $this->getDirectory();
						$newPath = "$dir/$name";
						$r       = $functionName($this->getPath(), $newPath);
					}
				break;
				//Переименование/копирование в другую папку с новым названием файла
				case 'complex_file':
					$newPath    = $name;
					$targetFile = new File($newPath);
					if ($this->getPath() != $targetFile->getPath()) {
						$targetDir = new Directory(dirname($newPath));
						if (!$targetDir->isExists()) {
							$targetDir->create();
						}
						$r = $functionName($this->getPath(), $newPath);
					}
				break;
				//Перемещение/копирование в другую папку с сохранением названия файла
				case 'simple_dir':
				case 'complex_dir':
					$thisDir   = $this->getDirectory(); //Получаем объекты директорий,
					$targetDir = new Directory($name);  //чтобы сравнивать нормализованные пути
					if ($thisDir->getPath() != $targetDir->getPath()) {
						if (!$targetDir->isExists()) {
							$targetDir->create();
						}
						$newPath = $name . $this->getName();
						$r = $functionName($this->getPath(), $newPath);
					}
				break;
			}
		}

		if (!$r) {
			$actionMessage = ($mode == 'rename') ? 'переименовать' : 'скопировать';
			throw new \Exception("Не удалось $actionMessage \"$this\" в \"$name\"");
		}

		if ($mode == 'rename') {
			$this->setPath($newPath);
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
