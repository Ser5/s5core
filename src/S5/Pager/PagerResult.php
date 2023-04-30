<?
namespace S5\Pager;

class PagerResult {
	protected int $originalPageNumber;
	protected int $pageNumber;

	protected int $pagesAmount;
	protected int $itemsAmount;

	protected int $itemsFrom;
	protected int $itemsTo;

	protected int $pagesWindowWidth;

	protected array $pagesList;

	protected Page  $first;
	protected Page  $rew;
	protected Page  $prev;
	protected array $sequence;
	protected Page  $next;
	protected Page  $ff;
	protected Page  $last;

	public function __construct (
		int $itemsAmount,
		int $itemsPerPage,
		$originalPageNumber,
		int $pageNumber,
		$linker,
		int $itemsFrom, int $itemsTo,
		int $pagesAmount, int $pagesWindowWidth,
		Page $first, Page $rew, Page $prev, array $sequence, Page $next, Page $ff, Page $last
	) {
		$this->originalPageNumber = $originalPageNumber;
		$this->pageNumber         = $pageNumber;

		$this->pagesAmount = $pagesAmount;
		$this->itemsAmount = $itemsAmount;

		$this->itemsFrom = $itemsFrom;
		$this->itemsTo   = $itemsTo;

		$this->pagesWindowWidth = $pagesWindowWidth;

		$this->first    = $first;
		$this->rew      = $rew;
		$this->prev     = $prev;
		$this->sequence = $sequence;
		$this->next     = $next;
		$this->ff       = $ff;
		$this->last     = $last;

		$this->pagesList = $sequence;
		array_unshift($this->pagesList, $first, $rew, $prev);
		array_push($this->pagesList, $next, $ff, $last);
	}



	public function getOriginalPageNumber (): int {
		return $this->originalPageNumber;
	}

	public function getPageNumber (): int {
		return $this->pageNumber;
	}

	public function isPageNumberFixed (): bool {
		return ($this->originalPageNumber !== $this->pageNumber);
	}

	/**
	 * Сколько страниц имеется всего.
	 */
	public function countPages (): int {
		return $this->pagesAmount;
	}

	/**
	 * Сколько элементов (статей, товаров) имеется всего.
	 */
	public function countItems (): int {
		return $this->itemsAmount;
	}

	/**
	 * С какого элемента начинается вывод текущей страницы.
	 */
	public function getItemsFrom (): int {
		return $this->itemsFrom;
	}

	/**
	 * Каким элементом заканчивается вывод текущей страницы.
	 */
	public function getItemsTo (): int {
		return $this->itemsTo;
	}

	/**
	 * От какой записи сколько штук выводить.
	 *
	 * Пригодится в LIMIT SQL-запросов или похожих методах:
	 * ```
	 * $limit = $r->getLimit();
	 * $query = "SELECT * FROM list LIMIT $limit[0], $limit[1]";
	 * $queryBuilder->setOffset($limit[0])->setLimit($limit[1]);
	 * ```
	 *
	 * @return array{0: int, 1: int}
	 */
	public function getLimit (): array {
		return [$this->itemsFrom, $this->itemsTo - $this->itemsFrom + 1];
	}

	/**
	 * Ширина окна постраничности.
	 *
	 * Если у нас имеется постраничность вида
	 * `1 2 [3] 4 5`
	 * то ширина окна равняется 5 страницам.
	 */
	public function getPagesWindowWidth (): int {
		return $this->pagesWindowWidth;
	}


	/**
	 * Список всех страниц, включая кнопки первой/последней страниц, предыдущей/следующей, прыжки на ширину окна, дырки.
	 * @return \S5\Pager\Page[]
	 */
	public function getPagesList (): array {
		return $this->pagesList;
	}

	public function getFirst (): Page {
		return $this->first;
	}

	public function getRew (): Page {
		return $this->rew;
	}

	public function getPrev (): Page {
		return $this->prev;
	}

	/**
	 * Последовательность номеров страниц, включая первые/последние несколько, разрывы, основное окно.
	 *
	 * То есть, эта часть шаблона:
	 * `1 2 3 ... 10 11 [12] 13 14 ... 98 99 100`
	 *
	 * @return \S5\Pager\Page[]
	 */
	public function getSequence (): array {
		return $this->sequence;
	}

	public function getNext (): Page {
		return $this->next;
	}

	public function getFF (): Page {
		return $this->ff;
	}

	public function getLast (): Page {
		return $this->last;
	}
}
