<?
namespace S5\Cli;

class Utils {
	/**
	 * Обвес для exec().
	 *
	 * Отличия от `exec()`:
	 * - добавляет в вывод данные не только STDOUT, но и STDERR
	 * - вывод возвращается не в виде массива, а в виде строки
	 * - может кидать исключение при ошибке - если выполненная команда возвращает значение, отличное от нуля
	 */
	public static function exec (string $cmd, bool $isThrowException = false): array {
		$output = '';
		$code   = 0;

		ob_start();
		exec($cmd.' 2>&1', $output, $code);
		ob_end_clean();

		$r = compact('code', 'output');
		if ($r['code'] and $isThrowException) {
			throw new \Exception(join("\n", $r['output']));
		}

		return $r;
	}
}
