<?
namespace S5\Console;

class Application {
	protected string $name;
	protected string $version;
	protected array  $commandsDataList;

	public function __construct (string $name, string $version, array $commandsDataList) {
		$this->name             = $name;
		$this->version          = $version;
		$this->commandsDataList = $commandsDataList;
	}



	public function run () {
		$app = new \Symfony\Component\Console\Application($this->name ?: 'App', $this->version ?: '1.0.0');

		$matches = [];
		foreach ($this->commandsDataList as $commandData) {
			$app->add(new \S5\Console\Commands\DelegateCommand($commandData));
		}

		$app->run();
	}
}
