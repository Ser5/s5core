<?
namespace S5\Xtrabackup\OS\Commands;

use S5\Xtrabackup\DateTime;



class RestoreCommands {
	public string $cdToRestoreDir;
	public string $deleteRestoreDir;
	public string $createRestoreDir;
	public array  $copyRestoreDirsList;



	public function __construct (string $restoreDirPath, array $dirsList) {
		$this->cdToRestoreDir = "cd \"$restoreDirPath\"";
	}
}
