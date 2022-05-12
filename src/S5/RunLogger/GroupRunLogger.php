<?
namespace S5\RunLogger;

class GroupRunLogger extends BaseRunLogger {
	protected $loggersList;

	public function __construct (array $loggersList = []) {
		$this->loggersList = $loggersList;
	}



	public function get ($message, $type = false, $level = false) {
		$dataList = [];
		foreach ($this->loggersList as $logger) {
			$dataList[] = $logger->get($message, $type, $level);
		}
		return $dataList;
	}



	public function log ($message, $type = false, $level = false) {
		foreach ($this->loggersList as $logger) {
			$logger->log($message, $type, $level);
		}
	}



	public function group ($message = false, $type = false, $level = false) {
		foreach ($this->loggersList as $logger) {
			$logger->group($message, $type, $level);
		}
	}

	public function groupEnd () {
		foreach ($this->loggersList as $logger) {
			$logger->groupEnd();
		}
	}



	public function pushLogger (BaseRunLogger $logger) {
		$this->loggersList[] = $logger;
	}

	public function getLogger ($key) {
		return $this->loggersList[$key] ?? null;
	}
}
