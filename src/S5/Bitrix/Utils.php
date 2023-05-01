<?
namespace S5\Bitrix;

class Utils {
	/**
	 * Показать страницу 404.
	 *
	 * Якобы можно использовать метод
	 * `\Bitrix\Iblock\Component\Tools::process404('', true, true, true)`
	 *
	 * Где аргументы:
	 * ```
	 * \Bitrix\Iblock\Component\Tools::process404(
	 *    'Не найдено', //Некое сообщение
	 *    true,         //Устанавливать константу ERROR_404
	 *    true,         //Устанавливать статус "404 Not Found"
	 *    true,         //По умолчанию false. Показывать файл /404.php
	 *    ''            //Путь к своему собственному файлу 404
	 * );
	 * ```
	 */
	public static function show404 () {
		@define('ERROR_404', 'Y');
		\CHTTP::setStatus('404 Not Found');
		$GLOBALS['APPLICATION']->RestartWorkarea();
		require(\Bitrix\Main\Application::getDocumentRoot() . '/404.php');
		exit();
	}
}
