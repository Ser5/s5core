<?
namespace S5\Web\AntiSpam\Items;

abstract class BaseItem implements IItem {
	protected static int $itemNumber = 0;

	public function __construct () {
		static::$itemNumber++;
	}
}
