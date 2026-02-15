<?
namespace S5\RunLoggers;

abstract class BaseRunLogger implements IRunLogger {
	const OK      = 'ok';
	const INFO    = 'info';
	const WARNING = 'warning';
	const ERROR   = 'error';
	const CLOSE   = 'close';

	protected int $level = 1;



	public function log (string $message, string|false $type = false, string|int|false $level = false) {
		echo $this->get($message, $type, $level);
	}



	public function ok      (string $message, string|int|false $level = false) { $this->log($message, 'ok',      $level); }
	public function error   (string $message, string|int|false $level = false) { $this->log($message, 'error',   $level); }
	public function warning (string $message, string|int|false $level = false) { $this->log($message, 'warning', $level); }
	public function info    (string $message, string|int|false $level = false) { $this->log($message, 'info',    $level); }



	public function group (string|false $message = false, string|false $type = false, \Closure|false $callback = false) {
		if ($message !== false) {
			$this->log($message, $type);
		}
		$this->level++;
		if ($callback) {
			try {
				$callback();
			} finally {
				$this->groupEnd();
			}
		}
	}

	public function groupEnd () {
		if ($this->level > 1) {
			$this->level--;
		}
	}

	protected function calcAbsLevel (string|int|false $level): int {
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
