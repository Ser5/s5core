<?
namespace S5\Traits;
use S5\RunLogger\IRunLogger;


trait RunLoggableTrait {
	private $_runLoggersFactory;

	/**
	 * @param  array|IRunLogger|mixed $p
	 * @return IRunLogger
	 */
	private function _getRunLogger ($p) {
		$rl = false;
		if (is_array($p)) {
			$rl = $p['run_logger'] ?? $p['runLogger'] ?? false;
		} else {
			$rl = $p;
		}
		return $this->_runLoggersFactory->get($rl);
	}
}
