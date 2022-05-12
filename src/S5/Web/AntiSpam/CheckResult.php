<?
namespace S5\Web\AntiSpam;

class CheckResult {
	protected bool   $isOK         = true;
	protected string $errorMessage = '';

	public function __construct (string $errorMessage = '') {
		if ($errorMessage) {
			$this->isOK         = false;
			$this->errorMessage = $errorMessage;
		}
	}



	public function isOK (): bool {
		return $this->isOK;
	}

	public function getErrorMessage (): string {
		return $this->errorMessage;
	}
}
