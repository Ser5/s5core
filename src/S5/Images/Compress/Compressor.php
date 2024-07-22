<?
namespace S5\Images\Compress;
use \S5\IO\{Directory, File};


/**
 * Создаёт рядом с обычными картинками соответствующие им webp и avif.
 *
 * ```
 * $compressor = new Compressor([
 *    'lock_file_path'         => '/path/to/lock/file.lock',
 *    'compressed_mark_string' => 'optional_custom_mark',
 *    'default_dir_data' => [
 *       'path'    => '/var/www/site.com/images/',
 *       'subdirs' => [
 *          'optional',
 *          'subdirs',
 *          'list',
 *          'folders',
 *          'elements',
 *          'etc',
 *       ],
 *    ],
 * ]);
 *
 * $compressor->processDefaultDirectoriesList();
 * ```
 */
class Compressor {
	use \S5\ConstructTrait;

	protected string $lockFilePath;
	protected string $compressedMarkString = 's5compressed';
	protected array  $defaultDirData = [];

	protected File $lockFile;
	protected bool $isSkipLock = false;
	protected      $compressChecker;


	public function __construct (array $params) {
		$r = $this->checkRequirements();
		if (!$r->isOK) {
			$errorsString = '';
			foreach ($r->itemsHash as $e) {
				if (!$e->isOK) {
					$errorsString .= "$e->errorMessage\n";
				}
			}
			throw new \Exception("Система не соответствует требованиям:\n$errorsString");
		}

		$this->_copyConstructParams($params);

		$this->setCompressChecker();
	}



	/**
	 * Определяет, через что искать комментарий с признаком обработанности картинки - через grep или rg.
	 */
	protected function setCompressChecker () {
		$binName = (exec('which rg') ? 'rg' : 'grep');
		$this->compressChecker = fn($filePath)=>(bool)exec("$binName '$this->compressedMarkString' '$filePath'");
	}



	/**
	 * Обработка директорий, указанных в конструкторе.
	 */
	public function processDefaultDirectoriesList () {
		if (!$this->lock()) {
			return;
		}

		if (!isset($this->defaultDirData['path'])) {
			throw new \Exception("Не указан путь к папке по умолчанию");
		}
		if (!is_dir($this->defaultDirData['path'])) {
			throw new \Exception("Указанная папка по умолчанию не существует: [$this->defaultDirData[path]]");
		}

		$this->isSkipLock = true;
		if (@!$this->defaultDirData['subdirs']) {
			$this->processDirectory($this->defaultDirData['path']);
		} else {
			$this->processDirectoriesList($this->defaultDirData['path'], $this->defaultDirData['subdirs']);
		}
		$this->isSkipLock = false;
	}



	/**
	 * Обработка списка директорий.
	 */
	public function processDirectoriesList (string $dirPath, array $subdirNamesList) {
		if (!$this->lock()) {
			return;
		}

		$this->isSkipLock = true;
		foreach ($subdirNamesList as $subdirName) {
			$this->processDirectory("$dirPath/$subdirName");
		}
		$this->isSkipLock = false;
	}



	/**
	 * Обработка одной директории.
	 */
	public function processDirectory (string $dirPath) {
		if (!$this->isSkipLock and !$this->lock()) {
			return;
		}

		if (!is_dir($dirPath)) {
			return;
		}

		$filePathsList = $this->getFilePathsList($dirPath);

		$oxipngCommandStringGetter = fn($fileName) => "squoosh-cli --oxipng '{\"level\":6}' '$fileName'";

		$lossyCommandStringGettersList = [
			'webp' => function ($fileName) { return "cwebp -q 85 -m 6 -f 35 -pre 0 -partition_limit 0 -metadata icc -mt -short -o '$fileName.webp' -- '$fileName'"; },
			'avif' => function ($fileName) { return "npx avif --quality=78 --effort=5 --append-ext --input='$fileName'"; },
		];

		echo "Папка $dirPath\n";

		foreach ($filePathsList as $filePath) {
			$fullFilePath = "$dirPath/$filePath";
			$isCompressed = ($this->compressChecker)($fullFilePath);
			if ($isCompressed) {
				echo "$filePath сжат\n";
				continue;
			} else {
				echo "$filePath не сжат\n";
			}

			$fileDirPath = dirname($fullFilePath);

			$getCommandString = fn($commandStringGetter, string $fileName) => "cd '$fileDirPath' && timeout 200 " . $commandStringGetter($fileName);
			$runCommand       = fn($commandStringGetter, string $fileName) => passthru($getCommandString($commandStringGetter, $fileName));

			//webp и avif создаём только для jpg-файлов.
			//png не трогаем, чтобы все иконки и графику не помутить.
			$fileName = basename($fullFilePath);
			if (preg_match('/\.png$/', $fileName)) {
				//png
				echo "Оптимизация через oxipng\n";
				$runCommand($oxipngCommandStringGetter, $fileName);
			} elseif (preg_match('/\.jpe?g$/', $fileName)) {
				//webp, avif
				$originalFileSize = filesize($fullFilePath);
				foreach ($lossyCommandStringGettersList as $ext => $commandStringGetter) {
					echo "Создание $ext\n";
					//dump($getCommandString($commandStringGetter, $fileName));
					$runCommand($commandStringGetter, $fileName);
					$newFilePath = "$fullFilePath.$ext";
					$newFileSize = filesize($newFilePath);
					if ($newFileSize >= $originalFileSize) {
						echo "$ext по размеру больше оригинала, удаляем";
						unlink($newFilePath);
					}
				}
			}

			//comment
			passthru("exiftool -q -overwrite_original_in_place -comment='$this->compressedMarkString' '$fullFilePath'");
			echo "----------------------\n";
		}
	}



	protected function getFilePathsList (string $dirPath): array {
		$output = [];
		exec("cd '$dirPath' && find . \( -iname '*.jpeg' -o -iname '*.jpg' -o -iname '*.png' \) -type f", $output);
		return $output;
	}



	public function processOrphanFilesList (string $dirPath, bool $isDelete = false): array {
		static $extHash = ['webp' => true, 'avif' => true];

		$logData = [
			'is_delete' => $isDelete,
			'ext_amounts' => [
				'jpg'  => 0,
				'png'  => 0,
				'webp' => 0,
				'avif' => 0,
			],
			'orphans_size'   => 0,
			'orphans_amount' => 0,
			'deleted_amount' => 0,
		];

		$processFilesTree = function ($dir) use (&$processFilesTree, $extHash, $isDelete, &$logData) {
			foreach ($dir->getItemsList() as $e) {
				if ($e->isFile()) {
					$logData['ext_amounts'][$e->getExtension()]++;
				}
				if ($e->isDirectory()) {
					$processFilesTree($e);
				} elseif ($e->isFile() and $e->isExists()) {
					$ext = $e->getExtension();
					if (isset($extHash[$ext])) {
						//Получаем пути к файлам jpg/png, webp, avif
						$srcFilePath = preg_replace("/\\.$ext$/u", '', $e);
						$isSrcExists = is_file($srcFilePath);
						if ($ext == 'webp') {
							$webpFilePath = (string)$e;
							$avifFilePath = $srcFilePath . '.avif';
						} else {
							$avifFilePath = (string)$e;
							$webpFilePath = $srcFilePath . '.webp';
						}
						//Опциональное удаление лишних файлов
						if (!$isSrcExists) {
							$logData = $this->deleteOrphanFilesList($webpFilePath, $avifFilePath, $isDelete, $logData);
						}
					}
				}
			}
		};

		$dir = new Directory($dirPath);
		$processFilesTree($dir);

		return $logData;
	}



	protected function deleteOrphanFilesList (string $webpFilePath, string $avifFilePath, bool $isDelete, array $logData) {
		static $varNamesList = ['webpFilePath', 'avifFilePath'];

		foreach ($varNamesList as $varName) {
			if (is_file($$varName)) {
				$logData['orphans_amount']++;
				$logData['orphans_size'] += filesize($$varName);
				if ($isDelete) {
					$logData['deleted_amount']++;
					unlink($$varName);
				}
			}
		}

		return $logData;
	}



	public function showOrphansLog (array $logData) {
		$mbSize        = round($logData['orphans_size'] / 1000) / 1000;
		$formattedSize = number_format($mbSize, 3, ',', ' ');

		$nf = fn($n)=>number_format($n, 0, ',', ' ');

		echo "Файлов jpg:          {$nf($logData['ext_amounts']['jpg'])}\n";
		echo "Файлов png:          {$nf($logData['ext_amounts']['png'])}\n";
		echo "Файлов webp:         {$nf($logData['ext_amounts']['webp'])}\n";
		echo "Файлов avif:         {$nf($logData['ext_amounts']['avif'])}\n";
		echo "Повисших файлов, шт: {$nf($logData['orphans_amount'])}\n";
		echo "Повисших файлов, МБ: $formattedSize\n";

		if (!$logData['is_delete']) {
			echo "Повисшие файлы оставлены\n";
		} else {
			echo "Повисшие файлы удалены\n";
		}
	}



	protected function lock (): bool {
		$lockFile = new File($this->lockFilePath);
		$isLockSuccessful = $lockFile->lock();
		if (!$isLockSuccessful) {
			echo "Обработка уже запущена\n";
		}
		return $isLockSuccessful;
	}



	public function checkRequirements (): RequirementsResult {
		$result = new RequirementsResult();

		foreach (['node', 'exiftool', 'squoosh-cli', 'cwebp'] as $name) {
			$r = $this->_exec("which $name");
			if (!$r) {
				$result->setInvalid($name, "$name не установлен");
			}
		}

		$r = $this->_exec("npx --no-install avif --version");
		if (!preg_match('/^[\d\.]+$/', $r)) {
			$result->setInvalid('avif', "avif не установлен");
		}

		if ($result->itemsHash['node']->isOK) {
			$r = $this->_exec("node -v");
			if (strpos($r, 'v16') !== 0) {
				$result->setInvalid('node16', "node должен быть версии 16, найдена $r");
			}
		}

		return $result;
	}



	private function _exec (string $commandString): string {
		ob_start();
		$r = exec($commandString);
		ob_end_clean();
		return $r;
	}



	/**
	 * Устанавливает fnm - чтобы можно было использовать node.js версии 16, т.к. squoosh-cli требует именно её.
	 */
	public function installFnm () {
		passthru('curl -fsSL https://fnm.vercel.app/install | sudo bash -s -- --install-dir "/usr/bin/fnm" --skip-shell');
		chmod('/usr/bin/fnm',     0755);
		chmod('/usr/bin/fnm/fnm', 0755);
	}

	public function addFnmToBashrc () {
		$bashrcFilePath = '~/.bashrc';
		$bashrcFileText = file_get_contents($bashrcFilePath);
		if (strpos($bashrcFileText, '#fnm') === false) {
			$bashrcFileText .= "\n#fnm\nexport PATH=\"/usr/bin/fnm:\$PATH\"\neval \"`fnm env`\"";
		}
		file_put_contents($bashrcFilePath, $bashrcFileText);
	}

	public function useNode16 () {
		$r = exec("fnm current");
		if (strpos($r, 'not found') !== false) {
			throw new \Exception("Node.js не установлен");
		}
		if (strpos($r, 'v16') === false) {
			passthru("fnm install v16; fnm use v16");
		}
	}
}
