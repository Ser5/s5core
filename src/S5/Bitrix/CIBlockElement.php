<?
namespace S5\Bitrix;
use ClassesHelper\Traits as T;



class CIBlockElement extends \CIBlockElement {
	use T\BaseTrait, T\IsExistsTrait, T\DeleteTrait;

	public function __construct () {
		$this->initClassesHelper(new \CIBlockElement());
	}



	/**
	 *
	 * @param  array $filter
	 * @return array|false
	 */
	public function getOne ($filter, $selectFields = [], $propertiesToGroupList = []) {
		$list = $this->getListAsArray($filter, $selectFields, ['nTopCount' => 1]);
		return count($list) > 0 ? $list[0] : false;
	}



	/**
	 * Возвращает список элементов инфоблока.
	 *
	 * - выкинуты редко используемые сортировка и группировка
	 * - возвращает сразу массив
	 * - позволяет сразу вытаскивать вместе с элементами их свойства (PROPERTY)
	 * - группирует свойства множественной привязки
	 *
	 * Пример:
	 * ```
	 * $iblockElement->getSimpleListAsArray(
	 *    ['IBLOCK_ID' => 10],
	 *    ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_PICTURE', 'PROPERTY_USER', 'PROPERTY_SIMPLE', 'PROPERTY_CITY'],
	 *    ['USER', 'CITY']
	 * );
	 * ```
	 *
	 * Свойства для группировки указываются без префикса PROPERTY_ - и так понятно, что они свойства.
	 *
	 * @param  array $filter Фильтр поиска - аналогичен Битриксовскому
	 * @param  array $selectFields Список полей для выбора, тоже аналогичен Битриксовскому
	 * @param  array $propertiesToGroupList Список свойств для группировки.
	 * @return array
	 */
	public function getSimpleListAsArray ($filter, $selectFields, $propertiesToGroupList = false) {
		return $this->getListAsArray(
			[],
			$filter,
			false,
			false,
			$selectFields,
			$propertiesToGroupList
		);
	}



	/**
	 * Возвращает список элементов инфоблока.
	 *
	 * @param  array       $order
	 * @param  array       $filter
	 * @param  array|false $groupBy
	 * @param  array|false $navParams
	 * @param  array       $selectFields
	 * @param  array|false $propertiesToGroupList
	 * @return array
	 */
	public function getListAsArray ($order, $filter, $groupBy, $navParams, $selectFields, $propertiesToGroupList = false) {
		$elementsDataHash = [];

		$r = parent::GetList($order, $filter, $groupBy, $navParams, $selectFields);
		if (!$propertiesToGroupList) {
			//Без группировки свойств - просто и быстро
			while ($e = $r->GetNext()) {
				$elementsDataHash[] = $e;
			}
		} else {
			//С группировкой - морочно
			$propsToGroup = [];
			foreach ($propertiesToGroupList as $propertyName) {
				$key = 'PROPERTY_'.$propertyName;
				if (!in_array($key, $params['select_fields'])) {
					throw new \InvalidArgumentException( "Свойство $key указано в массиве группировки, но отсутствует в массиве выбираемых полей" );
				}
				$propsToGroup[] = $key;
			}
			$e = $r->GetNext();
			if (!empty($e)) {
				foreach ($propsToGroup as $propertyName) {
					$key = $propertyName.'_VALUE';
					if (!array_key_exists( $key, $e) ) {
						throw new \InvalidArgumentException( "Поле $propertyName отсутствует в результате выборки" );
					}
				}
				//Наконец обрабатываем записи
				do {
					$elementId = $e['ID'];
					if (!isset($elementsDataHash[$elementId])) {
						$elementsDataHash[$elementId] = $e;
						//Выкидываем поля, которые будут сгруппированы
						unset($elementsDataHash[$elementId][$propertyName.'_VALUE']);
						unset($elementsDataHash[$elementId]['~'.$propertyName.'_VALUE']);
						unset($elementsDataHash[$elementId][$propertyName.'_VALUE_ID']);
						unset($elementsDataHash[$elementId]['~'.$propertyName.'_VALUE_ID']);
					}
					//Группируем свойства
					foreach ($propsToGroup as $propertyName) {
						if (!isset($elementsDataHash[$elementId][$propertyName])) {
							$elementsDataHash[$elementId][$propertyName] = [];
						}
						$elementsDataHash[$elementId][$propertyName][] = array(
							'VALUE'     => $e[$propertyName.'_VALUE'],
							'~VALUE'    => $e['~'.$propertyName.'_VALUE'],
							'VALUE_ID'  => $e[$propertyName.'_VALUE_ID'],
							'~VALUE_ID' => $e['~'.$propertyName.'_VALUE_ID'],
						);
					}
					//К следующему элементу
					$e = $r->GetNext();
					//var_dump( $aElement );
				} while (!empty($e));
			}
		}

		return $elementsDataHash;
	}
}
