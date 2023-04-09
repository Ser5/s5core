<?
namespace S5\Console;

use S5\Console\Application;


class ApplicationTest extends \S5\TestCase {
	public function test () {
		//Описание
		$this->assertStringContainsString('Run description', $this->_exec());

		$outputString = $this->_exec('-h test:run');
		$this->assertStringContainsString('Run description',   $outputString);
		$this->assertStringContainsString('Value description', $outputString);
		$this->assertStringContainsString('Flag description',  $outputString);

		//Запуск с правильными параметрами
		$this->assertEquals('run result', $this->_exec("test:run"));

		$this->assertEquals('run result one', $this->_exec("test:run --value=one"));

		$this->assertEquals('run result one two three', $this->_exec("test:run --value=one --value=two --value=three"));

		$this->assertEquals('run result yes', $this->_exec("test:run --flag"));

		//bool no default
		$this->assertEquals('run result',     $this->_exec("test:bool:no-default"));
		$this->assertEquals('run result yes', $this->_exec("test:bool:no-default --value"));
		$this->assertEquals('run result no',  $this->_exec("test:bool:no-default --value=0"));
		$this->assertEquals('run result yes', $this->_exec("test:bool:no-default --value=1"));

		//bool with default
		$this->assertEquals('run result no',  $this->_exec("test:bool:with-default"));
		$this->assertEquals('run result yes', $this->_exec("test:bool:with-default --value"));
		$this->assertEquals('run result no',  $this->_exec("test:bool:with-default --value=0"));
		$this->assertEquals('run result yes', $this->_exec("test:bool:with-default --value=1"));

		//Запуск с кривыми параметрами
		$this->assertStringContainsString('does not exist',     $this->_exec("test:run --nope"));
		$this->assertStringContainsString('requires a value',   $this->_exec("test:run --value"));
		$this->assertStringContainsString('not accept a value', $this->_exec("test:run --flag=1"));
	}



	private function _exec ($commandString = '') {
		ob_start();
		passthru('php ' . __DIR__ . '/console.php ' . $commandString . ' 2>&1');
		return trim(ob_get_clean());
	}
}
