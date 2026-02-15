<?
namespace S5\RunLoggers;

class EmptyRunLogger extends BaseRunLogger {
	public function get (string $message, string|false $type = false, string|int|false $level = false): mixed { return false; }
	public function log (string $message, string|false $type = false, string|int|false $level = false) {}
}
