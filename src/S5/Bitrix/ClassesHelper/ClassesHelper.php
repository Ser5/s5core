<?
namespace S5\Bitrix\ClassesHelper;

class ClassesHelper {
	protected $params;
	
	
	
	public function __construct ($params) {
		static $requiredParamNamesList = ['object', 'parent'];
		foreach ($requiredParamNamesList as $key) {
			if (!isset($params[$key])) {
				throw new \InvalidArgumentException("$key param required");
			}
		}
		$this->params = $params;
	}
	

	
	/**
	 * 
	 * @param  array $filter
	 * @return array
	 */
	public function getOne ($filter) {
		$list = $this->params['object']->getSimpleListAsArray($filter);
		return count($list) ? $list[0] : false;
	}
	
	
	
	public function getSimpleListAsArray () {
		$args   = func_get_args();
		$result = call_user_func_array(
			array($this->params['object'], 'GetList'),
			$args
		);
		return $this->createArrayFromCdbResult($result);
	}
	
	
	
	public function createArrayFromCdbResult (\CDBResult $result) {
		$list = [];
		while ($item = $result->GetNext()) {
			$list[] = $item;
		}
		return $list;		
	}
	
	
	
	/**
	 * 
	 * @param  int|array $filter
	 * @return int Количество успешно удалённых записей
	 */
	public function delete ($filter) {
		if (!is_array($filter)) {
			$r = $this->params['parent']->Delete($filter);
			return $r ? 1 : 0;
		} else {
			$list = $this->params['object']->getSimpleListAsArray($filter);
			$counter = 0;
			foreach ($list as $item) {
				if ($this->params['parent']->Delete($item['ID'])) {
					$counter++;
				}
			}
			return $counter;
		}
	}

	
	
	/**
	 * 
	 * @param  array $filter
	 * @return array
	 */
	public function isExists ($filter) {
		return $this->params['object']->getOne($filter) ? true : false;
	}
}
