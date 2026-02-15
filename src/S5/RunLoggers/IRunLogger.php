<?
namespace S5\RunLoggers;

interface IRunLogger {
	/**
	 * Возвращает данные для записи в лог.
	 *
	 * ```
	 * $runLogger->log('Здарофки', 2, 'ok');
	 * $runLogger->log('Здарофки', 2, $runLogger::OK);
	 * ```
	 *
	 * $level это уровень вложенности сообщения.
	 * Начинаются уровни с 1.
	 *
	 * Уровень можно не указывать, тогда будет использован текущий,
	 * установленный методами group() и groupEnd().
	 * ```
	 * $runLogger->log('Сообщенька');
	 * $runLogger->log('Сообщенька об ошибке', false, 'error');
	 * $runLogger->group();
	 * $runLogger->log('Сообщенька уровнем глубже');
	 * $runLogger->groupEnd();
	 * ```
	 *
	 * Можно указать уровень явно:
	 * ```
	 * $runLogger->log('Сообщенька', 2);
	 * ```
	 *
	 * Можно указать уровень относительно:
	 * ```
	 * $runLogger->log('Сообщенька уровнем глубже',   '+1');
	 * $runLogger->log('Сообщенька вровень выпирает', '-1');
	 * ```
	 */
	function get (string $message, string|false $type = false, string|int|false $level = false): mixed;

	/**
	 * Пишет лог с данными, возвращёнными get().
	 */
	function log (string $message, string|false $type = false, string|int|false $level = false);



	function ok      (string $message, string|int|false $level = false);
	function error   (string $message, string|int|false $level = false);
	function warning (string $message, string|int|false $level = false);
	function info    (string $message, string|int|false $level = false);



	function group (string|false $message = false, string|false $type = false, \Closure|false $callback = false);

	function groupEnd ();
}
