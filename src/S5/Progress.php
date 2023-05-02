<?
namespace S5;

/**
 * Для всяких прогрессбаров.
 *
 * Например, у нас есть 600 товаров.
 * Некий скрипт обрабатывает их все, тратя по 0,2 секунды на товар.
 *
 * Полностью обработка произойдёт за 120 секунд - или 2 минуты.
 *
 * Нам надо рисовать прогрессбар и писать, сколько времени осталось.
 * Прогрессбар имеет значения от 0 до 100.
 * Время отображается в секундах или их производных - минутах, часах итд.
 *
 * ```
 * $p = new Progress(600);
 * foreach ($productsList as $product) {
 *    processProduct($product);
 *    $p->add(1);
 *    drawProgressBar($p->getElapsedPercents());
 *    showSecondsLeft($p->getLeftTime());
 * }
 * ```
 *
 * Класс избавляет от необходимости:
 * - Вести подсчёт количества обработанных записей - можно просто
 *   прибавлять их методом add()
 * - Считать, сколько процентов составляет обработанное количество записей
 *   относительно общего - для этого есть метод getLeftPercents()
 * - Аналогично, считать, сколько осталось времени - используем getLeftTime()
 *
 * Названия методов сгруппированы по признаку прошло/осталось/всего:
 * - getElapsed***()
 * - getLeft***()
 * - getTotal***()
 *
 * И по единицам, которые надо получить - записи/проценты/время:
 * - get***Units()
 * - get***Percents()
 * - get***Time()
 */
class Progress {
	protected \Closure $timeGetter;
	protected int      $unitsAmount;
	protected int      $progress;
	protected int      $startTime;


	/**
	 * Ctor.
	 *
	 * @param array{
	 *    timeGetter?:  mixed,
	 *    unitsAmount?: int,
	 *    progress?:    int,
	 *    startTime?:   int|string,
	 * } $params
	 */
	public function __construct (array $params = []) {
		$this->timeGetter  = $params['timeGetter']  ?? fn()=>time();
		$this->unitsAmount = $params['unitsAmount'] ?? 100;
		$this->progress    = $params['progress']    ?? 0;

		if (!isset($params['startTime']) or !$params['startTime']) {
			$this->startTime = ($this->timeGetter)();
		} elseif (ctype_digit((string)$params['startTime'])) {
			$this->startTime = $params['startTime'];
		} elseif (is_string($params['startTime'])) {
			$this->startTime = strtotime($params['startTime']);
			if (!$this->startTime) {
				throw new \InvalidArgumentException("Не удалось разобрать startTime как строку даты-времени: [$params[startTime]]");
			}
		} else {
			throw new \InvalidArgumentException("Неизвестное значение startTime: [$params[startTime]]");
		}
	}



	public function set (int $value) {
		$this->progress = $value;
	}

	public function add (int $value) {
		$this->progress += $value;
	}

	public function restart () {
		$this->progress  = 0;
		$this->startTime = ($this->timeGetter)();
	}

	public function end () {
		$this->progress = $this->unitsAmount;
	}



	public function getElapsedUnits (): int {
		return $this->progress;
	}

	public function getLeftUnits (): int {
		return $this->unitsAmount - $this->progress;
	}

	public function getTotalUnits (): int {
		return $this->unitsAmount;
	}



	public function getElapsedPercents (int $precision = 0): int {
		return (int)round($this->progress / $this->unitsAmount * 100, $precision, PHP_ROUND_HALF_DOWN);
	}

	public function getLeftPercents (int $precision = 0): int {
		return 100 - $this->getElapsedPercents($precision);
	}



	public function getElapsedTime (): int {
		return ($this->timeGetter)() - $this->startTime;
	}

	/**
	 * Сколько секунд осталось до завершения.
	 *
	 * Если прогресс равен нулю - возвращает false.
	 *
	 * @return int|false
	 */
	public function getLeftTime () {
		if (!$this->progress) {
			return false;
		} else {
			$elapsedFraction = $this->progress / $this->unitsAmount;
			$leftFraction    = 1 - $elapsedFraction;
			$ratio           = $leftFraction / $elapsedFraction;
			$leftTime        = (($this->timeGetter)() - $this->startTime) * $ratio;
			return (int)round($leftTime, 0, PHP_ROUND_HALF_DOWN);
		}
	}

	/**
	 * Возвращает данные по оставшемуся времени: всего секунд, часы, минуты, секунды, строку со вмененем вида "12:34:56".
	 *
	 * Если прогресс равен нулю - возвращает false.
	 *
	 * @return ProgressTimeData|false
	 */
	public function getLeftTimeData () {
		$leftTimeData = $this->getLeftTime();
		return ($leftTimeData !== false ? new ProgressTimeData($leftTimeData) : false);
	}

	public function getTotalTime (): int {
		return $this->getElapsedTime() + $this->getLeftTime();
	}
}
