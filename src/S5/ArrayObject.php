<?
namespace S5;

class ArrayObject extends \ArrayObject implements \ArrayAccess {
	public function offsetExists(mixed $offset): bool {
		return parent::offsetExists($offset);
	}

	public function offsetGet(mixed $offset): mixed {
		return parent::offsetGet($offset);
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		parent::offsetSet($offset, $value);
	}

	public function offsetUnset(mixed $offset): void {
		parent::offsetUnset($offset);
	}
}
