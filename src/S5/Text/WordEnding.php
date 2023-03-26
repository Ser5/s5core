<?
namespace S5\Text;

class WordEnding {
	public static function get (int $number): int {
		$absNumber = (string)abs($number);
		//Проверить, если последние две цифры составляют число от 10 до 20.
		if ($absNumber >= 10) {
			$ten = substr($absNumber, mb_strlen($absNumber, 'UTF-8') - 2, 2);
			if ($ten >= 10 and $ten <= 20) {
				return 3;
			}
		}
		//Проверки последней цифры.
		$lastDigit = (int)substr($absNumber, mb_strlen($absNumber, 'UTF-8') - 1, 1);
		if ($lastDigit == 1) {
			return 1;
		} elseif ($lastDigit == 2 or $lastDigit == 3 or $lastDigit == 4) {
			return 2;
		} else {
			return 3;
		}
	}

	public static function getWord (int $number, string $variant1, string $variant2, string $variant3): string {
		switch (static::get($number)) {
			case 1: return $variant1;
			case 2: return $variant2;
			case 3: return $variant3;
			default: throw new \InvalidArgumentException("Неизвестный вариант количества: [$number]");
		}
	}
}
