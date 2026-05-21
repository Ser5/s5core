<?
namespace S5\Forms;



class DataTransformerTest extends \S5\TestCase {
	public function test () {
		$df = new DataTransformer();

		$this->assertEquals('text', $df->trim(" text\n"));
		$this->assertEquals(123,    $df->number(" 123\n"));

		$this->assertEquals(12.3,   $df->floatToDb(" 12.3\n"));
		$this->assertEquals(12.3,   $df->floatToDb(" 12,3\n"));
		$this->assertEquals('12,3', $df->floatToForm("12,3"));
		$this->assertEquals('12,3', $df->floatToForm("12,3"));

		$this->assertEquals('2000-01-30', $df->dateToDb('30.01.2000'));
		$this->assertEquals('2000-01-30', $df->dateToDb('30.01.2000 00:00:00'));
		$this->assertEquals('30.01.2000', $df->dateToForm('2000-01-30'));
		$this->assertEquals('30.01.2000', $df->dateToForm('2000-01-30 00:00:00'));

		$this->assertEquals('2000-01-30 00:00:00', $df->datetimeToDb('30.01.2000'));
		$this->assertEquals('2000-01-30 00:00:00', $df->datetimeToDb('30.01.2000 00:00:00'));
		$this->assertEquals('30.01.2000 00:00:00', $df->datetimeToForm('2000-01-30'));
		$this->assertEquals('30.01.2000 00:00:00', $df->datetimeToForm('2000-01-30 00:00:00'));

		$this->assertEquals('111111',             $df->phoneToDb(" 11-11-11\n"));
		$this->assertEquals('111111',             $df->phoneToForm("111111"));
		$this->assertEquals('+7 (987) 123-45-67', $df->phoneToForm("79871234567"));
	}
}
