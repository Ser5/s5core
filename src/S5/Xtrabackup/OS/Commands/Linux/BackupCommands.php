<?
namespace S5\Xtrabackup\OS\Commands\Linux;



class BackupCommands extends \S5\Xtrabackup\OS\Commands\BackupCommands {
	public function __construct (string $backupsDirPath, int $nowTimestamp = 0) {
		parent::__construct($backupsDirPath, $nowTimestamp);

		$this->cdToBackupsDir             = "cd \"$backupsDirPath\"";
		$this->createFullBackupDir        = "mkdir -p \"$backupsDirPath/{$this->datetime->dateString}/\"";
		$this->createIncrementalBackupDir = "mkdir -p \"$backupsDirPath/{$this->datetime->dateHourString}/\"";
	}
}
