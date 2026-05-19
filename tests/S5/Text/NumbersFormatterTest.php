<?
namespace S5\Text;



class NumbersFormatterTest extends \S5\TestCase {
	public function test () {
		$f = new NumbersFormatter();

		//Длина числа и шаблона совпадают
		$this->assertEquals('12-34-56', $f->formatWithPlaceholders('123456', 'xx-xx-xx'));
		$this->assertEquals('12-34-56', $f->formatWithPlaceholders('123456', 'nn-nn-nn'));

		//Длина числа и шаблона не совпадают
		$this->assertEquals('12-34-5',   $f->formatWithPlaceholders('12345',   'xx-xx-xx'));
		$this->assertEquals('12-34-567', $f->formatWithPlaceholders('1234567', 'xx-xx-xx'));

		//Дополнительные символы в исходном числе
		$this->assertEquals('12-34-56', $f->formatWithPlaceholders('123-456',     'xx-xx-xx'));
		$this->assertEquals('12-34-56', $f->formatWithPlaceholders(' 12a3b4c56 ', 'xx-xx-xx'));

		//Точная длина, диапазон
		$formatsMap = [
			'5'   => 'xxx-xx',
			'6-7' => 'xx-xx-xxx',
			'11'  => '+x (xxx) xxx-xx-xx',
		];
		$this->assertEquals('123-45',             $f->formatWithPlaceholders(12345,         $formatsMap));
		$this->assertEquals('12-34-56',           $f->formatWithPlaceholders(123456,        $formatsMap));
		$this->assertEquals('12-34-567',          $f->formatWithPlaceholders(1234567,       $formatsMap));
		$this->assertEquals('+7 (987) 123-45-67', $f->formatWithPlaceholders('79871234567', $formatsMap));

		//Формат не найден - возврат того, что было
		$this->assertEquals('1234', $f->formatWithPlaceholders(1234, $formatsMap));

		//Меньше/больше
		$formatsMap = [
			'<=7' => 'xx-xx-xxx',
			'>7'  => 'xxx-xxx-xxx',
		];
		$this->assertEquals('12-34-56',     $f->formatWithPlaceholders('123456',     $formatsMap));
		$this->assertEquals('12-34-567',    $f->formatWithPlaceholders('1234567',    $formatsMap));
		$this->assertEquals('123-456-78',   $f->formatWithPlaceholders('12345678',   $formatsMap));
		$this->assertEquals('123-456-789',  $f->formatWithPlaceholders('123456789',  $formatsMap));
		$this->assertEquals('123-456-7890', $f->formatWithPlaceholders('1234567890', $formatsMap));

		//Формат по умолчанию
		$formatsMap = [
			''  => 'xx-xx-xx-xx-xx',
			'6' => 'xxx-xxx',
		];
		$this->assertEquals('123-456',        $f->formatWithPlaceholders('123456',     $formatsMap));
		$this->assertEquals('12-34-5',        $f->formatWithPlaceholders('12345',      $formatsMap));
		$this->assertEquals('12-34-56-7',     $f->formatWithPlaceholders('1234567',    $formatsMap));
		$this->assertEquals('12-34-56-78-90', $f->formatWithPlaceholders('1234567890', $formatsMap));

		//Меньше/больше с кривым синтаксисом
		$this->assertException(fn() => $f->formatWithPlaceholders('123456', ['<<1']));
	}
}
