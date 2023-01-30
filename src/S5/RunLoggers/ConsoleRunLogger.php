<?
namespace S5\RunLogger;

class ConsoleRunLogger extends BaseRunLogger {
	private static $ok      = "\033[0;32m";
	private static $info    = "\033[0;96m";
	private static $warning = "\033[0;33m";
	private static $error   = "\033[0;31m";
	private static $close   = "\033[0m";

	public function get ($message, $type = false, $level = false) {
		if ($type and isset(static::$$type)) {
			$message = static::$$type . $message . static::$close;
		}
		$indentString = str_repeat('   ', $this->calcAbsLevel($level)-1);
		$message      = preg_replace('/^/uim', "$1$indentString", $message);
		return "$message\n";
	}
}
