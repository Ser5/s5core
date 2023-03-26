<?
namespace S5\RunLoggers;

class ConsoleRunLogger extends BaseRunLogger {
	protected static string $ok      = "\033[0;32m";
	protected static string $info    = "\033[0;96m";
	protected static string $warning = "\033[0;33m";
	protected static string $error   = "\033[0;31m";
	protected static string $close   = "\033[0m";

	public function get ($message, $type = false, $level = false) {
		if ($type and isset(static::$$type)) {
			$message = static::$$type . $message . static::$close;
		}
		$indentString = str_repeat('   ', $this->calcAbsLevel($level)-1);
		$message      = preg_replace('/^/uim', "$1$indentString", $message);
		return "$message\n";
	}
}
