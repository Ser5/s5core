<?
namespace S5\RunLoggers;



class GroupRunLogger extends BaseRunLogger {
	/**
	 * @param IRunLogger[] $loggersList
	 */
	public function __construct (
		protected array $loggersList = []
	) {
		$this->loggersList = $loggersList;
	}



	public function get (string $message, string|false $type = false, string|int|false $level = false): mixed {
		$dataList = [];
		foreach ($this->loggersList as $logger) {
			$dataList[] = $logger->get($message, $type, $level);
		}
		return $dataList;
	}



	public function log (string $message, string|false $type = false, string|int|false $level = false) {
		foreach ($this->loggersList as $logger) {
			$logger->log($message, $type, $level);
		}
	}



	public function group (string|false $message = false, string|false $type = false, \Closure|false $callback = false) {
		foreach ($this->loggersList as $logger) {
			$logger->group($message, $type, $callback);
		}
	}

	public function groupEnd () {
		foreach ($this->loggersList as $logger) {
			$logger->groupEnd();
		}
	}



	public function pushLogger (IRunLogger $logger) {
		$this->loggersList[] = $logger;
	}

	public function getLogger (int $index) {
		return $this->loggersList[$index] ?? null;
	}
}
