<?
namespace S5\Messages\Ru;



class RuMessages extends \S5\Messages\Messages {
	/**
	 * Для сообщений типа "от 1 символа", "от 2 символов", "от 5 символов" итп.
	 */
	protected function symbolsPlurals (string $varName) {
		return '{' .$varName. ',plural, one{символа} other{символов}}';
	}
}
