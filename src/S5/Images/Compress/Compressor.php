<?
namespace S5\Images\Compress;
use \S5\IO\{Directory, File};


class Compressor {
	use \S5\ConstructTrait;

	protected string $lockFilePath;

	protected File $lockFile;
	protected bool $isSkipLock = false;
	protected      $compressChecker;


	public function __construct (array $params) {
		$r = $this->checkRequirements();
		if (!$r) {
			throw new \Exception("Система не соответствует требованиям:\n" . array_map(fn($e)=>"$e->errorMessage\n", $r->itemsHash));
		}

		$this->_copyConstructParams($params);

		$this->setCompressChecker();
	}



	protected function setCompressChecker () {
		$binName = (exec('which rg') ? 'rg' : 'grep');
		$this->compressChecker = fn($filePath)=>(bool)exec("$binName 's5compressed' '$filePath'");
	}



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



	public function processDirectory (string $dirPath) {
		if (!$this->isSkipLock and !$this->lock()) {
			return;
		}

		$filePathsList = $this->getFilePathsList($dirPath);

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

			$getCommandString = fn(string $commandString, string $fileName) => "cd '$fileDirPath' && timeout 200 $commandString '$fileName'";
			$runCommand       = fn(string $commandString, string $fileName) => passthru($getCommandString($commandString, $fileName));

			//jpg, png
			$fileName = basename($fullFilePath);
			if (preg_match('/\.jpe?g$/', $filePath)) {
				$commandString = 'squoosh-cli --mozjpeg \'{"quality":85, "baseline":false, "arithmetic":false, "progressive":true, "optimize_coding":true, "smoothing":0,"color_space":3, "quant_table":3, "trellis_multipass":false, "trellis_opt_zero":false, "trellis_opt_table":false, "trellis_loops":1, "auto_subsample":true, "chroma_subsample":2, "separate_chroma_quality":false, "chroma_quality":75}\'';
				echo "Оптимизация через mozjpeg\n";
			} elseif (preg_match('/\.png$/', $filePath)) {
				$commandString = 'squoosh-cli --oxipng \'{"level":6}\'';
				echo "Оптимизация через oxipng\n";
			}
			$runCommand($commandString, $fileName);

			//webp, avif
			$originalFileSize = filesize($fullFilePath);
			static $commandStringsList = [
				'webp' => 'squoosh-cli --webp \'{"quality":85, "method":4, "sns_strength":50, "filter_strength":60, "filter_type":1, "segments":4, "pass":1, "show_compressed":0, "preprocessing":0, "autofilter":0, "partition_limit":0, "alpha_compression":1, "alpha_filtering":1, "alpha_quality":100, "lossless":0}\'',
				'avif' => 'squoosh-cli --avif \'{"speed":2}\'',
			];
			foreach ($commandStringsList as $ext => $commandString) {
				echo "Создание $ext\n";
				$newFilePath = "$fullFilePath.$ext";
				$newFileName = basename($newFilePath);
				copy($fullFilePath, $newFilePath);
				$runCommand($commandString, $newFileName);
				$newFileSize = filesize($newFilePath);
				if ($newFileName >= $originalFileSize) {
					echo "$ext по размеру больше оригинала, удаляем";
					unlink($newFilePath);
				}
			}

			//comment
			passthru("exiftool -q -overwrite_original_in_place -comment='s5compressed' '$fullFilePath'");
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

		foreach (['node', 'exiftool', 'squoosh'] as $name) {
			$r = exec("which " . ($name == 'squoosh' ? 'squoosh-cli' : $name));
			if (!$r) {
				$result->itemsHash[$name]->setInvalid("$name не установлен");
			}
		}

		if ($result->itemsHash['node']->isOK and strpos($r, 'v16') !== 0) {
			$result->itemsHash['node16']->setInvalid('node должен быть версии 16');
		}

		return $result;
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
