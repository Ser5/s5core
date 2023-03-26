<?
namespace S5\Net;

/**
 * Гибкая проверка IP-адресов по маске.
 *
 * Маска может выглядеть как обычный IP-адрес:
 * `192.168.0.1`
 *
 * Любая из четырёх частей маски может содержать несколько значений,
 * разделённых запятыми:
 * `192.168.0.1,2,3,4,5`
 *
 * Того же значения можно добиться использованием диапазонов:
 * `192.168.0.1-5`
 *
 * Диапазоны можно комбинировать с использованием запятых:
 * `192.168.0.1,3,5,10-20,30-100,105,200`
 *
 * Звёздочка, поставленная вместо какой-либо части маски,
 * обозначает любое значение:
 * `192.168.*.*`
 */
class IPsChecker {
	protected array $allowedIPs = [];

	/**
	 * Конструктор.
	 *
	 * Принимает список масок IP-адресов, например:
	 * ```
	 * new IPsChecker(['1.1.1.1', '1.1.1.10', '1.1.1.50-60']);
	 * ```
	 */
	public function __construct (array $allowedIPs) {
		$this->allowedIPs = $allowedIPs;

		if (count($this->allowedIPs) == 0) {
			throw new \InvalidArgumentException("Должна быть передана хотя бы одна маска.");
		}

		foreach ($this->allowedIPs as $ip) {
			static $ap = '[\d,*-]{1,}';
			if (!preg_match("|^$ap\\.$ap\\.$ap\\.$ap$|", $ip)) {
				throw new \InvalidArgumentException("Аргумент $ip не является правильной маской.");
			}
		}
	}



	/**
	 * Проверка IP-адреса, заданного четырьмя целыми числами.
	 *
	 * ```
	 * $ipc->check(192, 168, 0, 1);
	 * ```
	 *
	 * @param int|string $ip1
	 * @param int|string $ip2
	 * @param int|string $ip3
	 * @param int|string $ip4
	 */
	public function check ($ip1, $ip2, $ip3, $ip4): bool {
		$fourTestParts = array($ip1, $ip2, $ip3, $ip4);
		for ($ipx = 0; $ipx < count($this->allowedIPs); $ipx++) {
			//Сравниваем переданный адрес с одной из масок.
			if ($this->checkSingleMask($this->allowedIPs[$ipx], $fourTestParts)) {
				return true;
			}
		}
		//Цикл по маскам завершился. Если мы попали сюда, это означает,
		//что проверки всех масок провалились.
		return false;
	}



	protected function checkSingleMask (string $mask, array $fourTestParts): bool {
		//4 части: 192, 198, 0, 1
		$four_allowed_parts = explode('.', $mask);
		for ($partIx = 0; $partIx < 4; $partIx++) {
			//Если часть не прошла проверку, то вся маска тоже проверку не прошла.
			if (!$this->checkSinglePart($four_allowed_parts[$partIx], $fourTestParts[$partIx])) {
				return false;
			}
		}
		//Все четыре части маски прошли проверку,
		//значит проверка всей маски удалась. Можно возвращать true.
		return true;
	}



	protected function checkSinglePart (string $allowedPart, int $testPart): bool {
		//Если часть - звёздочка, то проверку можно пропустить,
		//она считается верной в любом случае.
		if ($allowedPart == '*') {
			return true;
		} else {
			//Разрешённые диапазоны, которые через запятую,
			//содержащиеся в одной из частей.
			$allowedRanges = explode(',', $allowedPart);
			//Проход по диапазонам.
			for ($range_ix = 0; $range_ix < count($allowedRanges); $range_ix++) {
				if ($this->checkSingleRange($allowedRanges[$range_ix], $testPart)) {
					return true;
				}
			}
			return false;
		}
	}



	protected function checkSingleRange (string $range, int $testPart): bool {
		//Берём один из разрешённых диапазонов
		if (strpos($range, '-')) {
			$min_max = explode('-', $range);
			$min = (int)$min_max[0];
			$max = (int)$min_max[1];
			//Если число попало в диапазон, остальные составляющие диапазона можно не проверять,
			//ведь часть сошлась.
			if ($testPart >= $min and $testPart <= $max) return true;
		} else {
			if ($testPart == $range) return true;
		}
		return false;
	}



	/**
	 * IP-адрес для проверки в виде строки "192.168.0.1"
	 */
	public function checkString (string $testIP): bool {
		$parts4 = explode('.', $testIP);
		return $this->check($parts4[0], $parts4[1], $parts4[2], $parts4[3]);
	}



	/**
	 * IP-адрес для проверки в виде упакованного длинного целого,
	 * подходящего для обработки функцией long2ip().
	 */
	public function checkPacked (int $testIP): bool {
		return $this->checkString(long2ip($testIP));
	}
}
