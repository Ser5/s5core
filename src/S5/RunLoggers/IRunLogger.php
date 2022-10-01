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
	 *
	 * @param  string           $message
	 * @param  string|false     $type
	 * @param  string|int|false $level
	 * @return mixed
	 */
	function get ($message, $type = false, $level = false);

	/**
	 * Пишет лог с данными, возвращёнными get().
	 *
	 * @param string           $message
	 * @param string|false     $type
	 * @param string|int|false $level
	 */
	function log ($message, $type = false, $level = false);



	function ok      (string $message, $level = false);
	function error   (string $message, $level = false);
	function warning (string $message, $level = false);
	function info    (string $message, $level = false);



	/**
	 * @param string|false $message
	 * @param string|false $type
	 */
	function group ($message = false, $type = false);

	function groupEnd ();
}
