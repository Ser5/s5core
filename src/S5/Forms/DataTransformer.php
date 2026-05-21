<?
namespace S5\Forms;

use S5\Text\Lang;



class DataTransformer {
	public function __construct (
		protected ?Lang $lang            = null,
		protected array $phoneFormatsMap = [],
	) {
		if (!$this->lang) {
			$this->lang = new Lang();
		}
	}



	public function trim (string $text): string {
		return trim($text);
	}

	public function number (string $number): int {
		return (int)trim($number);
	}


	public function floatToDb (float|string $number): float {
		return (float)str_replace(',', '.', trim($number));
	}

	public function floatToForm (float|string $number): string {
		return str_replace('.', ',', trim($number));
	}



	public function dateToDb (string|int $date): string {
		$date = trim($date);
		$ts   = !ctype_digit("$date") ? strtotime($date) : $date;
		return date('Y-m-d', $ts);
	}

	public function dateToForm (string $date): string {
		return date('d.m.Y', strtotime(trim($date)));
	}



	public function datetimeToDb (string|int $datetime): string {
		$datetime = trim($datetime);
		$ts       = !ctype_digit("$datetime") ? strtotime($datetime) : $datetime;
		return date('Y-m-d H:i:s', $ts);
	}

	public function datetimeToForm (string $datetime): string {
		return date('d.m.Y H:i:s', strtotime(trim($datetime)));
	}



	public function phoneToDb (string $phone): string {
		return preg_replace('/\D/', '', $phone);
	}

	public function phoneToForm (string $phone): string {
		$phone = preg_replace('/\D/', '', $phone);
		return strlen($phone) <= 7
			? $phone
			: $this->lang->formatPhone($phone)
		;
	}
}
