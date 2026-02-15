<?
namespace S5\Xtrabackup\OS\Commands;

use S5\Xtrabackup\DateTime;



class BackupCommands {
	protected DateTime $datetime;

	public string $cdToBackupsDir;
	public string $createFullBackupDir;
	public string $createIncrementalBackupDir;



	public function __construct (string $backupsDirPath, int $nowTimestamp = 0) {
		$this->datetime = new DateTime($nowTimestamp ?: time());
	}
}
