<?
namespace S5\Web;

/**
 * Упрощённая работа с куками.
 *
 * Решаемые проблемы:
 * - setcookie() требует специально указывать, что куки работают от корня сайта, несмотря на то, что это самый часто используемый вариант.
 *   Этот класс ставит куки на корень сайта по умолчанию.
 * - setcookie() не меняет $_COOKIE, только что поставленные куки из $_COOKIE не получить.
 *   Этот класс сразу помещает значение куки в $_COOKIE.
 * - неинтуитивный синтаксис удаления кук. И при удалении кук $_COOKIE опять же не затрагивается.
 *   Этот класс имеет специальный метод удаления - delete() - и из $_COOKIE значение тоже сразу удаляет.
 */
class CookiesManager {
	public static function set (string $name, string $value, int $time = 0, string $path = '/') {
		setcookie($name, $value, $time, $path);
		$_COOKIE[$name] = $value;
	}



	public static function delete (string $name) {
		setcookie($name, '', time()-86400);
		unset($_COOKIE[$name]);
	}
}
