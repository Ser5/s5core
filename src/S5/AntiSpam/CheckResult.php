<?
namespace S5\Web\AntiSpam;

class CheckResult {
	protected bool   $isOk         = true;
	protected string $errorMessage = '';

	/**
	 * Ctor.
	 *
	 * @param string|false $errorMessage
	 */
	public function __construct ($errorMessage = false) {
		if ($errorMessage !== false) {
			$this->isOk         = false;
			$this->errorMessage = $errorMessage;
		}
	}



	public function isOk (): bool {
		return $this->isOk;
	}

	public function getErrorMessage (): string {
		return $this->errorMessage;
	}
}
