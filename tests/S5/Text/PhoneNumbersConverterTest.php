<?
namespace S5\Text;

class PhoneNumbersConverterTest extends \S5\TestCase {
	private $_numberStringsList = array(
		'987-811-22-33',
		'9878112233',
		9878112233,
		'(987) 811 22 33',
		'9878-11-22-33',
		' 9878  112-233 ',
		' 8 9878  112-233 ',
		'  +7 9878  112-233  ',
	);



	public function testGetSolid () {
		foreach ($this->_numberStringsList as $numberString) {
			$this->assertEquals(
				'9878112233',
				PhoneNumbersConverter::getSolid($numberString)
			);
		}
	}



	public function testFormat () {
		foreach ($this->_numberStringsList as $numberString) {
			$this->assertEquals(
				'9878112233',
				PhoneNumbersConverter::format($numberString, 'dddddddddd')
			);
			$this->assertEquals(
				'987-811-22-33',
				PhoneNumbersConverter::format($numberString, 'ddd-ddd-dd-dd')
			);
			$this->assertEquals(
				'(987) 811 22 33',
				PhoneNumbersConverter::format($numberString, '(ddd) ddd dd dd')
			);
			$this->assertEquals(
				'9878-11-22-33',
				PhoneNumbersConverter::format($numberString, 'dddd-dd-dd-dd')
			);
			$this->assertEquals(
				'987',
				PhoneNumbersConverter::format($numberString, 'ddd')
			);
		}
	}



	public function testInvalidNumbers () {
		foreach (['123456789', '1234 56 78', '(123) 45-67'] as $numberString) {
			$this->assertException(
				fn() => PhoneNumbersConverter::getSolid($numberString)
			);
			$this->assertException(
				fn() => PhoneNumbersConverter::format($numberString, 'dddd-dd-dd-dd')
			);
		}
		$this->assertTrue(true);
	}



	public function testTooLongFormatString () {
		$this->expectException(\InvalidArgumentException::class);
		PhoneNumbersConverter::format('9878-11-22-33', 'd-dddd-dd-dd-dd');
	}
}
