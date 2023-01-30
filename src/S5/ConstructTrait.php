<?
namespace S5;

trait ConstructTrait {
	public function __construct (array $params = []) {
		$this->_copyConstructParams($params);
	}

	private function _copyConstructParams (array $params = []) {
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}
	}
}
