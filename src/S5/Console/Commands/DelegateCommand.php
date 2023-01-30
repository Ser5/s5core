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
 *
 * Фактически, обязательных опций нет - все настройки касаются уже указанных опций.
 *
 * `'is_required' => true` - это если всё-таки опцию надо указать обязательно.
 *
 * `transformer` - если в командной строке опция должна выглядеть как-то более кратко и понятно,
 * а в метод соответствующий параметр нужно передать в трансформированном виде.
 * Например, в коде выше на входе будет
 * `--log=console`
 * а на выходе
 * `['code' => 'run_logger', 'value' => объект]`
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
		foreach ($this->options as $e) {
			$isDefaultFalse = true;
			$optionData     = [$e['code'], null, null];
			if (isset($e['flags'])) {
				if (!is_array($e['flags'])) {
					$e['flags'] = [$e['flags']];
				}
				foreach ($e['flags'] as $f) {
					$constName      = 'VALUE_'.strtoupper($f);
					$optionData[2] |= constant('\Symfony\Component\Console\Input\InputOption::' . $constName);
					if ($f == 'none' or $f == 'is_array') {
						$isDefaultFalse = false;
					}
				}
			}
			if (isset($e['label'])) {
				$optionData[] = $e['label'];
			}
			if (isset($e['default'])) {
				$optionData[] = $e['default'];
			} elseif ($isDefaultFalse) {
				$optionData[] = false;
			}
			$this->addOption(...$optionData);
		}
	}



	protected function execute (InputInterface $input, OutputInterface $output): int {
		try {
			//По опциям назначаем запускаемому методу параметры
			$params = [];
			foreach ($this->options as $e) {
				$code      = $e['code'];
				$paramName = str_replace('-', '_', $code);
				$value     = $input->getOption($code);
				if (@$e['is_required'] and !$value) {
					throw new \InvalidArgumentException("[$code] - обязательный параметр не указан");
				}
				if ($value) {
					if (!isset($e['transformer'])) {
						$params[$paramName] = $value;
					} else {
						$r = call_user_func($e['transformer'], $value);
						$params[$r['code']] = $r['value'];
					}
				}
			}
			call_user_func($this->callback, $params);
			return Command::SUCCESS;
		} catch (\Exception $e) {
			echo $e->getMessage(),"\n";
			//$this->logger->critical(get_class($this) . " не работает:\n" . $e->getMessage());
			return Command::FAILURE;
		}
	}
}
