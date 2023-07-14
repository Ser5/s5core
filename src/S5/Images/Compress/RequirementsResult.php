<?
namespace S5\Images\Compress;

class RequirementsResult {
	public bool  $isOK      = false;
	public array $itemsHash = [];

	public function __construct () {
		$this->itemsHash = [
			'node'     => new RequirementsItemResult(),
			'node16'   => new RequirementsItemResult(),
			'exiftool' => new RequirementsItemResult(),
			'squoosh'  => new RequirementsItemResult(),
		];
	}
}
