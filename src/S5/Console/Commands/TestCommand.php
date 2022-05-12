<?
namespace Jaam\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;



class TestCommand extends Command {
	private $_name;
	private $_description;
	protected $logger;
	protected $test;

	protected $testRunParams = [];



	public function __construct ($params) {
		$this->_name        = $params['name'];
		$this->_description = $params['description'];

		$this->logger = $params['logger'];
		$this->test   = $params['test'];

		parent::__construct();
	}



	protected function configure () {
		$this
			->setName($this->_name)
			->setDescription($this->_description)
			->addOption('log',    null, InputOption::VALUE_NONE)
			->addOption('errors', null, InputOption::VALUE_REQUIRED, 'Куда выводить ошибки, если они есть: logger, stdout', '')
		;
	}



	protected function initialize (InputInterface $input, OutputInterface $output) {
		if ($input->getOption('log')) {
			$this->testRunParams['run_logger'] = 'console';
		}
		$this->testRunParams['errors'] = $input->getOption('errors');
	}



	protected function execute (InputInterface $input, OutputInterface $output) {
		try {
			$this->test->run($this->testRunParams);
			return Command::SUCCESS;
		} catch (\Exception $e) {
			//dump($e->getMessage());
			$this->logger->critical(get_class($this) . " не работает:\n" . $e->getMessage());
			return Command::FAILURE;
		}
	}
}
