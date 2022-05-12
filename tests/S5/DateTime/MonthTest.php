<?
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'S5/DateTime/Month.php';

class S5_DateTime_MonthsListInfoTest extends \PHPUnit\Framework\TestCase {
	public function test () {
		//Май
		$m = new S5_DateTime_Month('2013-05');
		$this->assertEquals('2013-05',    (string)$m);
		$this->assertEquals(2013,         $m->getYearNumber());
		$this->assertEquals('2013-05-01', $m->getStartDateString());
		$this->assertEquals(3,            $m->getFirstWeekDay());
		$this->assertEquals(31,           $m->getDaysAmount());
		$this->assertEquals('2013-05-31', $m->getEndDateString());
		//Июнь
		$m = new S5_DateTime_Month('2013-06');
		$this->assertEquals('2013-06',    (string)$m);
		$this->assertEquals(2013,         $m->getYearNumber());
		$this->assertEquals('2013-06-01', $m->getStartDateString());
		$this->assertEquals(6,            $m->getFirstWeekDay());
		$this->assertEquals(30,           $m->getDaysAmount());
		$this->assertEquals('2013-06-30', $m->getEndDateString());

		$this->assertEquals('2013-05', (string)$m->getPrevious());
		$this->assertEquals('2013-06', (string)$m);
		$this->assertEquals('2013-07', (string)$m->getNext());
	}



	public function testConstruct () {
		$m1 = new S5_DateTime_Month('2000-01');
		$m2 = new S5_DateTime_Month('01.2000');
		$this->assertEquals((string)$m1, (string)$m2);
	}



	public function testInvalidConstruct () {
		$exceptionsAmount = 0;
		foreach (array('200001', '012000', 'jopa', '0', '-') as $monthString) {
			try {
				new S5_DateTime_Month($monthString);
			} catch (InvalidArgumentException $e) {
				$exceptionsAmount++;
			}
		}
		$this->assertEquals(5, $exceptionsAmount);
	}
}
