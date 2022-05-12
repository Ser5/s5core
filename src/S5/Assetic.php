<?
namespace S5;
use S5\IO\Directory;


class Assetic {
	const AM_ORIGINALS    = 'originals';
	const AM_ORIGINALS_TS = 'originals_ts';
	const AM_MINIFIED     = 'minified';

	const WHAT_JS  = 'js';
	const WHAT_CSS = 'css';

	protected string $assetsMode;
	protected string $rootPath;
	protected string $npmPath;
	protected string $lastModified;
	protected string $assetUrls;
	protected string $jsMinDirUrl;
	protected string $cssMinDirUrl;

	protected string $jsMinDirPath;
	protected string $cssMinDirPath;
	protected string $jsMinTempDirPath;
	protected string $cssMinTempDirPath;


	public function __construct (array $params) {
		foreach ($params as $k => $v) {
			$this->{$k} = $v;
		}
		//Постоянные папки
		$this->jsMinDirPath      = "{$this->rootPath}/{$this->jsMinDirUrl}";
		$this->cssMinDirPath     = "{$this->rootPath}/{$this->cssMinDirUrl}";
		//Временные папки
		$this->_jsMinTempDirUrl  = $this->jsMinDirUrl.'.temp';
		$this->_cssMinTempDirUrl = $this->cssMinDirUrl.'.temp';
		$this->jsMinTempDirPath  = $this->jsMinDirPath.'.temp';
		$this->cssMinTempDirPath = $this->cssMinDirPath.'.temp';
		//Добавляем хвосты к урлам
		$this->jsMinDirUrl       .= '/';
		$this->cssMinDirUrl      .= '/';
		$this->_jsMinTempDirUrl  .= '/';
		$this->_cssMinTempDirUrl .= '/';
	}



	/**
	 * Генерация минифицированных ассетов.
	 *
	 * $params:
	 * - what - какой-то кривой параметр. Работает только в варианте с true.
	 *
	 * @param array $params
	 */
	public function generate (array $params = []) {
		$p = $params + [
			'what' => true,
		];

		//Здесь будут лежать окончательные версии css/js
		$jsMinDir  = new Directory($this->jsMinDirPath);
		$cssMinDir = new Directory($this->cssMinDirPath);

		//Но сначала положим их во временные папки
		$jsMinTempDir  = new Directory($this->jsMinTempDirPath);
		$cssMinTempDir = new Directory($this->cssMinTempDirPath);
		$jsMinTempDir->delete();
		$cssMinTempDir->delete();
		$jsMinTempDir->create();
		$cssMinTempDir->create();

		//Обрабатываем файлы и складываем их во временные папки
		//$this->assetUrls выглядит как-то так:
		//[
		//   'js' => [                 $type
		//      'external_js'  => []   $code => $urlsList
		//      'local_js'     => []
		//   ],
		//   'css' => [
		//      'external_css' => []
		//      'local_css'    => []
		//   ],
		//]
		foreach ($this->assetUrls as $type => $data) {
			if ($type != 'js' and $type != 'css') {
				throw new \InvalidArgumentException("Unknown type [$type]");
			}
			$a = 1;
			foreach ($data as $code => $urlsList) {
				if ($p['what'] === true or (isset($p['what'][$type]) and in_array($code, $p['what'][$type]))) {
					$commandString    = "cd $this->npmPath && ";
					$inputFilesString = '';
					foreach ($this->_expandUrlsList($urlsList) as $url) {
						$inputFilesString .= "-i '$url' ";
					}
					if ($type == 'js') {
						$filePath       = "$this->rootPath/{$this->jsMinDirUrl}/{$a}_$code.js";
						$tempFilePath   = "$this->rootPath/{$this->_jsMinTempDirUrl}/{$a}_$code.js";
						$commandString .= "npx gulp js  $inputFilesString -o {$this->_jsMinTempDirUrl}/{$a}_$code.js";
					} elseif ($type == 'css') {
						$filePath       = "$this->rootPath/{$this->cssMinDirUrl}/{$a}_$code.css";
						$tempFilePath   = "$this->rootPath/{$this->_cssMinTempDirUrl}/{$a}_$code.css";
						$commandString .= "npx gulp css $inputFilesString -o {$this->_cssMinTempDirUrl}/{$a}_$code.css";
					}
					passthru($commandString);
					//Так как ассет может сжиматься не весь, а отдельными наборами,
					//то менять старую папку на новую нельзя - тогда там окажутся не все ассеты.
					//Надо менять пофайликово.
					rename($tempFilePath, $filePath);
				}
				$a++;
			}
		}

		//Удаляем временные папки
		$jsMinTempDir->delete();
		$cssMinTempDir->delete();
	}



	public function show (string $what) {
		if ($what == 'js') {
			$minDirPath = $this->jsMinDirPath;
			$minDirUrl  = $this->jsMinDirUrl;
		} elseif ($what == 'css') {
			$minDirPath = $this->cssMinDirPath;
			$minDirUrl  = $this->cssMinDirUrl;
		} else {
			throw new \InvalidArgumentException("Unknown value: [$what]");
		}

		if ($this->assetsMode == 'minified') {
			//Минифицированные файлы
			$minDir = new Directory($minDirPath);
			if ($minDir->isExists() and count($minFilesList = $minDir->getItemsList()->sort('name'))) {
				foreach ($minFilesList as $minFile) {
					$url = $minDirUrl.$minFile->getName().'?t='.$this->lastModified;
					echo $what == 'js'
						? '<script src="'.$url.'"></script>'."\n"
						: '<link rel="stylesheet" href="'.$url.'">'."\n";
				}
			}
		} elseif ($this->assetsMode == 'originals' or $this->assetsMode == 'originals_ts') {
			//Обычные файлы
			foreach ($this->_getUrlsList($what) as $url) {
				if ($this->assetsMode == 'originals_ts') {
					$url .= '?t='.time();
				}
				echo $what == 'js'
					? '<script src="'.$url.'"></script>'."\n"
					: '<link rel="stylesheet" href="'.$url.'">'."\n";
			}
		} elseif ($this->assetsMode == 'concatenated') {
			//JS или CSS одним файлом
			echo $what == 'js'
				? '<script src="/assets/scripts/concatenated.php?t='.time().'"></script>'."\n"
				: '<link rel="stylesheet" href="/assets/styles/concatenated.php?t='.time().'">'."\n";
		} else {
			throw new \InvalidArgumentException("Invalid assets mode: [$this->assetsMode]");
		}
	}



	public function showConcatenated (string $what) {
		$contentType = ($what == 'js') ? 'text/javascript' : 'text/css';
		header("Content-type: $contentType; charset=UTF-8");

		ob_start("ob_gzhandler");
		foreach ($this->_getUrlsList($what) as $url) {
			readfile("$this->rootPath/$url");
			echo "\n";
		}
		ob_end_flush();
	}



	/**
	 * Возвращает все ссылки на файлы js/css, различая директории и файлы.
	 *
	 * Если в записи из настроек указано что-то типа "/assets/styles/common.css",
	 * то эта запись в выходном массиве и вернётся.
	 *
	 * Если же там указано "/assets/styles/components/", со слэшем на конце,
	 * то в выходной массив будут добавлены все файлы, лежащие в этой директории.
	 *
	 * @param  string $what js/css
	 * @return array
	 */
	protected function _getUrlsList (string $what): array {
		$list = [];
		foreach ($this->assetUrls[$what] as $urlsList) {
			$list = array_merge($list, $this->_expandUrlsList($urlsList));
		}
		return $list;
	}



	protected function _expandUrlsList (array $urlsList): array {
		$list = [];
		foreach ($urlsList as $url) {
			if ($url[strlen($url)-1] == '/') {
				//Указана директория - вытаскиваем из неё все файлы
				$dir = new Directory("$this->rootPath/$url");
				foreach ($dir->getItemsList() as $file) {
					$list[] = $url .'/'. $file->getName();
				}
			} else {
				//Указан один файл - используем его
				$list[] = $url;
			}
		}
		return $list;
	}
}
