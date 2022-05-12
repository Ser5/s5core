<?
namespace S5\Text;

/**
 * Для форматирования телефонных номеров.
 */
class PhoneNumbersConverter {
	/**
	 * Возвращает номер в виде десяти сплошных цифр.
	 *
	 * Пример: передан номер 9878-11-22-33.<br>
	 * Метод вернёт 9878112233.
	 */
	public static function getSolid (string $numberString): string {
		$numberString = trim($numberString);
		$return       = preg_replace('/[^\d]/', '', $numberString);

		if (strlen($return) == 10) {
			return $return;
		} elseif (
			strlen($return) == 11 and
			($numberString[0] == '8' or substr($numberString,0,2) == '+7')
		) {
			return substr($return, 1);
		} else {
			throw new \InvalidArgumentException("Not a phone number");
		}
	}



	/**
	 * Форматирует номер телефона.
	 *
	 * Пример:
	 * ```
	 * NumbersConverter::format('9878112233', 'dddd-dd-dd-dd'); //9878-11-22-33
	 * ```
	 */
	public static function format (string $numberString, string $formatString): string {
		$return             = '';
		$numberString       = self::getSolid($numberString);
		$formatStringLength = strlen($formatString);
		$numberStringLength = strlen($numberString);

		for ($formatIx = 0, $numberIx = 0; $formatIx < $formatStringLength; $formatIx++) {
			if ($numberIx >= $numberStringLength) {
				throw new \InvalidArgumentException("Format string too long");
			}
			if ($formatString[$formatIx] == 'd') {
				$return .= $numberString[$numberIx++];
			} else {
				$return .= $formatString[$formatIx];
			}
		}

		return $return;
	}
}
