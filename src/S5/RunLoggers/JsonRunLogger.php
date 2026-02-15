<?
namespace S5\RunLoggers;

class JsonRunLogger extends BaseRunLogger {
	public function get (string $message, string|false $type = false, string|int|false $level = false): mixed {
		return json_encode([
			'message' => $message,
			'type'    => $type,
			'level'   => $this->calcAbsLevel($level),
		]);
	}
}
