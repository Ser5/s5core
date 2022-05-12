<?
namespace S5;

class InnerDb {
	protected array $db            = [];
	protected array $kvToIndexHash = [];


	public function __construct (array $data = [], array $keysList = []) {
		if (!$keysList) {
			$this->db = $data;
		} else {
			foreach ($data as $rowIndex => $row) {
				foreach ($row as $valueIndex => $value) {
					$this->db[$rowIndex][$keysList[$valueIndex]] = $value;
				}
			}
		}

		foreach ($data as $index => $e) {
			foreach ($e as $fieldName => $fieldValue) {
				$this->kvToIndexHash[$fieldName][$fieldValue][$index] = true;
			}
		}
	}



	/**
	 * @param array|false  $filterData
	 * @param string|false $fieldName
	 */
	public function get ($filterData = false, $fieldName = false): mixed {
		$list = $this->getList($filterData, $fieldName);
		return ($list ? $list[0] : false);
	}



	/**
	 * @param array|false  $filterData
	 * @param string|false $fieldName
	 */
	public function getList ($filterData = false, $fieldName = false): array {
		$r = [];

		if (!$filterData) {
			$r = $this->db;
		} else {
			//Составляем список индексов по парам свойство-значение.
			//Это двухмерный массив, типа: [
			//   [1 =>true,  2 =>true  3 =>true],
			//   [2 =>true,  3 =>true  4 =>true],
			//   [10=>true,  30=>true, 50=>true],
			//]
			//
			//Это может значить, например, следующее:
			//Паре ['name' => 'Vasya'] принадлежат записи с индексами [1,  2,  3],
			//Паре ['age'  => 85] - записи с индексами [2,  3,  4]
			//итд.
			$allIndexesTable = [];
			foreach ($filterData as $k => $v) {
				if (!isset($this->kvToIndexHash[$k][$v])) {
					$allIndexesTable = [];
					break;
				}
				$allIndexesTable[] = $this->kvToIndexHash[$k][$v];
			}

			if ($allIndexesTable) {
				//Обрабатывать будем в порядке увеличения количества элементов
				usort($allIndexesTable, fn($a, $b) => count($a) - count($b));

				if (count($allIndexesTable) == 1) {
					//Тут логика простая:
					//у нас, получается, типа есть [[1=>true, 2=>true, 3=>true]].
					//Просто берём все записи, соответствующие этим индексам.
					foreach ($allIndexesTable[0] as $ix => $true) {
						$r[] = $this->db[$ix];
					}
				} else {
					//Тут сложнее: нужно выбрать записи, которые соответствуют
					//всем парам ключ-значение, указанным в фильтре.
					//
					//Берём первый набор индексов и начинаем по нему проходить:
					//берём первый индекс из первого набора
					//и по-очереди сравниваем его с первым индексом
					//каждого другого набора.
					//
					//Если такой индекс есть во всех наборах,
					//то запись считается найденной.
					//
					//В общем, проход по таблице делается сверху-вниз слева-направо.
					//
					//Именно для этого данные в $this->kvToIndexHash хранятся
					//как [1=>true, 2=>true, 3=>true], а не просто [1, 2, 3] - чтобы
					//можно было проверять наличие индекса через isset().
					$firstIndexesList = array_shift($allIndexesTable);
					foreach ($firstIndexesList as $ix => $true) {
						foreach ($allIndexesTable as $otherIndexesList) {
							if (!isset($otherIndexesList[$ix])) {
								continue 2;
							}
						}
						$r[] = $this->db[$ix];
					}
				}
			}
		}

		if ($fieldName) {
			foreach ($r as &$e) {
				$e = $e[$fieldName];
			}
			unset($e);
		}

		return $r;
	}
}
