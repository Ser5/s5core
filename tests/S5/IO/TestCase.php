<?
namespace S5\IO;

class TestCase extends \S5\TestCase {
	protected $testDirPath = __DIR__.'/files/';



	protected function setTestDirSubpath ($subPath) {
		$this->testDirPath = (string)(new Path(__DIR__."/files/$subPath/"));
	}



	protected function clearDirectory ($subPath) {
		$d = new Directory($this->testDirPath.$subPath);
		$d->clear();
	}

	protected function deleteDirectory ($subPath) {
		$d = new Directory($this->testDirPath.$subPath);
		$d->delete();
	}



	public function setUp (): void {
		$d = new Directory($this->testDirPath);
		$d->delete();
		$d->create();
	}

	public function tearDown (): void {
		$this->deleteDirectory('');
	}
}
