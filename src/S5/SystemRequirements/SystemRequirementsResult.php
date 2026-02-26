<?
namespace S5\SystemRequirements;

use S5\System;



class SystemRequirementsResult {
	public bool $isOK = true;

	/** @var SystemRequirementsResultItem[] */
	public array $itemsHash = [];


	public function __construct (array $itemKeysHash) {
		foreach ($itemKeysHash as $key => $isCheck) {
			$this->itemsHash[$key] = new SystemRequirementsResultItem();
			if ($isCheck) {
				$r = System::exec("which $key");
				if (!$r[0]) {
					$this->itemsHash[$key]->setInvalid("$key не установлен");
				}
			}
		}
	}



	public function setInvalid (string $itemName, string $errorMessage = '') {
		$this->isOK = false;
		$this->itemsHash[$itemName]->setInvalid($errorMessage);
	}
}
