<?
namespace S5\Assets\Assetic;
use S5\IO\{Directory, File};

/**
 * Отображение, минификация JS, CSS.
 *
 * Типичная ситация:
 * - Есть набор внешних библиотек JS
 * - Есть собственные скрипты, предположительно, разбитые на разные файлы
 * - Есть набор внешних CSS от всяких бутстрапов, а также каких-либо библиотек JS
 * - И свои стили - предположительно, разбитые по компонентам
 *
 * Обычно скрипты и стили зависят друг от друга и должны подключаться в определённом порядке.
 * Для настроек используется структура, подобная такой:
 * ```
 * [
 *   'js' => [
 *      'external_js' => [
 *         '/scripts/libs/straustrap.js',
 *         '/scripts/libs/unreact.js',
 *      ],
 *      'local_js' => [
 *         '/scripts/GodClass.js',
 *         '/scripts/main.js',
 *      ],
 *   ],
 *   'css' => [
 *      'external_css' => [
 *         '/css/libs/straustrap.css',
 *         '/css/libs/unreact.css',
 *      ],
 *      'local_css'    => [
 *         '/css/components/',
 *         '/css/main.css',
 *      ],
 *   ],
 * ]
 * ```
 *
 * Благодаря тому, что эти настройки хранятся в PHP, файлы можно отображать
 * и из самого PHP, и передавать на минификацию в обработчик на node.js,
 * и при этом всегда обеспечивать верную последовательность отображаемых файлов.
 *
 * В примере кроме файлов указана папка `/css/components/` - в ней могут лежать
 * несколько десятков компонентов, независимых друг от друга,и потому могущих
 * подключаться в любом порядке. Чтобы не писать путь к каждому из этой кучи файлов,
 * можно указать просто папку - все файлы оттуда выберутся автоматически.
 *
 * Вероятно, CSS-файлы нужно отображать в начале HTML, а JS - в конце.
 * Для этого в нужных местах используем `getJsTagsString()` и `getCssTagsString()`.
 *
 * Для дев-сервера всё это добро надо отображать в оригинальном виде.
 * Для этого используем режимы
 * - `Assetic::ORIGINALS`:    подключение всех файлов по-отдельности
 * - `Assetic::ORIGINALS_TS`: то же, но со свежими метками времени, чтобы бразуер не кэшировал
 *
 * Чтобы подключение кучи файлов меньше тормозило, можно использовать
 * `Assetic::CONCATENATED` - подключает оригиналы файлов, налету объединяя
 * их в один - один файл для JS, один для CSS. Для этого в папках с JS/CSS
 * должен лежать файл PHP, который и будет отвечать за вывод объединённых данных.
 * Для вывода каждый из этих скриптов должен вызывать методы
 * `getConcatenatedJsCode()` и `getConcatenatedCssCode()` соответственно.
 *
 * `Assetic::MINIFIED` используется для отображения минифицированных файлов,
 * предварительно созданных через метод `generate()`.
 *
 * Режим отображения задаётся в конструкторе через поле `assetsMode`.
 *
 * Исходные папки и файлы могут лежать где угодно - путь к каждой записи всё равно
 * задаётся полностью, от DOCUMENT_ROOT веб-сервера. Минифицированные же файлы хранятся
 * лишь в двух папках - одна для JS, другая для CSS. Пути к ним задаются в конструкторе.
 *
 * Наконец, должен присутствовать скрипт node.js, принимающий пути к файлам
 * в качестве аргументов командной строки, и генерирующий минифицированные наборы.
 */
class Assetic {
	const ORIGINALS    = 'originals';
	const ORIGINALS_TS = 'originals_ts';
	const CONCATENATED = 'concatenated';
	const MINIFIED     = 'minified';

	const JS  = 'js';
	const CSS = 'css';

	protected string $assetsMode;
	protected string $dbFilePath;
	protected string $documentRootPath;
	protected string $npmPath;
	protected string $nodeCommandString = 'node minifier.js';
	protected array  $assetUrlsData;
	protected string $jsMinDirUrl;
	protected string $cssMinDirUrl;
	protected string $jsConcatenatorUrl;
	protected string $cssConcatenatorUrl;
	protected string $jsTagsTemplate  = '<script src="$url"></script>';
	protected string $cssTagsTemplate = '<link rel="stylesheet" href="$url">';

	protected string $jsMinDirPath;
	protected string $cssMinDirPath;
	protected string $jsMinTempDirPath;
	protected string $cssMinTempDirPath;

	protected string $jsMinTempDirUrl;
	protected string $cssMinTempDirUrl;

	protected array $typesDataHash;

	/**
	 * Данные о последней минификации.
	 *
	 * Лазить только через методы writeDb() и getDbData().
	 */
	protected array $dbData = [];


	/**
	 * Ctor.
	 *
	 * @param array{
	 *    assetsMode:         string,
	 *    dbFilePath:         string,
	 *    documentRootPath:   string,
	 *    npmPath:            string,
	 *    nodeCommandString:  string,
	 *    assetUrlsData:      array,
	 *    jsMinDirUrl:        string,
	 *    cssMinDirUrl:       string,
	 *    jsConcatenatorUrl:  string,
	 *    cssConcatenatorUrl: string,
	 *    jsTagsTemplate:     string,
	 *    cssTagsTemplate:    string,
	 * } $params
	 */
	public function __construct (array $params) {
		foreach ($params as $k => $v) {
			$this->{$k} = $v;
		}

		//Проверка
		$allowedAssetsModesHash = [
			static::ORIGINALS    => true,
			static::ORIGINALS_TS => true,
			static::CONCATENATED => true,
			static::MINIFIED     => true,
		];
		if (!isset($allowedAssetsModesHash[$this->assetsMode])) {
			throw new \InvalidArgumentException("Неверный assetsMode: [$this->assetsMode]");
		}

		if (!is_dir($this->documentRootPath) or !is_writable($this->documentRootPath)) {
			throw new \InvalidArgumentException("Нет доступа в корень веб-сервера: [$this->documentRootPath]");
		}

		if (!is_dir($this->npmPath)) {
			throw new \InvalidArgumentException("Нет доступа к папке npm: [$this->npmPath]");
		}
		/*if (!is_file("$this->npmPath/gulpfile.js")) {
			throw new \InvalidArgumentException("Отсутствует файл gulp: [$this->npmPath/gulpfile.js]");
		}*/

		foreach (['jsConcatenatorUrl', 'cssConcatenatorUrl'] as $fieldName) {
			if (!isset($this->$fieldName)) {
				throw new \InvalidArgumentException("Не передан $fieldName");
			}
			$this->$fieldName = $params[$fieldName];
		}

		foreach (['jsTagsTemplate', 'cssTagsTemplate'] as $fieldName) {
			if (isset($this->$fieldName) and strpos($this->$fieldName, '$url') === false) {
				throw new \InvalidArgumentException("$fieldName: не указан \"\$url\" для подстановки ссылки");
			}
			$this->$fieldName = $params[$fieldName];
		}

		//Постоянные папки
		$this->jsMinDirPath  = "{$this->documentRootPath}/{$this->jsMinDirUrl}";
		$this->cssMinDirPath = "{$this->documentRootPath}/{$this->cssMinDirUrl}";
		//Временные папки
		$this->jsMinTempDirUrl   = $this->jsMinDirUrl  .'.temp';
		$this->cssMinTempDirUrl  = $this->cssMinDirUrl .'.temp';
		$this->jsMinTempDirPath  = $this->jsMinDirPath .'.temp';
		$this->cssMinTempDirPath = $this->cssMinDirPath.'.temp';
		//Добавляем хвосты к урлам
		$this->jsMinDirUrl      .= '/';
		$this->cssMinDirUrl     .= '/';
		$this->jsMinTempDirUrl  .= '/';
		$this->cssMinTempDirUrl .= '/';

		//Что делать в зависимости от типа
		$this->typesDataHash = [
			static::JS => [
				'ext'              => 'js',
				'min_dir_url'      => $this->jsMinDirUrl,
				'min_temp_dir_url' => $this->jsMinTempDirUrl,
				'min_dir'          => new Directory($this->jsMinDirPath),
				'min_temp_dir'     => new Directory($this->jsMinTempDirPath),
			],
			static::CSS => [
				'ext'              => 'css',
				'min_dir_url'      => $this->cssMinDirUrl,
				'min_temp_dir_url' => $this->cssMinTempDirUrl,
				'min_dir'          => new Directory($this->cssMinDirPath),
				'min_temp_dir'     => new Directory($this->cssMinTempDirPath),
			],
		];
	}



	/**
	 * Генерация минифицированных ассетов.
	 *
	 * $params:
	 * - type: 'js'; 'css'; true - обрабатывать и JS, и CSS
	 *
	 * Минифицированные файлы будут иметь имена типа:
	 * - 001_external_js.js
	 * - 002_local_js.js
	 * - 001_external_css.js
	 * - 002_local_css.js
	 *
	 * @param array{
	 *    type: string|true,
	 * } $params
	 */
	public function generate (array $params = []) {
		$p = $params + [
			'type' => true,
		];

		$typesList = ($p['type'] === true ? [static::JS, static::CSS] : [$type]);

		//Пересоздадим временные папки для каждого типа
		foreach ($this->typesDataHash as $typeData) {
			$typeData['min_temp_dir']->delete();
			$typeData['min_temp_dir']->create();
		}

		//Обрабатываем файлы и складываем их во временные папки
		foreach ($typesList as $type) {
			$number = 1;
			//Проходим по требуемым типам - js/css
			foreach ($this->assetUrlsData[$type] as $baseFileName => $urlsList) {
				$typeData = $this->typesDataHash[$type];
				$commandString    = "cd $this->npmPath && ";
				$inputFilesString = '';
				foreach ($this->expandUrlsList($urlsList) as $url) {
					$inputFilesString .= "-i \"$url\" ";
				}
				$n         = str_pad($number, 3, '0', STR_PAD_LEFT);
				$fileName  = "{$n}_$baseFileName.";
				$fileName .= $typeData['ext'];
				$tempFile  = new File("$typeData[min_temp_dir]/$fileName");
				$finalFile = new File("$typeData[min_dir]/$fileName");
				$commandString .=
					"$this->nodeCommandString ".
					"$typeData[ext] ".
					"-d \"$this->documentRootPath\" ".
					"$inputFilesString ".
					"-o \"$typeData[min_temp_dir_url]/$fileName\""
				;
				passthru($commandString);
				//Так как ассет может сжиматься не весь, а отдельными наборами,
				//то менять старую папку на новую нельзя - тогда там окажутся не все ассеты.
				//Надо менять пофайликово.
				$tempFile->rename($finalFile);
				$number++;
			}
		}

		//Удаляем временные папки
		foreach ($this->typesDataHash as $typeData) {
			$typeData['min_temp_dir']->delete();
		}

		//Записываем данные о минификации в файл-базу
		$this->writeDb();
	}



	public function getJsTagsString (): string {
		return $this->getTagsString(static::JS);
	}

	public function getCssTagsString (): string {
		return $this->getTagsString(static::CSS);
	}



	public function getTagsString (string $type): string {
		if ($type == static::JS) {
			$minDirPath = $this->jsMinDirPath;
			$minDirUrl  = $this->jsMinDirUrl;
		} else {
			$minDirPath = $this->cssMinDirPath;
			$minDirUrl  = $this->cssMinDirUrl;
		}

		$methodNamesHash = [
			static::ORIGINALS    => 'getTagsStringForOriginals',
			static::ORIGINALS_TS => 'getTagsStringForOriginals',
			static::CONCATENATED => 'getTagsStringForConcatenated',
			static::MINIFIED     => 'getTagsStringForMinfied',
		];
		return $this->{$methodNamesHash[$this->assetsMode]}($type, $minDirPath, $minDirUrl);
	}



	protected function getTagsStringForOriginals (string $type, string $minDirPath, string $minDirUrl): string {
		$tagsString = '';
		$time       = ($this->assetsMode == static::ORIGINALS) ? '' : '?t='.time();
		foreach ($this->getUrlsList($type) as $url) {
			$tagsString .= $this->formatTag($type, $url.$time);
		}
		return $tagsString;
	}



	protected function getTagsStringForConcatenated (string $type, string $minDirPath, string $minDirUrl): string {
		$url = ($type == static::JS) ? $this->jsConcatenatorUrl : $this->cssConcatenatorUrl;
		return $this->formatTag($type, $url.'?t='.time());
	}



	protected function getTagsStringForMinfied (string $type, string $minDirPath, string $minDirUrl): string {
		$tagsString = '';
		$dbData     = $this->getDbData();
		foreach ($dbData[$type] as $url) {
			$tagsString .= $this->formatTag($type, $url);
		}
		return $tagsString;
	}



	protected function formatTag (string $type, string $url) {
		$tag  = ($type == static::JS) ? $this->jsTagsTemplate : $this->cssTagsTemplate;
		$tag  = str_replace('$url',       $url, $tag);
		$tag  = preg_replace('~/{2,}~ui', '/',  $tag);
		$tag .= "\n";
		return $tag;
	}



	public function showConcatenatedJsCode (): string {
		$this->showConcatenatedCode(static::JS);
	}

	public function showConcatenatedCssCode (): string {
		$this->showConcatenatedCode(static::CSS);
	}



	public function showConcatenatedCode (string $type) {
		$contentType = ($type == static::JS) ? 'text/javascript' : 'text/css';
		header("Content-type: $contentType; charset=UTF-8");

		ob_start('ob_gzhandler');
		echo $this->getConcatenatedCode($type);
		echo ob_get_clean();
	}



	public function getConcatenatedCode (string $type): string {
		$code = '';
		foreach ($this->getUrlsList($type) as $url) {
			$code .= file_get_contents("$this->documentRootPath/$url");
			$code .= "\n";
		}
		return $code;
	}



	/**
	 * Возвращает все ссылки на файлы js/css из $this->assetUrlsData, различая директории и файлы.
	 *
	 * Если в записи из настроек указано что-то типа "/assets/styles/common.css",
	 * то эта запись в выходном массиве и вернётся.
	 *
	 * Если же там указано "/assets/styles/components/", со слэшем на конце,
	 * то в выходной массив будут добавлены все файлы, лежащие в этой директории.
	 *
	 * @param  string $type js/css
	 * @return array
	 */
	protected function getUrlsList (string $type): array {
		$list = [];
		foreach ($this->assetUrlsData[$type] as $urlsList) {
			$list = array_merge($list, $this->expandUrlsList($urlsList));
		}
		return $list;
	}



	/**
	 * Возвращает все ссылки на файлы js/css из переданного $urlsList, различая директории и файлы.
	 *
	 * @param  string $type js/css
	 * @return array
	 */
	protected function expandUrlsList (array $urlsList): array {
		$list = [];
		foreach ($urlsList as $url) {
			if ($url[strlen($url)-1] == '/') {
				//Указана директория - вытаскиваем из неё все файлы
				$dir = new Directory("$this->documentRootPath/$url");
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



	protected function writeDb (): array {
		$dbData = [
			'js'  => [],
			'css' => [],
		];

		$timeString = '?t='.time();

		foreach ($this->typesDataHash as $type => $typeData) {
			if ($typeData['min_dir']->isExists() and count($minFilesList = $typeData['min_dir']->getItemsList()->sort('name'))) {
				foreach ($minFilesList as $minFile) {
					$dbData[$type][] = $typeData['min_dir_url'].$minFile->getName().$timeString;
				}
			}
		}

		(new File($this->dbFilePath))->putPhpReturn($dbData);
		$this->dbData = $dbData;
		return $this->dbData;
	}



	protected function getDbData (): array {
		if (!$this->dbData) {
			if (!is_file($this->dbFilePath) or !is_readable($this->dbFilePath)) {
				throw new \InvalidArgumentException("Файл с данными о последней минификации недоступен: [$dbFile]");
			}
			$this->dbData = require $this->dbFilePath;
		}
		return $this->dbData;
	}
}
