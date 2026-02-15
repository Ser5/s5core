<?
namespace S5\RunLoggers;

class RunLoggersFactory {
	protected array $loggersHash;

	public function __construct (array $params = []) {
		foreach ($params as $k => $v) {
			$this->{$k} = $v;
		}

		if (@!$params['loggersHash']) {
			$this->initDefaultLoggersHash();
		}
	}



	protected function initDefaultLoggersHash () {
		$this->loggersHash = [
				''        => \S5\RunLoggers\EmptyRunLogger::class,
				'1'       => \S5\RunLoggers\ConsoleRunLogger::class,
				'console' => \S5\RunLoggers\ConsoleRunLogger::class,
				'json'    => \S5\RunLoggers\JsonRunLogger::class,
		];
	}



	/**
	 * Возвращает объект логгера.
	 *
	 * @param  string|int|bool|IRunLogger $logger
	 * @return IRunLogger
	 */
	public function get ($logger) {
		if ($logger instanceof IRunLogger) {
			return $logger;
		}
		else {
			@$logger = (string)$logger;
			if (!isset($this->loggersHash[$logger])) {
				throw new \InvalidArgumentException("Логгер не найден: [$logger]");
			}
			$loggerClassName = $this->loggersHash[$logger];
			if (is_string($loggerClassName)) {
				return new $loggerClassName();
			} else {
				return $loggerClassName();
			}
		}
	}
}
