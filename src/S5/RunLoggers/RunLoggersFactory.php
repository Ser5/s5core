<?
namespace S5\RunLoggers;

class RunLoggersFactory {
	protected array $loggers;

	public function __construct (array $params) {
		foreach ($params as $k => $v) {
			$this->{$k} = $v;
		}
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
			if (!isset($this->loggers[$logger])) {
				throw new \InvalidArgumentException("Логгер не найден: [$logger]");
			}
			$loggerClassName = $this->loggers[$logger];
			if (is_string($loggerClassName)) {
				return new $loggerClassName();
			} else {
				return $loggerClassName();
			}
		}
	}
}
