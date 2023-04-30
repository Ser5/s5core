<?
namespace S5\Pager;

/**
 * Работа с постраничностью.
 *
 * Позволяет формировать блоки постраничности вида:
 * `first   <<   <   1 2 3 ... 10 11 [12] 13 14 ... 98 99 100   >   >>   last`
 *
 * Элементы постраничности:
 * - first - ссылка на первую страницу
 * - <<    - отмотать назад на количество страниц, равное ширине "окна"
 * - <     - на предыдущую страницу
 * - 1 2 3 - ссылки на несколько первых страниц
 * - ...   - разделитель между первыми страницами и основным окном
 * - 10 11 [12] 13 14 - основное "окно" постраничности, в данном случае ширина - 5 страниц
 * - ...       - разделитель между окном и последними страницами
 * - 98 99 100 - ссылки на несколько последних страниц
 * - >         - следующая страница
 * - >>        - вперёд на количество страниц, равное ширине "окна"
 * - last      - на последнюю страницу
 *
 * Набор элементов постраничности и их настройка делается через шаблон вида:
 * `[3 4*5 3]`
 *
 * Элементы шаблона:
 * - [3  - выводить ссылки на 3 первые страницы
 * - 4*5 - основное окно: выводить 4 страницы до текущей, текущую, 5 страниц после
 * - 3]  - выводить ссылки на 3 последние страницы
 *
 * Почему `4*5`, а не `5*5`:
 * типовое количество страниц в постраничности - 10 штук.
 * 4 + 1 + 5 дают как раз 10 страниц - с 1 по 10.
 * Тогда как шаблон `5*5` дал бы 11.
 *
 * Весь функционал постраничности используется через этот класс Pager.
 * Его можно использовать как класс-сервис или в качестве одноразовых объектов.
 * Что конструктор, что метод get() конфигурируются следующим набором параметров:
 * ```
 * $params = [
 *    'items_amount'   => 12345, //Общее количество записей
 *    'items_per_page' => 20,    //Количество записей, выводимых на одной странице
 *    'template'       => '4*5', //Шаблон
 *    'linker'         => fn($pageNumber)=>"/articles/?page=$pageNumber",
 *    'page_number'    => $_REQUEST['page'] ?? 1,
 * ];
 * ```
 *
 * Параметры наследуются:
 * 1. Умолчальные значения, забитые в класс
 * 2. Параметры, переданные в конструктор
 * 3. Параметры, переданные в метод get()
 *
 * В итоге в get() в обязательном порядке должен оказаться
 * `items_amount` - в умолчальных значениях его быть не может.
 *
 * Ещё желательно указать свой `linker` - умолчальный не всегда полезен:
 * он только приделывает `page` к текущему адресу: `?page=$pageNumber`.
 *
 * Получаются следующие варианты использования.
 *
 * Класс-сервис, если на сайте есть только одна постраничность - например, для новостей:
 * ```
 * //Инициализация сервиса (в конфиге)
 * $globalPager = new Pager(['items_amount' => $amount, 'linker' => $linker]);
 * //Использование (в модели, контроллере или шаблоне)
 * $globalPager->get(['page_number' => $_REQUEST['page'] ?? 1]);
 * ```
 *
 * Класс-сервис, если на сайте есть постраничность для нескольких сущностей:
 * ```
 * //Инициализация
 * $globalPager = new Pager(['items_amount' => $amount, 'linker' => $linker]);
 * //Постраничность новостей
 * $globalPager->get([
 *    'items_amount' => $newsAmount,
 *    'linker'       => $newsLinker
 *    'page_number'  => $_REQUEST['page'] ?? 1,
 * ]);
 * //Постраничность каталога
 * $globalPager->get([
 *    'items_amount' => $productsAmount,
 *    'linker'       => $productsLinker
 *    'page_number'  => $_REQUEST['page'] ?? 1,
 * ]);
 * ```
 *
 * Для таких целей имеет смысл завести отдельный класс или набор функций:
 * ```
 * $pagers->getForArticles($pageNumber);
 * $pagers->getForNews($pageNumber);
 * $pagers->getForCatalog($pageNumber);
 * ```
 *
 * Одноразовый объект - инициализация - и сразу использование:
 * ```
 * $params = ['items_amount' => $amount, 'linker' => $linker];
 * //Или так:
 * $pager = new Pager($params);
 * $pager->get();
 * //Или так:
 * $pager = new Pager();
 * $pager->get($params);
 * ```
 *
 * Примеры html-шаблонов для вывода постраничности см. в юнит-тестах.
 */
class Pager {
	protected static int    $defaultItemsPerPage = 10;
	protected static string $defaultTemplate     = '4*5';
	protected static int    $defaultPageNumber   = 1;

	protected array $params;



	/**
	 * Ctor.
	 *
	 * @param array{
	 *    items_amount:   ?int,
	 *    items_per_page: ?int,
	 *    template:       ?string,
	 *    linker:         ?mixed,
	 *    page_number:    ?int,
	 * } $params
	 */
	function __construct ($params = []) {
		$params = static::checkItemsAmount($params);
		$params = static::checkItemsPerPage($params);
		$params = static::checkTemplate($params);
		$params = static::checkLinker($params);
		$params = static::checkPageNumber($params, false);
		$this->params = $params;
	}



	/**
	 * Расчёт данных постраничности с произвольными параметрами.
	 *
	 * @param array{
	 *    items_amount:   ?int,
	 *    items_per_page: ?int,
	 *    template:       ?string,
	 *    linker:         ?mixed,
	 *    page_number:    ?int,
	 * } $params
	 */
	public function get (array $params = []): PagerResult {
		$p = $params + $this->params;

		$originalPageNumber = $p['page_number'] ?? false; //Номер страницы, как пришёл в параметрах, до корректировки

		//Проверка и изменение параметров
		$p = static::checkItemsAmount($p);
		$p = static::checkItemsPerPage($p);
		$p = static::checkTemplate($p);
		$p = static::checkLinker($p);
		$p = static::checkPageNumber($p, true);

		if (!isset($p['items_amount'])) {
			throw new \InvalidArgumentException("items_amount не передан");
		}

		$itemsAmount  = (int)$p['items_amount'];
		$itemsPerPage = (int)$p['items_per_page'];
		$linker       = $p['linker'];
		$pageNumber   = (int)$p['page_number']; //Итоговый номер страницы. Далее может быть скорректирован.

		$itemsFrom   = $itemsTo          = 0;
		$pagesAmount = $pagesWindowWidth = 0;
		$firstPage   = $rewPage = $prevPage = null;
		$nextPage    = $ffPage  = $lastPage = null;
		/** @var Page[] */
		$sequence = [];

		if ($itemsAmount) {
			//Количество страниц
			$pagesAmount = (int)ceil($itemsAmount / $itemsPerPage);

			//Корректировка номера текущей страницы
			if (!ctype_digit((string)$pageNumber)) {
				$pageNumber = 1;
			} elseif ($pageNumber < 1) {
				$pageNumber = 1;
			} elseif ($pageNumber > $pagesAmount) {
				$pageNumber = $pagesAmount;
			}

			//С какой по какую запись будет происходить вывод
			$itemsFrom = ($itemsPerPage * ($pageNumber - 1));
			$itemsTo   = min($itemsAmount - 1, ($itemsPerPage * $pageNumber) - 1);

			//Сборка диапазонов страниц исходя из того, что указано в шаблоне
			$rangeStringsList = preg_split('/\s+/', $p['template']);
			$pagesWindowWidth = false;
			//Список диапазонов может иметь такой вид:
			//[
			//   [1,  3],
			//   [10, 15],
			//   [98, 100],
			//]
			//Пересекающиеся диапазонв будут объединены.
			$rangesList = [];
			$matches    = [];
			foreach ($rangeStringsList as $rangeString) {
				if (preg_match('/^\[(\d*)$/', $rangeString, $matches)) {
					//Первые несколько страниц
					if (count($matches) < 2) {
						$rangesList[] = [1, 1];
					} else {
						$rangesList[] = [1, $matches[1]];
					}
				} elseif (preg_match('/^(\d*)\]$/', $rangeString, $matches)) {
					//Последние несколько страниц
					if (count($matches) < 2) {
						$rangesList[] = [$pagesAmount, $pagesAmount];
					} else {
						$rangesList[] = [$pagesAmount-$matches[1]+1, $pagesAmount];
					}
				} elseif (preg_match('/^(\d*)\*(\d*)$/', $rangeString, $matches)) {
					//Основное окно
					$range            = [];
					$pagesWindowWidth = 1;
					if ($matches[1]) {
						$range[]           = $pageNumber-$matches[1];
						$pagesWindowWidth += $matches[1];
					} else {
						$range[] = $pageNumber;
					}
					if ($matches[2]) {
						$range[]           = $pageNumber+$matches[2];
						$pagesWindowWidth += $matches[2];
					} else {
						$range[] = $pageNumber;
					}
					//Сдвигаем диапазон, если он не лезет в окно
					if ($range[0] < 1) {
						$range[1] += (1 - $range[0]);
						$range[0] = 1;
					} elseif ($range[1] > $pagesAmount) {
						$range[0] -= ($range[1] - $pagesAmount);
						$range[1] = $pagesAmount;
					}
					$rangesList[] = $range;
				}
			}

			//Чиним вылезание за края
			foreach ($rangesList as &$range) {
				$range = [max(1,$range[0]), min($pagesAmount,$range[1])];
			}
			unset($range);

			//Слияние пересекающихся диапазонов страниц
			do {
				$isRestart    = false;
				$rangesAmount = count($rangesList);
				if ($rangesAmount > 1) {
					for ($a = 0; $a < $rangesAmount-1; $a++) {
						for ($b = $a+1; $b < $rangesAmount; $b++) {
							$range1 = &$rangesList[$a];
							$range2 = $rangesList[$b];
							if (
								($range2[0] >= $range1[0] and $range2[0] <= $range1[1] + 1) or
								($range1[0] >= $range2[0] and $range1[0] <= $range2[1] + 1)
							) {
								$range1[0] = min($range1[0], $range2[0]);
								$isRestart = true;
							}
							if (
								($range2[1] >= $range1[0] - 1 and $range2[1] <= $range1[1]) or
								($range1[1] >= $range2[0] - 1 and $range1[1] <= $range2[1])
							) {
								$range1[1] = max($range1[1], $range2[1]);
								$isRestart = true;
							}
							if ($isRestart) {
								array_splice($rangesList, $b, 1);
								break 2;
							}
						}
					}
				}
			} while ($isRestart);

			//Сборка массива с номерами страниц
			for ($rangeIx = 0; $rangeIx < $rangesAmount; $rangeIx++) {
				for ($n = $rangesList[$rangeIx][0]; $n <= $rangesList[$rangeIx][1]; $n++) {
					$sequence[] = new Page(Page::NUMBER, $n, $linker, ($n != $pageNumber));
				}
				if ($rangeIx < $rangesAmount - 1) {
					$sequence[] = new Page(Page::GAP);
				}
			}

			//Сборка кнопок
			$firstPage = new Page(Page::FIRST, 1, $linker, ($pageNumber > 1));
			$rewPage   = new Page(Page::REW,   max(1, $pageNumber - $pagesWindowWidth), $linker, ($pageNumber > $pagesWindowWidth));
			$prevPage  = new Page(Page::PREV,  $pageNumber - 1, $linker, ($pageNumber > 1));
			$nextPage  = new Page(Page::NEXT,  $pageNumber + 1, $linker, ($pageNumber < $pagesAmount));
			$ffPage    = new Page(Page::FF,    min($pagesAmount, $pageNumber + $pagesWindowWidth), $linker, ($pageNumber < $pagesAmount));
			$lastPage  = new Page(Page::LAST,  $pagesAmount, $linker, ($pageNumber < $pagesAmount));
		}

		$pagerResult = new PagerResult(
			$itemsAmount,
			$itemsPerPage,
			$originalPageNumber,
			$pageNumber,
			$linker,
			$itemsFrom, $itemsTo,
			$pagesAmount, $pagesWindowWidth,
			$firstPage, $rewPage, $prevPage, $sequence, $nextPage, $ffPage, $lastPage
		);

		//Готово
		return $pagerResult;
	}



	protected static function checkItemsPerPage (array $p): array {
		if (!isset($p['items_per_page'])) {
			$p['items_per_page'] = static::$defaultItemsPerPage;
		} else {
			if (!ctype_digit((string)$p['items_per_page'])) {
				throw new \InvalidArgumentException("items_per_page: ожидалось целое число, получено [$p[items_per_page]]");
			}
			if ($p['items_per_page'] < 1) {
				throw new \InvalidArgumentException("items_per_page: ожидалось число больше 0, получено [$p[items_amount]]");
			}
		}
		return $p;
	}

	protected static function checkItemsAmount (array $p): array {
		if (!ctype_digit((string)$p['items_amount'])) {
			throw new \InvalidArgumentException("items_amount: ожидалось целое число, получено [$p[items_amount]]");
		}
		if ($p['items_amount'] < 0) {
			throw new \InvalidArgumentException("items_amount меньше нуля: [$p[items_amount]]");
		}
		return $p;
	}

	protected static function checkPageNumber (array $p, bool $isSetIfEmpty): array {
		if (!isset($p['page_number'])) {
			if ($isSetIfEmpty) {
				$p['page_number'] = static::$defaultPageNumber;
			}
		} elseif (!ctype_digit((string)$p['page_number'])) {
			throw new \InvalidArgumentException("page_number должен быть целым числом, получено: [$p[items_amount]]");
		}
		return $p;
	}

	protected static function checkTemplate (array $p): array {
		if (!isset($p['template']) or !$p['template']) {
			$p['template'] = static::$defaultTemplate;
		} else {
			$testTemplate = $p['template'];
			$testTemplate = preg_replace(
				['/\[\d*/', '/\d*\*\d*/', '/\d*\]/'],
				'',
				$testTemplate
			);
			if (strlen(str_replace(' ', '', $testTemplate)) > 0) {
				throw new \InvalidArgumentException("template содержит неверные символы: [$p[template]]");
			}
		}
		return $p;
	}

	protected static function checkLinker (array $p): array {
		if (!isset($p['linker'])) {
			$p['linker'] = fn($pageNumber)=>"?page=$pageNumber";
		} else {
			if (!is_callable($p['linker'])) {
				throw new \InvalidArgumentException("linker: ожидалась функция обратного вызова, получено [$p[linker]]");
			}
		}
		return $p;
	}
}
