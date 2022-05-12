<?
namespace S5\IO;

class ItemsList extends \S5\ArrayObject {
	private int $_sortOrder;

	/** @param callable|false $filter */
	public function delete ($filter = false) {
		if (!$filter) {
			$filter = fn($i)=>true;
		}
		$list = (array)$this;
		foreach ($list as $ix => $item) {
			if ($filter($item)) {
				$item->delete();
				unset($list[$ix]);
			}
		}
		$this->exchangeArray(array_values($list));
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
		static $allowedOrderHash = array(
			'asc'  => true,
			'desc' => true,
		);
		if (!isset($allowedOrderHash[$order])) {
			throw new \InvalidArgumentException("Unknown sort order");
		}
		$this->_sortOrder = ($order == 'asc') ? 1 : -1;
		$array = $this->getArrayCopy();
		switch ($by) {
			case 'path': usort($array, array($this, '_pathsComparer')); break;
			case 'name': usort($array, array($this, '_namesComparer')); break;
			default:     throw new \InvalidArgumentException("Unknown sort by");
		}
		$this->exchangeArray($array);
		return $this;
	}



	public function filter (callable $filterCallback): ItemsList {
		$this->exchangeArray(array_filter((array)$this, $filterCallback));
		return $this;
	}



	private function _pathsComparer (Item $a, Item $b): int {
		$a = strtolower($a->getPath());
		$b = strtolower($b->getPath());
		if ($a > $b) {
			return $this->_sortOrder;
		} elseif ($a < $b) {
			return -$this->_sortOrder;
		} else {
			return 0;
		}
	}

	private function _namesComparer (Item $a, Item $b): int {
		$a = strtolower($a->getName());
		$b = strtolower($b->getName());
		if ($a < $b) {
			return -$this->_sortOrder;
		} elseif ($a > $b) {
			return $this->_sortOrder;
		} else {
			return 0;
		}
	}
}
