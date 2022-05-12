<?
namespace S5\RunLogger;

abstract class BaseRunLogger {
	const OK      = 'ok';
	const INFO    = 'info';
	const WARNING = 'warning';
	const ERROR   = 'error';
	const CLOSE   = 'close';

	protected $level = 1;

	/**
	 * Возвращает данные для записи в лог.
	 *
	 * ```
	 * $runLogger->log('Здарофки', 2, 'ok');
	 * $runLogger->log('Здарофки', 2, $runLogger::OK);
	 * ```
	 *
	 * $level это уровень вложенности сообщения.
	 * Начинаются уровни с 1.
	 *
	 * Уровень можно не указывать, тогда будет использован текущий,
	 * установленный методами group() и groupEnd().
	 * ```
	 * $runLogger->log('Сообщенька');
	 * $runLogger->log('Сообщенька об ошибке', false, 'error');
	 * $runLogger->group();
	 * $runLogger->log('Сообщенька уровнем глубже');
	 * $runLogger->groupEnd();
	 * ```
	 *
	 * Можно указать уровень явно:
	 * ```
	 * $runLogger->log('Сообщенька', 2);
	 * ```
	 *
	 * Можно указать уровень относительно:
	 * ```
	 * $runLogger->log('Сообщенька уровнем глубже',   '+1');
	 * $runLogger->log('Сообщенька вровень выпирает', '-1');
	 * ```
	 *
	 * @param  string           $message
	 * @param  string|false     $type
	 * @param  string|int|false $level
	 * @return mixed
	 */
	abstract public function get ($message, $type = false, $level = false);



	/**
	 * Пишет лог с данными, возвращёнными get().
	 *
	 * @param string           $message
	 * @param string|false     $type
	 * @param string|int|false $level
	 */
	public function log ($message, $type = false, $level = false) {
		echo $this->get($message, $type, $level);
	}



	public function ok      (string $message) { $this->log($message, 'ok',      false); }
	public function error   (string $message) { $this->log($message, 'error',   false); }
	public function warning (string $message) { $this->log($message, 'warning', false); }
	public function info    (string $message) { $this->log($message, 'info',    false); }



	/**
	 * @param string|false $message
	 * @param string|false $type
	 */
	public function group ($message = false, $type = false) {
		if ($message !== false) {
			$this->log($message, $type);
		}
		$this->level++;
	}

	public function groupEnd () {
		if ($this->level > 1) {
			$this->level--;
		}
	}

	/**
	 * @param  string|int $level
	 * @return int
	 */
	protected function calcAbsLevel ($level) {
		$matches = [];
		if (!$level) {
			$level = $this->level;
		} elseif (preg_match('/^([\+\-])(\d+)$/', $level, $matches)) {
			$level = ($matches[1] == '+')
				? $this->level + (int)$matches[2]
				: $this->level - (int)$matches[2];
		}
		return $level;
	}
}
