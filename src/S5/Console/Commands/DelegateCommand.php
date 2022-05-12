<?
namespace S5\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;



class DelegateCommand extends \Symfony\Component\Console\Command\Command {
	protected string $name;
	protected string $description;
	protected $callback;
	protected array $options = [];
	protected \S5\RunLogger\BaseRunLogger $logger;

	protected $methodParams = [];

	public function __construct ($params) {
		foreach ($params as $k => $v) {
			$this->{'_'.snakeToCamel($k)} = $v;
		}
		parent::__construct();
	}



	protected function configure () {
		$this
			->setName($this->name)
			->setDescription($this->description)
		;
		//$optionData
		//code    -> 0 - код аргумента командной строки
		//           1 - null
		//flags   -> 2 - VALUE_REQUIRED, VALUE_OPTIONAL итд - может быть несколько
		//label   -> 3 - человекочитаемое название аргумента
		//default -> 4 - значение по умолчанию
		foreach ($this->options as $e) {
			if ($e['code'] == 'log') {
				$optionData = ['log'];
			} else {
				$optionData = [$e['code'], null, null];
				if (isset($e['flags'])) {
					if (!is_array($e['flags'])) {
						$e['flags'] = [$e['flags']];
					}
					foreach ($e['flags'] as $f) {
						$constName      = 'VALUE_'.strtoupper($f);
						$optionData[2] |= constant('\Symfony\Component\Console\Input\InputOption::' . $constName);
					}
				}
				if (isset($e['label'])) {
					$optionData[] = $e['label'];
				}
				if (isset($e['default'])) {
					$optionData[] = $e['default'];
				}
			}
			$this->addOption(...$optionData);
		}
	}



	protected function execute (InputInterface $input, OutputInterface $output) {
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
					if ($code == 'log') {
						$params['run_logger'] = 'console';
					} else {
						$params[$paramName] = $value;
					}
				}
			}
			call_user_func($this->callback, $params);
			return Command::SUCCESS;
		} catch (\Exception $e) {
			echo $e->getMessage(),"\n";
			$this->logger->critical(get_class($this) . " не работает:\n" . $e->getMessage());
			return Command::FAILURE;
		}
	}
}
