<?
namespace S5\RunLogger;

class EmptyRunLogger extends BaseRunLogger {
	public function get ($message, $type = false, $level = 1) { return false; }
	public function log ($message, $type = false, $level = 1) {}
}
