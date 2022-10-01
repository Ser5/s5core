<?
namespace S5\RunLoggers;

abstract class BaseRunLogger implements IRunLogger {
	const OK      = 'ok';
	const INFO    = 'info';
	const WARNING = 'warning';
	const ERROR   = 'error';
	const CLOSE   = 'close';

	protected $level = 1;

	abstract public function get ($message, $type = false, $level = false);



	public function log ($message, $type = false, $level = false) {
		echo $this->get($message, $type, $level);
	}



	public function ok      (string $message, $level = false) { $this->log($message, 'ok',      $level); }
	public function error   (string $message, $level = false) { $this->log($message, 'error',   $level); }
	public function warning (string $message, $level = false) { $this->log($message, 'warning', $level); }
	public function info    (string $message, $level = false) { $this->log($message, 'info',    $level); }



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
