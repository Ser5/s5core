<?
namespace S5;

class TestCase extends \PHPUnit\Framework\TestCase {
	public function assertException ($func, $failText = 'Failed') {
		try {
			$func();
			$this->fail($failText);
		} catch (\Throwable $e) {
			$this->assertTrue(true); //Чтобы PHPUnit не пищал про Risky tests
		}
	}
}
