<?
namespace S5\RunLogger;

class JsonRunLogger extends BaseRunLogger {
	public function get ($message, $type = false, $level = false) {
		return json_encode([
			'message' => $message,
			'type'    => $type,
			'level'   => $this->calcAbsLevel($level),
		]);
	}
}
