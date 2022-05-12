<?
namespace S5;

class TestCase extends \PHPUnit\Framework\TestCase {
	public function assertException ($func, $failText = 'Failed') {
		do {
			try {
				$func();
			} catch (\Exception $e) {
				break;
			}
			$this->fail($failText);
		} while (false);
	}
}
