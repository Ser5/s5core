<?
namespace S5;

/**
 * Сборник всякой шляпени для массивов.
 */
class ArrayUtils {
	/**
	 * Возвращает все возможные комбинации значений для переданного списка массивов.
	 *
	 * Пример<br />
	 * Есть у нас такой список массивов, и мы его передаём в метод:
	 * ```
	 * $arraysList =[
	 *    ['news', 'articles'],
	 *    [10, 20],
	 *    ['vote', 'download'],
	 * ];
	 * $result = ArrayUtils::getAllCombinations($arraysList);
	 * ```
	 *
	 * На выходе получаем такую штуку:
	 * ```
	 *[
	 *    ['news',     10, 'vote'],
	 *    ['news',     10, 'download'],
	 *    ['news',     20, 'vote'],
	 *    ['news',     20, 'download'],
	 *    ['articles', 10, 'vote'],
	 *    ['articles', 10, 'download'],
	 *    ['articles', 20, 'vote'],
	 *    ['articles', 20, 'download'],
	 * ];
	 * ```
	 *
	 * Если передать на вход пустой массив - на выходе будет тоже пустой массив.
	 *
	 * Если хотя бы один из подмассивов будет пустой или вообще не массив - ловим исключение.
	 * Также ловим исключение, если не-массив будет передан в качестве аргумента.
	 */
	public static function getAllCombinations (array $arraysList): array {
		//Проверка, что это список
		if (!is_array($arraysList)) {
			throw new \InvalidArgumentException("\$arraysList must be an array of arrays");
		}
		//Проверка, что в списке что-нибудь есть
		$listsAmount = count($arraysList);
		if ($listsAmount == 0) {
			return [];
		}
		//Проверка, что все подмассивы - непустые, и что они вообще массивы
		for ($a = 0; $a < $listsAmount; $a++) {
			$messageBase = "\$arraysList must consist of non-empty arrays;";
			if (!is_array($arraysList[$a])) {
				throw new \InvalidArgumentException("$messageBase something other than array met");
			}
			if (count($arraysList[$a]) == 0) {
				throw new \InvalidArgumentException("$messageBase empty array met");
			}
		}

		$result         = [];
		$levelsList     = array_fill(0, count($arraysList), 0);
		$lastArrayIndex = count($arraysList) - 1;

		$processArray = function ($arrayIndex) use (&$processArray, $arraysList, $lastArrayIndex, &$result, &$levelsList) {
			//Текущий массив
			$array = $arraysList[$arrayIndex];

			//Надо обойти текущий массив целиком
			for ($level = 0; $level < count($array); $level++) {
				//Актуализируем указатель уровня для текущего массива
				$levelsList[$arrayIndex] = $level;

				//Составление очередной записи результата из всех массивов
				if ($arrayIndex == $lastArrayIndex) {
					$resultRow = [];
					foreach ($arraysList as $ix => $arr) {
						$resultRow[] = $arr[$levelsList[$ix]];
					}
					$result[] = $resultRow;
				}

				//Обходим следующие массивы, если они есть
				if (isset($arraysList[$arrayIndex + 1])) {
					$processArray($arrayIndex + 1);
				}

			}

			//Как обошли все уровни текущего массива, устанавливаем указатель в ноль,
			//чтобы при повторном обходе можно было начать его с начала.
			$levelsList[$arrayIndex] = 0;
		};

		$processArray(0);

		return $result;
	}



	/**
	 * Делает из плоского списка дерево.
	 *
	 * source может быть как простым массивом, так и каким-нибудь ресурсом, объектом или вообще чем угодно.
	 * Во втором случае так же требуется передать параметр reader - анонимную функцию, которая это что угодно будет читать.
	 * reader будет вызываться с source в качестве аргумента.
	 * Возвращать reader должен какое-либо значение или false - если значений больше не осталось.
	 *
	 * Например, в случае чтения из базы данных код может быть таким:
	 * ```
	 * $source = mysql_query("SELECT * FROM users ORDER BY id");
	 * $reader = function ($source) {
	 * 		return mysql_fetch_array($source);
	 * };
	 * ```
	 *
	 * Каждый элемент списка должен иметь ключ с уникальным ID элемента и ключ с ID родительского элемента - по этим ключам
	 * элементы сцепляются друг с другом и образуют дерево.
	 *
	 * Возвращает метод набор не простых массивов, а ассоциативных:
	 * индексом, соответствующим элементу является значение его уникального идентификатора.
	 *
	 * $params:
	 * - root_id
	 * - source
	 * - reader
	 * - id_key_name
	 * - parent_key_name
	 * - subtree_key_name
	 */
	public static function getTreeFromList (array $params): array  {
		foreach (['source', 'parent_key_name', 'subtree_key_name'] as $paramName) {
			if (!isset($params[$paramName])) {
				throw new \InvalidArgumentException("$paramName is required");
			}
		}

		$rootId         = $params['root_id']     ?? 0;
		$reader         = $params['reader']      ?? false;
		$idKeyName      = $params['id_key_name'] ?? 'id';
		$source         = $params['source'];
		$parentKeyName  = $params['parent_key_name'];
		$subtreeKeyName = $params['subtree_key_name'];

		if (is_array($source) and !is_callable($reader)) {
			foreach ($source as $e) {
				$tree[$e[$idKeyName]] = $e;
			}
		}
		elseif (is_callable($reader)) {
			$tree = [];
			while (false !== ($e = $reader($source))) {
				$tree[$e[$idKeyName]] = $e;
			}
		}
		else {
			throw new \InvalidArgumentException("source or reader are invalid");
		}

		$idsList = array_keys($tree);
		foreach ($idsList as $id) {
			$e = &$tree[$id];
			$tree[$e[$parentKeyName]][$subtreeKeyName][$e[$idKeyName]] = &$e;
		}

		return $tree[$rootId];
	}



	/**
	 * Достаёт из БД дерево начиная с указанного элемента.
	 *
	 * $params:
	 * - query
	 * - db_query
	 * - db_fetch
	 * - id_key_name
	 * - parent_key_name
	 * - subtree_key_name
	 *
	 * query:
	 * # SELECT * FROM users WHERE
	 *   <br>
	 *   базовая часть запроса
	 * # id = 123
	 *   <br>
	 *   условие для получения корневого элемента дерева
	 * # parent_id IN ()
	 *   <br>
	 *   условие для получения остальных элементов дерева
	 *
	 * Третий элемент массива query можно пропустить,
	 * тогда недостающая часть запроса будет составлена с использванием
	 * значения параметра parent_key_name.
	 * Например, при основе запроса
	 * ```
	 * SELECT * FROM users WHERE
	 * ```
	 * и parent_key_name равном parent_id,
	 * окончательный шаблон запроса будет выглядеть как
	 * ```
	 * SELECT * FROM users WHERE parent_id IN ()
	 * ```
	 *
	 * Полный пример вызова метода:
	 * ```
	 * ArrayUtils::getTreeFromDb(array(
	 * 		'query' => array(
	 *  		'SELECT * FROM users WHERE',
	 * 			'id = 123',
	 * 			'parent_id IN ()',
	 * 		),
	 * 		'db_query'         => function ($query) { return mysql_query($query); },
	 * 		'db_fetch'         => function ($r)     { return mysql_fetch_array($r); },
	 * 		'id_key_name'      => 'id',
	 * 		'parent_key_name'  => 'parent_id',
	 * 		'subtree_key_name' => 'subtree',
	 * ));
	 * ```
	 */
	public static function getTreeFromDb (array $params): array  {
		foreach (array('query', 'parent_key_name', 'subtree_key_name') as $paramName) {
			if (!isset($params[$paramName])) {
				throw new \InvalidArgumentException("$paramName is required");
			}
		}
		if (!is_array($params['query']) or count($params['query']) < 2) {
			throw new \InvalidArgumentException("query param should be an array and have at least 2 elements");
		}
		foreach (array('db_query', 'db_fetch') as $paramName) {
			if (!is_callable($params[$paramName])) {
				throw new \InvalidArgumentException("$paramName must be a callable");
			}
		}
		static $defaultParams = array(
			'id_key_name' => 'id',
		);
		$p = array_merge($defaultParams, $params);
		$idKeyName      = $p['id_key_name'];
		$parentKeyName  = $p['parent_key_name'];
		$subtreeKeyName = $p['subtree_key_name'];
		$getRootItemQuery = $params['query'][0] .' '. $params['query'][1];
		if (isset($params['query'][2])) {
			$getSubitemsQuery = $params['query'][0] .' '. $params['query'][2];
		} else {
			$getSubitemsQuery = $params['query'][0] . "$parentKeyName IN ()";
		}
		$dbQuery = $params['db_query'];
		$dbFetch = $params['db_fetch'];
		$r            = $dbQuery($getRootItemQuery);
		$rootItemData = $dbFetch($r);
		if (empty($rootItemData)) {
			return [];
		}
		$itemsHash = array(
			$rootItemData[$idKeyName] => $rootItemData,
		);
		$queueString = $rootItemData[$idKeyName];
		while ($queueString) {
			$r = $dbQuery(str_replace('()', "($queueString)", $getSubitemsQuery));
			$queueString = '';
			while ($subitemData = $dbFetch($r)) {
				$itemsHash[$subitemData[$idKeyName]] = $subitemData;
				$queueString .= $subitemData[$idKeyName].',';
			}
			if ($queueString) {
				$queueString = substr($queueString, 0, -1);
			}
		}
		$tree = static::getTreeFromList(array(
			'root_id'          => $rootItemData[$idKeyName],
			'source'           => $itemsHash,
			'id_key_name'      => $idKeyName,
			'parent_key_name'  => $parentKeyName,
			'subtree_key_name' => $subtreeKeyName,
		));
		return $tree;
	}



	/**
	 * Принимает два списка: имеющиеся записи и новые - и показывает, какие записи создать, какие - обновить, какие - удалить.
	 */
	public static function getCrudActions (array $existingIdsList, array $newIdsList): array {
		return [
			'delete' => array_diff($existingIdsList, $newIdsList),
			'create' => array_diff($newIdsList, $existingIdsList),
			'edit'   => array_intersect($existingIdsList, $newIdsList),
		];
	}



	/**
	 * Сортирует таблицу по переданному образцу.
	 *
	 * Если есть таблица
	 * ```
	 * $list = [
	 *    ['id' => 1, 'value' => 'a'],
	 *    ['id' => 2, 'value' => 'b'],
	 *    ['id' => 3, 'value' => 'c'],
	 * ];
	 * ```
	 *
	 * И надо её отсортировать, чтобы id были именно в порядке 3, 1, 2,
	 * то вызываем метод таким образом:
	 * ```
	 * ArrayUtils::customSort($list, 'id', [3, 1, 2]);
	 * ```
	 *
	 * $params:
	 * - list
	 * - sort_key_name
	 * - order
	 *
	 * @param array $params
	 */
	public static function customSort (array $list, string $sortKeyName, array $order): array {
		if (count($list) == 0) {
			return [];
		}
		$hash = [];
		reset($list);
		if (is_array(current($list))) {
			foreach ($list as $e) {
				$hash[$e[$sortKeyName]][] = $e;
			}
		}
		elseif (is_object(current($list))) {
			foreach ($list as $e) {
				$hash[$e->{$sortKeyName}][] = $e;
			}
		}
		else {
			throw new \InvalidArgumentException("List contains values of invalid type");
		}
		$orderedList = [];
		foreach ($order as $keyValue) {
			if (isset($hash[$keyValue])) {
				$orderedList = array_merge($orderedList, $hash[$keyValue]);
			}
		}
		return $orderedList;
	}
}
