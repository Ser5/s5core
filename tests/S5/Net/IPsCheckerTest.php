<?
namespace S5\Net;

class IPsCheckerTest extends \S5\TestCase {
	public function testCheckSimple () {
		$ipc = new IPsChecker(['192.168.0.1']);
		$result = $ipc->check(192, 168, 0, 1);
		$this->assertEquals(true, $result);
		$result = $ipc->check(192, 168, 0, 2);
		$this->assertEquals(false, $result);
	}
	
	public function testCheckCommas () {
		$ipc = new IPsChecker(['192.168.0.1,3,5']);
		$this->assertEquals(true, $ipc->check(192, 168, 0, 1));
		$this->assertEquals(false, $ipc->check(192, 168, 0, 2));
		$this->assertEquals(true, $ipc->check(192, 168, 0, 3));
		$this->assertEquals(false, $ipc->check(192, 168, 0, 4));
		$this->assertEquals(true, $ipc->check(192, 168, 0, 5));
	}
	
	public function testCheckRanges () {
		$ipc = new IPsChecker(['192.168.0.5-20']);
		for ($a=0; $a<=4; $a++) {
			$this->assertEquals(false, $ipc->check(192, 168, 0, $a));
		}
		for ($a=5; $a<=20; $a++) {
			$this->assertEquals(true, $ipc->check(192, 168, 0, $a));
		}
		for ($a=21; $a<=30; $a++) {
			$this->assertEquals(false, $ipc->check(192, 168, 0, $a));
		}
	}
	
	public function testCheckMultipleRanges () {
		$ipc = new IPsChecker(['192.168.0.1-10,20-30', '192.168.0.40-50']);
		
		$this->assertEquals(true, $ipc->check(192, 168, 0, 1));

		for ($a=0; $a<=100; $a++) {
			$expected = (($a>=1 && $a<=10) or ($a>=20 && $a<=30) or ($a>=40 && $a<=50));
			$this->assertEquals($expected, $ipc->check(192, 168, 0, $a), "a=$a");
		}
	}
	
	public function testCheckStars () {
		$ipc = new IPsChecker(['192.168.0.*']);
		for ($a=0; $a<=255; $a++) {
			$this->assertEquals(true, $ipc->check(192, 168, 0, $a), "a=$a");
		}
		$this->assertEquals(false, $ipc->check(192, 168, 1, 1));
	}
	
	public function testCheckString () {
		$ipc = new IPsChecker(['192.168.0,1.1-10']);
		$this->assertEquals(true, $ipc->checkString('192.168.0.1'));
		$this->assertEquals(false, $ipc->checkString('192.200.0.1'));
		$this->assertEquals(true, $ipc->checkString('192.168.1.5'));
		$this->assertEquals(false, $ipc->checkString('192.168.1.0'));
		$this->assertEquals(false, $ipc->checkString('192.168.0.50'));
	}
	
	public function testCheckPacked () {
		$ipc = new IPsChecker(['192.168.0,1.1-10']);
		$this->assertEquals(true, $ipc->checkPacked(3232235521));
		$this->assertEquals(false, $ipc->checkPacked(3234332673));
		$this->assertEquals(true, $ipc->checkPacked(3232235781));
		$this->assertEquals(false, $ipc->checkPacked(3232235776));
		$this->assertEquals(false, $ipc->checkPacked(3232235570));
	}
	
	public function testErrorOnInvalidIPs () {
		$this->expectException(\InvalidArgumentException::class);
		new IPsChecker(['192.168.0.x']);
		new IPsChecker(['192.168.0.1', '192.168.0.2', '192.168.0.3 ']);
	}
}
