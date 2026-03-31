<?
namespace S5\IO;



class ItemsList extends \S5\ArrayObject {
	protected int $sortOrder;



	public function delete (string|callable|false $filter = false): ItemsList {
		/** @var Item[] */
		$list = (array)$this;
		/** @var Item[] */
		$listAfterDeletion = [];

		foreach ($list as $item) {
			if (!$filter or $filter($item)) {
				$item->delete();
			} else {
				$listAfterDeletion[] = $item;
			}
		}

		$this->exchangeArray($listAfterDeletion);
		return $this;
	}



	/**
	 * Сортировка списка файлов.
	 *
	 * $by:
	 * - path
	 * - name
	 *
	 * $order:
	 * - asc
	 * - desc
	 */
	public function sort (string $by = 'path', string $order = 'asc'): ItemsList {
		static $allowedOrderHash = [
			'asc'  => true,
			'desc' => true,
		];
		if (!isset($allowedOrderHash[$order])) {
			throw new \InvalidArgumentException("Неизвестный порядок сортировки: [$order]. Допустимые значения: asc, desc.");
		}
		$this->sortOrder = ($order == 'asc') ? 1 : -1;
		$array = $this->getArrayCopy();
		switch ($by) {
			case 'path': usort($array, [$this, 'pathsComparer']); break;
			case 'name': usort($array, [$this, 'namesComparer']); break;
			default:     throw new \InvalidArgumentException("Неизвестный источник сортировки: [$by]. Допустимые значения: path, name.");
		}
		$this->exchangeArray($array);
		return $this;
	}



	public function filter (string|callable $filter): ItemsList {
		if (is_string($filter)) {
			$filter = fn($item) => preg_match($filter, $item->getName());
		}
		$this->exchangeArray(array_values(array_filter((array)$this, $filter)));
		return $this;
	}



	protected function pathsComparer (Item $a, Item $b): int {
		$a = strtolower($a->getPath());
		$b = strtolower($b->getPath());
		if ($a > $b) {
			return $this->sortOrder;
		} elseif ($a < $b) {
			return -$this->sortOrder;
		} else {
			return 0;
		}
	}

	protected function namesComparer (Item $a, Item $b): int {
		$a = strtolower($a->getName());
		$b = strtolower($b->getName());
		if ($a < $b) {
			return -$this->sortOrder;
		} elseif ($a > $b) {
			return $this->sortOrder;
		} else {
			return 0;
		}
	}
}
