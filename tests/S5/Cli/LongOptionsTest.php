<?
namespace S5\Cli;

class LongOptionsTest extends \S5\TestCase {
	public function test () {
		$output      = exec('php '.__DIR__.'/LongOptions/1.php --value1=1 --value2=2 --novalue1 --novalue2 --defaultvalue1 --defaultvalue2');
		$optionsHash = unserialize($output);
		$this->assertEquals(8, count($optionsHash));
		$this->assertEquals(1, $optionsHash['value1']);
		$this->assertEquals(2, $optionsHash['value2']);
		$this->assertTrue($optionsHash['novalue1']);
		$this->assertTrue($optionsHash['novalue2']);
		$this->assertFalse($optionsHash['noarg1']);
		$this->assertFalse($optionsHash['noarg2']);
		$this->assertEquals(10, $optionsHash['defaultvalue1']);
		$this->assertEquals(20, $optionsHash['defaultvalue2']);
	}
}
