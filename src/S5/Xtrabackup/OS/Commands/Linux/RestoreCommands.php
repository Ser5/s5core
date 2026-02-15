<?
namespace S5\Xtrabackup\OS\Commands\Linux;



class RestoreCommands extends \S5\Xtrabackup\OS\Commands\RestoreCommands {
	public function __construct (string $restoreDirPath, array $dirsList) {
		parent::__construct ($restoreDirPath, $dirsList);

		$this->deleteRestoreDir = "rm -rf \"$restoreDirPath\"";
		$this->createRestoreDir = "mkdir -p \"$restoreDirPath\"";

		$lastDir = $dirsList[count($dirsList) - 1];
		foreach ($dirsList as $dir) {
			$this->copyRestoreDirsList[] = "cp -r \"$dir\" \"$restoreDirPath\{$lastDir->getName()}/\"";
		}
	}
}
