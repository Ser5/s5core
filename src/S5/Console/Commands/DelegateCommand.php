<?
namespace S5\Console\Commands;

use Symfony\Component\Console\{
	Command\Command,
	Input\InputInterface,
	Output\OutputInterface,
	Input\InputOption,
};



/**
 * Запуск методов как консольных команд Symfony.
 *
 * ```
 * $c = new DelegateCommand([
 *    'name'        => 'Удалить всё',
 *    'description' => 'Удалить все разделы, товары, всех пользователей, весь сайт, отформатировать сервер',
 *    'callback'    => ['SiteManagerClass', 'delete'],
 *    'options' => [
 *       ['label' => 'Номер сервера',              'code' => 'server',        'flags' => 'required', 'is_required' => true],
 *       ['label' => 'Отформатировать сервер',     'code' => 'format-server', 'flags' => 'optional'],
 *       ['label' => 'Бэкап после форматирования', 'code' => 'backup',        'flags' => 'optional', 'default' => true],
 *       [
 *          'label'       => 'Логи на экран, на емейл',
 *          'code'        => 'log',
 *          'flags'       => ['is_array','required'],
 *          'default'     => [],
 *          'transformer' => function ($value) {
 *             $r = ['code' => 'run_logger'];
 *             if (!$value) {
 *                $r['value'] = false;
 *             } else {
 *                $r['value'] = new GroupLogger($value);
 *             }
 *             return $r;
 *          },
 *       ],
 *    ],
 * ]);
 * ```
 *
 * flags - параметры опций с точки зрения Symfony.
 *
 * Возможные варианты:
 * - is_array  - может быть указан несколько раз с разными значениями
 * - none      - значений не принимает, возвращает значение true или false
 * - required  - если опция указана, то она должна иметь значение
 * - optional  - значение может быть не указано или указано
 * - negatable - может быть указан или сама опция, или её противоположность - например, --log или --no-log
 * - bool - самодельный вариант, несовместим с другими флагами:
 *   - значение не указано и default не установлен - значение не будет передано в функцию;
 *   - значение не указано и default установлен - значение будет равно default;
 *   - значение указано как 1 - получится true;
 *   - значение указано как 0 - получится false;
 *
 * none используется по умолчанию.
 *
 * Фактически, обязательных опций нет - все настройки касаются уже указанных опций.
 *
 * `['code' => 'Опция', 'is_required' => true]` - это если всё-таки опцию надо указать обязательно.
 *
 * `transformer` - если в командной строке опция должна выглядеть как-то более кратко и понятно,
 * а в метод соответствующий параметр нужно передать в трансформированном виде.
 * Например, в коде выше на входе будет
 * `--log=console`
 * а на выходе
 * `['code' => 'run_logger', 'value' => объект]`
 *
 * Как реализовывать разные варианты:
 * - Опция со значением:
 *   `--option=value`
 *   `['label' => 'Опция', 'code' => 'option', 'flags' => 'required']`
 * - Опция с несколькими значениями
 *   `--option=value1 --option=value2 --option=value3`
 *   `['label' => 'Опция', 'code' => 'option', 'flags' => ['required','is_array']]`
 * - Опция-флаг: если не указана - false, если указана - true
 *   `--log`
 *   `['label' => 'Опция', 'code' => 'option', 'flags' => ['none']]`
 * - Опция-флаг: не указана - умолчальное значение, указана без значения - true, 0 - false, 1 - true
 *   `--log`
 *   `--log=0`
 *   `--log=1`
 *   `['label' => 'Опция', 'code' => 'option', 'flags' => 'bool']`
 */
class DelegateCommand extends Command {
	protected string $name;
	protected string $description;
	protected        $callback;
	protected array  $options = [];


	public function __construct (array $params) {
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}
		parent::__construct();
	}



	protected function configure () {
		$this
			->setName($this->name)
			->setDescription($this->description)
		;
		//$optionData:
		//code    -> 0 - код аргумента командной строки
		//           1 - null
		//flags   -> 2 - VALUE_REQUIRED, VALUE_OPTIONAL итд - может быть несколько
		//label   -> 3 - человекочитаемое название аргумента
		//default -> 4 - значение по умолчанию
		foreach ($this->options as &$e) {
			$optionData     = [$e['code'], null, null];
			if (isset($e['flags'])) {
				if (!is_array($e['flags'])) {
					$e['flags'] = [$e['flags']];
				}
				$e = $this->configureBoolOptionData($e);
				foreach ($e['flags'] as $f) {
					$constName      = 'VALUE_'.strtoupper($f);
					$optionData[2] |= constant('\Symfony\Component\Console\Input\InputOption::' . $constName);
				}
			}
			if (isset($e['label'])) {
				$optionData[3] = $e['label'];
			}
			if (in_array('default', $e)) {
				$optionData[4] = $e['default'];
			}
			$this->addOption(...$optionData);
		}
		unset($e);
	}



	protected function execute (InputInterface $input, OutputInterface $output): int {
		try {
			//По опциям назначаем запускаемому методу параметры
			$params = [];
			foreach ($this->options as $e) {
				$code  = $e['code'];
				$value = $input->getOption($code);
				if (@$e['is_required'] and !$value) {
					throw new \InvalidArgumentException("[$code] - обязательный параметр не указан");
				}
				$params = $this->addTargetFunctionParam($params, $e, $value);
			}
			call_user_func($this->callback, $params);
			return Command::SUCCESS;
		} catch (\Exception $e) {
			echo $e->getMessage(),"\n";
			return Command::FAILURE;
		}
	}



	protected function configureBoolOptionData (array $optionData): array {
		$optionData['_is_bool'] = in_array('bool', $optionData['flags']);

		if ($optionData['_is_bool']) {
			if (count($optionData['flags']) > 1) {
				throw new \InvalidArgumentException("bool не может быть указан совместно с другими флагами");
			}
			array_splice(
				$optionData['flags'],
				array_search('bool', $optionData['flags']),
				1
			);
			$optionData['flags'] = ['optional'];
			if (isset($optionData['default'])) {
				$optionData['default'] = (int)$optionData['default'];
			} else {
				$optionData['default'] = false;
			}
		}

		return $optionData;
	}



	protected function addTargetFunctionParam (array $params, array $optionData, $value): array {
		$paramName = str_replace('-', '_', $optionData['code']);

		if ($value or $optionData['_is_bool']) {
			//Если это bool, то получим его value по особым правилам
			if ($optionData['_is_bool']) {
				$boolData = $this->getBoolData($optionData, $value);
				if (!$boolData['is_add_param']) {
					return $params; // <--- NB!
				} else {
					$value = $boolData['value'];
				}
			}
			if (!isset($optionData['transformer'])) {
				$params[$paramName] = $value;
			} else {
				$r = call_user_func($optionData['transformer'], $value);
				$params[$r['code']] = $r['value'];
			}
		}

		return $params;
	}



	/**
	 * Что попадёт в метод-делегат из опции типа bool.
	 *
	 * ```
	 * Вызов                      default   $symfonyValue   Что попадёт в параметры метода-делегата
	 * --------------------------------------------------------------------------------------------
	 * ./console run              -         false           Ничего
	 * ./console run              false     0               false
	 * ./console run              true      1               true
	 * ./console run --option     Любое     null            true
	 * ./console run --option=0   Любое     0               false
	 * ./console run --option=1   Любое     1               true
	 * ```
	 *
	 * Как Symfony различает ситуацию когда опция не передана и передана опция без значения.
	 *
	 * Если в настройках опции не указать default, то в любом случае будет null.
	 * Поэтому default надо указать = false.
	 * Тогда, если опция не передана, получится значение false,
	 * а если передана - значение null.
	 *
	 * @param  array $optionData   Наши настройки опции
	 * @param  mixed $symfonyValue Что пришло из симфоневского getOption() - false, null, 0, 1
	 * @return array
	 */
	protected function getBoolData (array $optionData, $symfonyValue): array {
		$r = ['is_add_param' => true, 'value' => null];

		if ($symfonyValue === false) {
			$r['is_add_param'] = false;
		} elseif (is_null($symfonyValue)) {
			$r['value'] = true;
		} else {
			$r['value'] = (bool)$symfonyValue;
		}

		return $r;
	}
}
