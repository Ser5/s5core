<?
namespace S5;
use S5\IO\Directory;


class TestCase extends \PHPUnit\Framework\TestCase {
	public function assertException ($func, string $failText = 'Failed') {
		try {
			$func();
			$this->fail($failText);
		} catch (\Throwable $e) {
			$this->assertTrue(true); //Чтобы PHPUnit не пищал про Risky tests
		}
	}




	/*public function getDirectoryFromCurrent (string $dirPath): string {
		$callerDirPath = dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0]['file']);
		return new Directory("$callerDirPath/$dirPath");
	}*/
}
