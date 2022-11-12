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
 *
 * Для целей тестирования можно переназначить функцию, возвращающую текущее время,
 * чтобы это был не time() - через setTimeGetter().
 *
 * resetTimeGetter() возвращает time() на место.
 */
class Progress {
	protected \Closure $timeGetter;
	protected int      $unitsAmount;
	protected int      $progress;
	protected int      $startTime;


	public function __construct (array $params) {
		$this->timeGetter  = $params['time_getter']  ?? fn()=>time();
		$this->unitsAmount = $params['units_amount'] ?? 100;
		$this->startTime   = $params['start_time']   ?? ($this->timeGetter)();
		$this->progress    = $params['progress']     ?? 0;
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
		return round($this->progress / $this->unitsAmount * 100, $precision, PHP_ROUND_HALF_DOWN);
	}

	public function getLeftPercents (int $precision = 0): int {
		return 100 - $this->getElapsedPercents($precision);
	}



	public function getElapsedTime (): int {
		return ($this->timeGetter)() - $this->startTime;
	}

	public function getLeftTime (): int {
		$elapsedFraction = $this->progress / $this->unitsAmount;
		$leftFraction    = 1 - $elapsedFraction;
		$ratio           = $leftFraction / $elapsedFraction;
		$leftTime        = (($this->timeGetter)() - $this->startTime) * $ratio;
		return round($leftTime, 0, PHP_ROUND_HALF_DOWN);
	}

	/**
	 * Возвращает данные по оставшемуся времени: всего секунд, часы, минуты, секунды, строку со вмененем вида "12:34:56".
	 */
	public function getLeftTimeData (): ProgressTimeData {
		return new ProgressTimeData($this->getLeftTime());
	}

	public function getTotalTime (): int {
		return $this->getElapsedTime() + $this->getLeftTime();
	}
}
