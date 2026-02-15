<?
namespace S5\Xtrabackup;



class DateTime {
	public int    $timestamp;
	public string $datetimeString;

	public int    $dateHourTimestamp;
	public string $dateHourString;

	public int    $dateTimestamp;
	public string $dateString;

	public string $hourString;



	public function __construct (int|string|DateTime $datetime) {
		$ts = false;
		if (is_a($datetime, DateTime::class)) {
			$ts = $datetime->timestamp;
		} elseif (ctype_digit("$datetime")) {
			$ts = (int)$datetime;
		} else {
			$strlen = strlen($datetime);
			if ($strlen == 10 or $strlen == 19) {
				$ts = strtotime($datetime);
			} elseif ($strlen == 13) {
				$ts = strtotime(str_replace('_', ' ', $datetime) . ':00:00');
			}
		}
		if (!$ts) {
			throw new \InvalidArgumentException("Неверная дата/время: $datetime");
		}
		$this->timestamp = $ts;

		$this->setFieldValues();
	}



	public function addSeconds (int $secondsCount) {
		$this->timestamp += $secondsCount;
		$this->setFieldValues();
	}

	public function subSeconds (int $secondsCount) {
		$this->addSeconds(-$secondsCount);
	}



	protected function setFieldValues () {
		$this->datetimeString = date('Y-m-d H:i:s', $this->timestamp);

		$this->dateHourString    = date('Y-m-d_H', $this->timestamp);
		$this->dateHourTimestamp = strtotime(substr($this->datetimeString, 0, 13) . ':00:00');

		$this->dateString    = substr($this->datetimeString, 0, 10);
		$this->dateTimestamp = strtotime($this->dateString);

		$this->hourString = substr($this->dateHourString, 11, 2);
	}
}
