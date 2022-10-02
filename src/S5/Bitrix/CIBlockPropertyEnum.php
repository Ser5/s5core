<?
namespace S5\Bitrix;
use ClassesHelper\Traits as T;



class CIBlockPropertyEnum extends \CIBlockPropertyEnum {
	use T\BaseTrait, Е\GetOneTrait, Е\GetSimpleListAsArrayTrait, T\IsExistsTrait, T\DeleteTrait;

	public function __construct () {
		$this->initClassesHelper(new \CIBlockPropertyEnum());
	}
}
