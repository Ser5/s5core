<?
namespace S5\RunLoggers;

class ArrayRunLogger extends BaseRunLogger {
	protected array $outputList = [];


	public function get (string $message, string|false $type = false, string|int|false $level = false): mixed {
		return [
			'message' => $message,
			'type'    => $type,
			'level'   => $this->calcAbsLevel($level),
		];
	}



	public function log (string $message, string|false $type = false, string|int|false $level = false) {
		$this->outputList[] = $this->get($message, $type, $level);
	}



	public function getOutputList (): array {
		return $this->outputList;
	}

	public function clearOutputList () {
		$this->outputList = [];
	}
}
