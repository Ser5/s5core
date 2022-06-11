<?
namespace S5;

trait ConstructorTrait {
	public function __constructor (array $params) {
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}
	}
}
