<?
namespace S5\RunLogger;

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
	 * @param  string|int|bool|BaseRunLogger $logger
	 * @return BaseRunLogger
	 */
	public function get ($logger) {
		if ($logger instanceof BaseRunLogger) {
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
