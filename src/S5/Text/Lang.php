<?
namespace S5\Text;

use libphonenumber\PhoneNumberUtil;



class Lang {
	protected PhoneNumberUtil $phoneFormatter;

	public function __construct (
		protected string $locale = 'ru',
	) {
		$this->phoneFormatter = PhoneNumberUtil::getInstance();
	}



	/**
	 * Форматирование через \MessageFormatter::formatMessage() с предустановленной локалью.
	 */
	public function format (string $pattern, array $varsMap = []): string {
		return \MessageFormatter::formatMessage($this->locale, $pattern, $varsMap);
	}



	/**
	 * Возвращает строку в единственном/множественном числе.
	 *
	 * Примеры:
	 * ```
	 * //Без аргументов
	 * $lang->pluralize(3); //Пустая строка
	 *
	 * //Один вариант
	 * $lang->pluralize(3, 'штука'); //3 штука
	 *
	 * //Два варианта
	 * $lang->pluralize(1, 'штука', 'штук');  //1 штука
	 * $lang->pluralize(3, 'штука', 'штук');  //3 штук
	 * $lang->pluralize(5, 'штука', 'штук');  //5 штук
	 * $lang->pluralize(1, 'unit',  'units'); //1 unit
	 * $lang->pluralize(3, 'unit',  'units'); //3 units
	 * $lang->pluralize(5, 'unit',  'units'); //5 units
	 *
	 * //Три варианта
	 * $lang->pluralize(1, 'штука', 'штуки', 'штук');   //1 штука
	 * $lang->pluralize(3, 'штука', 'штуки', 'штук');   //3 штуки
	 * $lang->pluralize(5, 'штука', 'штуки', 'штук');   //5 штук
	 * $lang->pluralize(1, 'unit',  'units', 'unitaz'); //1 unit
	 * $lang->pluralize(3, 'unit',  'units', 'unitaz'); //3 units
	 * $lang->pluralize(5, 'unit',  'units', 'unitaz'); //5 units
	 * ```
	 */
	public function pluralize (int $count, string $variant1 = '', string $variant2 = '', string $variant3 = ''): string {
		if (!$variant1 and !$variant2 and !$variant3) {
			return '';
		}

		if ($variant1 and !$variant2 and !$variant3) {
			return $variant1;
		}

		$pattern = "count, plural, one{$variant1} ";
		if ($variant1 and $variant2 and !$variant3) {
			$pattern .= "other{$variant2}";
		} else {
			$pattern .= "few{$variant2} other{$variant3}";
		}

		return $this->format($pattern, ['count' => $count]);
	}



	public function formatPhone (string $phone): string {
		$proto = $this->phoneFormatter->parse($phone, $this->locale);
		$r     = $this->phoneFormatter->formatOutOfCountryCallingNumber($proto, $this->locale);
		//libphonenumber\PhoneNumberUtil для России может возвращать результат в виде:
		//- +79871234567
		//- 8 (987) 123-45-67
		//- +7 987 123-45-67
		//Тогда как нам нужно +7 (987) 123-45-67.
		//Заменяем "8" на "+7".
		if ($this->locale == 'ru' and $r[0] == '8') {
			$r = substr_replace($r, '+7', 0, 1);
		}
		return $r;
	}
}
