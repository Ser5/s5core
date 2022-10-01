<?
namespace S5\RunLoggers;



class RunLoggersTest extends \PHPUnit\Framework\TestCase {
	public function testEmpty () {
		$rl = new EmptyRunLogger();
		$this->assertEquals('', $rl->get('test', 'ok', 2));
		$this->assertEquals('', $this->_getLog($rl, 'log', 'test'));
	}


	public function testConsole () {
		$rl = new ConsoleRunLogger();
		$this->assertEquals("test\n",                  $this->_getLog($rl, 'log', 'test'));
		$this->assertEquals("\033[0;32mtest\033[0m\n", $this->_getLog($rl, 'log', 'test', 'ok'));
		$this->assertEquals("   s1\n   s2\n",          $this->_getLog($rl, 'log', "s1\ns2", false, 2));
		$this->assertEquals("      s1\n      s2\n",    $this->_getLog($rl, 'log', "s1\ns2", false, 3));

		$this->assertEquals("   \033[0;32ms1\n   s2\033[0m\n", $this->_getLog($rl, 'log', "s1\ns2", 'ok', 2));

		$rl->group();
		$this->assertEquals("   test\n",    $this->_getLog($rl, 'log', 'test', false       ));
		$this->assertEquals("   test\n",    $this->_getLog($rl, 'log', 'test', false, false));
		$this->assertEquals("   test\n",    $this->_getLog($rl, 'log', 'test', false, 2   ));
		$this->assertEquals("test\n",       $this->_getLog($rl, 'log', 'test', false, -1  ));
		$this->assertEquals("      test\n", $this->_getLog($rl, 'log', 'test', false, '+1'));
		$rl->groupEnd();

		$this->assertEquals(
			$this->_getLog($rl, 'log',   'test', 'error'),
			$this->_getLog($rl, 'group', 'test', 'error')
		);
		$rl->groupEnd();
	}



	public function testArray () {
		$rl = new ArrayRunLogger();

		$expected1 = ['message'=>'test', 'type'=>false, 'level'=>1];
		$expected2 = ['message'=>'test', 'type'=>'ok',  'level'=>2];

		$this->assertEquals($expected1, $rl->get('test'));
		$this->assertEquals($expected2, $rl->get('test', 'ok', 2));

		$this->assertEquals('', $this->_getLog($rl, 'log', 'test'));
		$this->assertEquals('', $this->_getLog($rl, 'log', 'test', 'ok', 2));
		$this->assertEquals([$expected1, $expected2], $rl->getOutputList());
	}



	public function testGroup () {
		$rl = new GroupRunLogger([new EmptyRunLogger(), new ConsoleRunLogger(), new ArrayRunLogger()]);

		$expected1 = ['message'=>'test', 'type'=>false, 'level'=>1];

		$this->assertEquals(
			[
				'',
				"test\n",
				$expected1,
			],
			$rl->get('test')
		);

		$this->assertEquals("test\n", $this->_getLog($rl, 'log', 'test'));

		$this->assertEquals([$expected1], $rl->getLogger(2)->getOutputList());
	}



	public function testFactory () {
		$f = new RunLoggersFactory(['loggers' => [
			'empty'   => '\S5\RunLoggers\EmptyRunLogger',
			''        => '\S5\RunLoggers\EmptyRunLogger',
			'console' => '\S5\RunLoggers\ConsoleRunLogger',
			'1'       => '\S5\RunLoggers\ConsoleRunLogger',
			'group'   => fn() => new GroupRunLogger([new ConsoleRunLogger(), new ArrayRunLogger()]),
		]]);

		$this->assertInstanceOf(EmptyRunLogger::class, $f->get('empty'));
		$this->assertInstanceOf(EmptyRunLogger::class, $f->get(''));
		$this->assertInstanceOf(EmptyRunLogger::class, $f->get(false));

		$this->assertInstanceOf(ConsoleRunLogger::class, $f->get('console'));
		$this->assertInstanceOf(ConsoleRunLogger::class, $f->get(1));
		$this->assertInstanceOf(ConsoleRunLogger::class, $f->get(true));

		$groupRunLogger = $f->get('group');
		$this->assertInstanceOf(GroupRunLogger::class,   $groupRunLogger);
		$this->assertInstanceOf(ConsoleRunLogger::class, $groupRunLogger->getLogger(0));
		$this->assertInstanceOf(ArrayRunLogger::class,   $groupRunLogger->getLogger(1));

		$l1 = $f->get('console');
		$l2 = $f->get('console');
		$this->assertTrue($l1 ==  $l2);
		$this->assertTrue($l1 !== $l2);

		$sameLogger = $f->get($l1);
		$this->assertTrue($sameLogger === $l1);

		$exception = null;
		try {
			$f->get('invalid');
		} catch (\InvalidArgumentException $e) {
			$exception = $e;
		}
		$this->assertInstanceOf('InvalidArgumentException', $exception);
	}



	private function _getLog ($rl, $methodName, ...$params) {
		ob_start();
		call_user_func_array([$rl, $methodName], $params);
		return ob_get_clean();
	}
}
