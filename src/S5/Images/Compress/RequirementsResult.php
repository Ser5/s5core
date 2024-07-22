<?
namespace S5\Images\Compress;

class RequirementsResult {
	public bool  $isOK = true;

	/** @var RequirementsItemResult[] */
	public array $itemsHash = [];


	public function __construct () {
		$this->itemsHash = [
			'node'        => new RequirementsItemResult(),
			'node16'      => new RequirementsItemResult(),
			'exiftool'    => new RequirementsItemResult(),
			'squoosh-cli' => new RequirementsItemResult(),
			'cwebp'       => new RequirementsItemResult(),
			'avif'        => new RequirementsItemResult(),
		];
	}

	public function setInvalid (string $itemName, string $errorMessage = '') {
		$this->isOK = false;
		$this->itemsHash[$itemName]->setInvalid($errorMessage);
	}
}
