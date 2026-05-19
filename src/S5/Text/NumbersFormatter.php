<?
namespace S5\Text;



class NumbersFormatter {
	/**
	 * Форматирование числа по шаблону, типа "xx-xx-xx".
	 *
	 * В качестве символов для подстановки можно использовать любые буквы a-z:
	 * - xx-xx-xx
	 * - nn-nn-nn
	 * - итд
	 *
	 * ```
	 * $f->formatWithPlaceholders(123456,    'xx-xx-xx');   //12-34-56
	 * $f->formatWithPlaceholders('123 456', 'xx-xx-xx');   //12-34-56
	 * $f->formatWithPlaceholders(123456,    'xx-x');       //12-3456
	 *
	 * $formatsMap = [
	 *    '5'   => 'x-xxx',
	 *    '6-7' => 'xx-xx-xxx',
	 *    '11'  => '+x (xxx) xxx-xx-xx',
	 * ];
	 * $f->formatWithPlaceholders(12345,         $formatsMap);   //1-2345
	 * $f->formatWithPlaceholders(123456,        $formatsMap);   //12-34-56
	 * $f->formatWithPlaceholders(1234567,       $formatsMap);   //12-34-567
	 * $f->formatWithPlaceholders('79871234567', $formatsMap);   //+7 (987) 123-45-67
	 *
	 * $formatsMap = [
	 *    '<=7' => 'xx-xx-xxx',
	 *    '>7'  => 'xxx-xxx-xxx',
	 * ];
	 * $f->formatWithPlaceholders('123456',     $formatsMap);   //12-34-56
	 * $f->formatWithPlaceholders('1234567',    $formatsMap);   //12-34-567
	 * $f->formatWithPlaceholders('12345678',   $formatsMap);   //123-456-78
	 * $f->formatWithPlaceholders('123456789',  $formatsMap);   //123-456-789
	 * $f->formatWithPlaceholders('1234567890', $formatsMap);   //123-456-7890
	 *
	 * $formatsMap = [
	 *    ''  => 'xx-xx-xx-xx-xx', //Ловит всё, не попавшее под другие правила
	 *    '6' => 'xxx-xxx',
	 * ];
	 * $f->formatWithPlaceholders('123456',     $formatsMap);   //123-456
	 * $f->formatWithPlaceholders('12345',      $formatsMap);   //12-34-5
	 * $f->formatWithPlaceholders('1234567',    $formatsMap);   //12-34-56-7
	 * $f->formatWithPlaceholders('1234567890', $formatsMap);   //12-34-56-78-90
	 * ```
	 */
	public function formatWithPlaceholders (int|string $number, string|array $format): string {
		$r = '';

		//number
		if (is_string($number)) {
			$number = preg_replace('/\D/', '', $number);
		}
		$numberString = (string)$number;
		$numberLength = strlen($numberString);

		//formats
		$formatsMap = (is_string($format) ? [''=>$format] : $format);

		//Обработка форматов
		$isLengthMatched     = false;
		$defaultFormatString = '';
		foreach ($formatsMap as $lengthString => $formatString) {
			if ($lengthString === '') {
				$defaultFormatString = $formatString;
			}
			if ($lengthString) {
				$isRange = str_contains($lengthString, '-');
				$isMath  = (bool)preg_match('/[<=>]/', $lengthString);
				if (!$isRange and !$isMath and $numberLength == $lengthString) {
					//Конкретная длина, типа "6" - форматируем числа с точно такой длиной
					$isLengthMatched = true;
				} else {
					if ($isRange) {
						//Диапазон, типа "5-7"
						[$min, $max] = explode('-', $lengthString);
						if ($numberLength >= $min and $numberLength <= $max) {
							$isLengthMatched = true;
						}
					} elseif ($isMath) {
						//Форматы типа ">=5", "<8"
						$r = preg_match('/([<=>]+)(\d+)/ui', $lengthString, $matches);
						[, $math, $value] = $matches;
						$isLengthMatched = match ($math) {
							'<'     => $numberLength <  $value,
							'<='    => $numberLength <= $value,
							'>'     => $numberLength >  $value,
							'>='    => $numberLength >= $value,
							default => throw new \InvalidArgumentException("Ошибка в длине строки: $lengthString"),
						};
					}
				}
			}
			if ($isLengthMatched) {
				//Конкретное правило нашлось - форматируем по нему
				$r = $this->formatOneWithPlaceholders($numberString, $formatString);
				break;
			}
		}

		if (!$isLengthMatched) {
			if ($defaultFormatString) {
				//По конкретным правилам ничего не нашлось,
				//но есть шаблон по умолчанию - с пустой строкой длины - используем
				//его по умолчанию
				$r = $this->formatOneWithPlaceholders($numberString, $defaultFormatString);
			} else {
				//Вообще ничего не подошло - возвращаем число как есть
				$r = $numberString;
			}
		}

		return $r;
	}



	protected function formatOneWithPlaceholders (string $numberString, string $formatString): string {
		$r = '';

		$numberLength = strlen($numberString);
		$numberIndex  = 0;
		foreach (str_split($formatString, 1) as $e) {
			if (!preg_match('/\w/ui', $e)) {
				$r .= $e;
			} else {
				$r .= $numberString[$numberIndex];
				$numberIndex++;
			}
			if ($numberIndex >= $numberLength) {
				break;
			}
		}

		if ($numberIndex < $numberLength) {
			$r .= substr($numberString, $numberIndex);
		}

		return $r;
	}
}
