<?
namespace S5\Images\Compress;

class RequirementsItemResult {
	public bool   $isOK         = true;
	public string $errorMessage = '';

	public function setValid () {
		$this->isOK = true;
	}

	public function setInvalid (string $errorMessage = '') {
		$this->isOK         = false;
		$this->errorMessage = $errorMessage;
	}
}
