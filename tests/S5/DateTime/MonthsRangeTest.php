<?
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'S5/DateTime/Month.php';
require_once 'S5/DateTime/MonthsRange.php';

class S5_DateTime_MonthsRangeTest extends \PHPUnit\Framework\TestCase {
	public function test () {
		$l = new S5_DateTime_MonthsRange('2013-05', '2013-10');
		$this->assertEquals(6, $l->count());

		//Май
		$this->assertEquals('2013-05-01', $l[0]->getStartDateString());
		$this->assertEquals(3,            $l[0]->getFirstWeekDay());
		$this->assertEquals(31,           $l[0]->getDaysAmount());
		$this->assertEquals('2013-05-31', $l[0]->getEndDateString());

		//Июнь
		$this->assertEquals('2013-06-01', $l[1]->getStartDateString());
		$this->assertEquals(6,            $l[1]->getFirstWeekDay());
		$this->assertEquals(30,           $l[1]->getDaysAmount());
		$this->assertEquals('2013-06-30', $l[1]->getEndDateString());

		//Октябрь
		$this->assertEquals('2013-10-01', $l[5]->getStartDateString());
		$this->assertEquals(2,            $l[5]->getFirstWeekDay());
		$this->assertEquals(31,           $l[5]->getDaysAmount());
		$this->assertEquals('2013-10-31', $l[5]->getEndDateString());

		$this->assertEquals($l[5], $l->getLast());
	}
}
