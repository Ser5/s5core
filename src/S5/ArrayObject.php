<?
namespace S5;

class ArrayObject extends \ArrayObject implements \ArrayAccess {
	public function offsetExists($offset): bool {
		return parent::offsetExists($offset);
	}

	public function offsetGet($offset) {
		return parent::offsetGet($offset);
	}

	public function offsetSet($offset, $value) {
		parent::offsetSet($offset, $value);
	}

	public function offsetUnset($offset) {
		parent::offsetUnset($offset);
	}
}
