<?
namespace S5\Cli;

/**
 * Простой класс для получения значений длинных аргументов командной строки.
 *
 * Длинные аргументы это типа: --session, --help и т. д.
 *
 * Аргумент может иметь значение, как --session=12345, так и не иметь, как --help.
 * Для аргументов без значения будет возвращено просто true.
 *
 * Для несуществующих аргументов возвращается false.
 *
 * Пример:<br>
 * Скрипт запускается так:
 * <code>php script.php --foo=1 --bar=2 --format</code>
 *
 * Код для получения аргументов:
 * <code>
 * $options = new S5_Cli_LongOptions();
 * $foo    = $options->get('foo');    //1, аргумент со значением
 * $bar    = $options->get('bar');    //2, аргумент со значением
 * $baz    = $options->get('baz');    //false, несуществующий аргумент
 * $format = $options->get('format'); //true, аргумент без значения
 *
 * $baz = $options->get('baz', 3); //3, явно указанное умолчальное значение для несуществующего аргумента
 * </code>
 */
class LongOptions {
	private $_optionsHash;

	public function __construct () {
		global $argc, $argv;
		$matches = array();
		for ($a = 1; $a < $argc; $a++) {
			if (preg_match('/^--([^=]+)=(.+)$/', $argv[$a], $matches)) {
				$this->_optionsHash[$matches[1]] = $matches[2];
			} elseif (preg_match('/^--([^=]+)$/', $argv[$a], $matches)) {
				$this->_optionsHash[$matches[1]] = true;
			}
		}
	}



	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed|false
	 */
	public function get ($name, $defaultValue = false) {
		if (isset($this->_optionsHash[$name])) {
			return $this->_optionsHash[$name];
		} else {
			return $defaultValue;
		}
	}
}
