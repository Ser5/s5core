<?
namespace S5\Xtrabackup\OS\Commands\Windows;



class BackupCommands extends \S5\Xtrabackup\OS\Commands\BackupCommands {
	public function __construct (string $backupsDirPath, int $nowTimestamp = 0) {
		parent::__construct($backupsDirPath, $nowTimestamp);

		$this->cdToBackupsDir             = "cd \"$backupsDirPath\"";
		$this->createFullBackupDir        = "mkdir \"$backupsDirPath/{$this->datetime->dateString}/\"";
		$this->createIncrementalBackupDir = "mkdir \"$backupsDirPath/{$this->datetime->dateHourString}/\"";
	}
}
