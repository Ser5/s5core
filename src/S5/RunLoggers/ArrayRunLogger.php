<?
namespace S5\RunLoggers;

class ArrayRunLogger extends BaseRunLogger {
	protected array $outputList = [];


	public function get ($message, $type = false, $level = false) {
		return [
			'message' => $message,
			'type'    => $type,
			'level'   => $this->calcAbsLevel($level),
		];
	}



	public function log ($message, $type = false, $level = false) {
		$this->outputList[] = $this->get($message, $type, $level);
	}



	public function getOutputList (): array {
		return $this->outputList;
	}

	public function clearOutputList () {
		$this->outputList = [];
	}
}
