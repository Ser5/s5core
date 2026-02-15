<?
namespace S5\Xtrabackup\OS\Commands\Windows;



class RestoreCommands extends \S5\Xtrabackup\OS\Commands\RestoreCommands {
	public function __construct (string $restoreDirPath, array $dirsList) {
		parent::__construct ($restoreDirPath, $dirsList);

		$this->deleteRestoreDir    = "rmdir /s /q \"$restoreDirPath\"";
		$this->createRestoreDir    = "mkdir -f \"$restoreDirPath\"";

		$lastDirName = $dirsList[count($dirsList) - 1]->getName();
		foreach ($dirsList as $dir) {
			$this->copyRestoreDirsList[] = "xcopy \"$dir\" \"{$restoreDirPath}$lastDirName/\" /e /i /y";
		}
	}
}
