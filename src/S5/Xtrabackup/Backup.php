<?
namespace S5\Xtrabackup;

use S5\System;
use S5\IO\{Directory, File};
use S5\RunLoggers\{EmptyRunLogger, RunLoggersFactory, IRunLogger};
use S5\SystemRequirements\{SystemRequirementsResult, SystemRequirementsResultItem};

use S5\Xtrabackup\OS\Commands\{BackupCommands, RestoreCommands};

use S5\Xtrabackup\Results\{Result, BackupResult, RestoreResult};



class Backup {
	public const OS_LINUX   = 'linux';
	public const OS_WINDOWS = 'windows';

	public const APP_XTRABACKUP  = 'xtrabackup';
	public const APP_MARIABACKUP = 'maria-backup';

	protected Directory $rootDirectory;
	protected Directory $backupsDirectory;
	protected Directory $restoreDirectory;

	protected File $lockFile;

	protected bool            $isDumpCommands;
	protected BackupCommands  $backupCommands;
	protected RestoreCommands $restoreCommands;



	/**
	 * Конструктор.
	 *
	 * @param string $rootDirectoryPath        Путь к папке, в которой будут храниться бэкапы и файлы восстановления
	 * @param string $osName                   `Backup::OS_LINUX`, `Backup::OS_WINDOWS`
	 * @param string $osNamespaceString        Свой namespace для команд ОС — если это не Linux или Windows
	 * @param string $appName                  `Backup::APP_XTRABACKUP`, `Backup::APP_MARIABACKUP`
	 * @param int    $fullDaysPeriod           Количество дней между полными бэкапами
	 * @param int    $incrementalHoursPeriod   Количество часов между инкрементальными бэкапами
	 * @param int    $keepDaysCount            Количество дней, после которого устаревшие бэкапы будут удалены
	 * @param $runLoggersFactory,
	 * @param $runLogger
	 * @param string $dbUser                   Пользователь БД
	 * @param string $dbPassword               Пароль БД
	 * @param string $chown                    Группа и пользователь для `chown` файлов в папке `/var/lib/mysql/`. По умолчанию "mysql:mysql".
	 */
	public function __construct (
		string                       $rootDirectoryPath,
		protected string             $osName                 = 'linux',
		protected string             $osNamespaceString      = '',
		protected string             $appName                = 'xtrabackup',
		protected int                $fullDaysPeriod         = 1,
		protected int                $incrementalHoursPeriod = 6,
		protected int                $keepDaysCount          = 14,
		protected ?RunLoggersFactory $runLoggersFactory      = null,
		protected ?IRunLogger        $runLogger              = null,
		protected string             $dbUser                 = '',
		protected string             $dbPassword             = '',
		protected string             $chown                  = 'mysql:mysql',
	) {
		foreach (['fullDaysPeriod', 'incrementalHoursPeriod', 'keepDaysCount'] as $varName) {
			if ($this->$varName and (!ctype_digit("{$this->$varName}") or $this->$varName < 1)) {
				throw new \InvalidArgumentException("Неверный this->$varName: {$this->$varName}");
			}
		}

		if (!$osNamespaceString) {
			$this->osNamespaceString = match ($osName) {
				static::OS_LINUX   => '\S5\Xtrabackup\OS\Commands\Linux',
				static::OS_WINDOWS => '\S5\Xtrabackup\OS\Commands\Windows',
				default            => throw new \InvalidArgumentException("Неизвестное значение \$osName: $osName"),
			};
		}

		$this->rootDirectory    = new Directory($rootDirectoryPath);
		$this->backupsDirectory = new Directory("$rootDirectoryPath/backups/");
		$this->restoreDirectory = new Directory("$rootDirectoryPath/restore/");

		if (!$this->runLoggersFactory) {
			$this->runLoggersFactory = new RunLoggersFactory();
		}
		$this->runLogger = $this->runLoggersFactory->get($runLogger ?? false);
	}



	/**
	 * Создание нового бэкапа — полного или инкрементального.
	 *
	 * @param $isDumpCommands   false — запустить бэкап; true — вывести команды для запуска из консоли вручную
	 */
	public function backup (bool $isDumpCommands = false): BackupResult {
		/** @var BackupResult */
		$result = $this->initLock(new BackupResult());
		if ($result->code == $result::LOCKED) {
			return $result;
		}

		$rl = $this->runLogger;

		try {
			$nowTs = $this->getNowTimestamp();
			$now   = new DateTime($nowTs);

			$rl->info("Запуск бэкапа: $now->datetimeString");

			$this->isDumpCommands = $isDumpCommands;
			$this->backupCommands = $this->getBackupCommands();

			$lastDir              = $this->getLastBackupDirectory();
			$lastFullDir          = $this->getLastFullBackupDirectory();
			$lastDirDatetime      = $lastDir     ? new DateTime($lastDir->getName())                    : null;
			$lastFullDirDatetime  = $lastFullDir ? new DateTime(substr($lastFullDir->getName(), 0, 10)) : null;
			$isLastDirIncremental = $lastDir     ? str_contains($lastDir->getName(), '_')               : false;

			//Когда полагается делать полный и инкрементальный бэкапы
			if (!$lastDir) {
				$nextFullBackupDatetime        = new DateTime($nowTs + 100000);
				$nextIncrementalBackupDatetime = new DateTime($nowTs + 100000);
			} else {
				$nextFullBackupDatetime        = new DateTime($lastFullDirDatetime->timestamp + ($this->fullDaysPeriod * 86400));
				$nextIncrementalBackupDatetime = new DateTime(
					($lastDirDatetime->timestamp ?? $lastDirDatetime->timestamp) +
					($this->incrementalHoursPeriod * 3600)
				);
			}

			if (!$lastDir or $now->timestamp >= $nextFullBackupDatetime->timestamp) {
				if (!$lastDir) {
					$rl->group("Бэкапы отсутствуют");
				} else {
					$rl->group("Создания папки полного бэкапа: {$now->datetimeString} >= {$nextFullBackupDatetime->datetimeString}");
				}
				$workDir = $this->createNewFullDirectory($now);
				$this->runFullBackup($workDir);
				$result->code = $result::FULL;
			}
			elseif ($now->dateHourTimestamp >= $nextIncrementalBackupDatetime->dateHourTimestamp) {
				if (!$isLastDirIncremental) {
					$rl->group("Создания папки первого инкрементального бэкапа");
				} else {
					$rl->group("Создания папки следующего инкрементального бэкапа: {$now->dateHourString} >= {$lastDirDatetime->dateHourString}");
				}
				$workDir = $this->createNewIncrementalDirectory($now);
				$this->runIncrementalBackup($lastDir, $workDir);
				$result->code = $result::INCREMENTAL;
			} else {
				$rl->group("Не настало время для нового бэкапа");
				$result->code = $result::EARLY;
			}

			if (!$isDumpCommands) {
				$this->deleteOldDirectoriesList($now);
			}
		} finally {
			$rl->groupEnd();
			$this->unlock();
		}

		return $result;
	}



	/**
	 * Восстановление за указанную дату/час.
	 *
	 * @param $dateHour         один из вариантов: метка времени; "2000-01-01"; "2000-01-01_12"
	 * @param $isDumpCommands   false — запустить восстановление; true — вывести команды для запуска из консоли вручную
	 */
	public function restore (int|string $dateHour, bool $isDumpCommands = false): RestoreResult {
		/** @var RestoreResult */
		$result = $this->initLock(new RestoreResult());
		if ($result->code == $result::LOCKED) {
			return $result;
		}

		try {
			$rl = $this->runLogger;

			$this->restoreDirectory->delete();
			$dt         = new DateTime($dateHour);
			$restoreDir = new Directory("$this->restoreDirectory/$dt->dateHourString/");

			$rl->info("Папка восстановления: $restoreDir");

			/** @var Directory[] */
			$allDirsList = $this->backupsDirectory->getItemsList(SCANDIR_SORT_DESCENDING);

			/** @var Directory[] */
			$dirsList = [];
			foreach ($allDirsList as $dir) {
				if ($dir->getName() <= $dt->dateHourString) {
					$dirsList[] = $dir;
				}
				if ($this->isFullBackupDirName($dir)) {
					break;
				}
			}

			$dirsList = array_reverse($dirsList);

			if (!count($dirsList)) {
				throw new \InvalidArgumentException("Отсутствуют файлы для восстановления на $dt->dateHourString");
			}
			if (!$this->isFullBackupDirName($dirsList[0])) {
				throw new \InvalidArgumentException("Отсутствует полный бэкап для $dt->dateHourString");
			}

			$this->isDumpCommands  = $isDumpCommands;
			$this->restoreCommands = $this->getRestoreCommands($dirsList);

			$rl->group("Восстановление: {$dt->dateHourString}", 'info', function () use ($rl, $dt, $dirsList, $restoreDir) {
				$rl->group("Копирование файлов: {$dt->dateHourString}", 'info', function () use ($rl, $dirsList, $restoreDir) {
					//Логгирование названий папок.
					//Если нужен запуск, а не вывод команд в консоль, — то папки копируются сразу.
					foreach ($dirsList as $dir) {
						$rl->log($dir->getName());
						if (!$this->isDumpCommands) {
							$dir->copy("$restoreDir/" . $dir->getName());
						}
					}
					//Если нужен только вывод команд в консоль
					if ($this->isDumpCommands) {
						foreach ($this->restoreCommands->copyRestoreDirsList as $commandString) {
							echo "$commandString\n";
						}
					}
				});
				$this->runRestorePreparationsList($restoreDir, $dt, $dirsList);
				$this->runCommand($this->getMysqlStopCommandString());
				$this->runCommand($this->getCopyBackCommandString($restoreDir));
				$this->runCommand($this->getChownCommandString());
				$this->runCommand($this->getMysqlStartCommandString());
			});
		} finally {
			$this->unlock();
		}

		return $result;
	}



	public function checkSystemRequirements (): SystemRequirementsResult {
		$reqs = new SystemRequirementsResult([$this->appName => true]);
		return $reqs;
	}



	protected function getBackupCommands (): BackupCommands {
		$className = "$this->osNamespaceString\\BackupCommands";
		return new $className($this->backupsDirectory, $this->getNowTimestamp());
	}

	protected function getRestoreCommands (array $dirsList): RestoreCommands {
		$className = "$this->osNamespaceString\\RestoreCommands";
		return new $className($this->restoreDirectory, $dirsList);
	}



	protected function getLastBackupDirectory (): ?Directory {
		$d = null;
		if ($this->backupsDirectory->isExists()) {
			$d = $this->backupsDirectory->getItemsList(SCANDIR_SORT_DESCENDING)[0] ?? null;
		}
		return $d;
	}



	protected function getLastFullBackupDirectory (): ?Directory {
		$d = null;
		if ($this->backupsDirectory->isExists()) {
			$dirsList = $this->backupsDirectory->getItemsList(SCANDIR_SORT_DESCENDING);
			if ($dirsList) {
				$d =
					$dirsList->filter(fn($dir) => $this->isFullBackupDirName($dir))[0]
					?? null
				;
			}
		}
		return $d;
	}



	protected function createNewFullDirectory (DateTime $datetime): Directory {
		$this->runCommand($this->backupCommands->createFullBackupDir);
		$d = new Directory("$this->backupsDirectory/$datetime->dateString");
		$this->runLogger->log("Создана папка для полных бэкапов: $d");
		return $d;
	}



	protected function createNewIncrementalDirectory (DateTime $datetime): Directory {
		$this->runCommand($this->backupCommands->createIncrementalBackupDir);
		$d = new Directory("$this->backupsDirectory/$datetime->dateHourString");
		$this->runLogger->log("Создана папка для инкрементальных бэкапов: $d");
		return $d;
	}



	protected function runFullBackup (Directory $dir) {
		$command = $this->getFullBackupCommandString($dir);
		$this->runLogger->info("Создание полного бэкапа: $command");

		$this->runCommand($command);
	}



	protected function runIncrementalBackup (Directory $baseDir, Directory $dir) {
		$command = $this->getIncrementalBackupCommandString($baseDir, $dir);
		$this->runLogger->info("Создание инкрементального бэкапа: $command");

		$this->runCommand($command);
	}



	protected function deleteOldDirectoriesList (DateTime $now): array {
		$deletedDatesHash = [];
		$rl               = $this->runLogger;

		$oldDatetime = new DateTime($now->dateString);
		$oldDatetime->subSeconds($this->keepDaysCount * 86400);

		try {
			$rl->group("Удаление старых бэкапов - старше $oldDatetime->dateString", 'info');

			/** @var Directory[] */
			$dirsList = $this->backupsDirectory->getItemsList();
			foreach ($dirsList as $dir) {
				$dirDateString = substr($dir->getName(), 0, 10);
				if ($dirDateString <= $oldDatetime->dateString) {
					$dir->delete();
					$rl->log($dir->getName());
				}
			}
		} finally {
			$rl->groupEnd();
		}

		return array_keys($deletedDatesHash);
	}



	/**
	 * Подготовка файлов к восстановлению.
	 *
	 * Запуск ряда команд `xtrabackup --prepare --target-dir= ...`.
	 */
	protected function runRestorePreparationsList (Directory $restoreDir, DateTime $datetime, iterable $dirsList) {
		$rl = $this->runLogger;

		$rl->group("Подготовка: {$datetime->dateHourString}", 'info', function () use ($rl, $dirsList, $restoreDir) {
			$dirsCount     = count($dirsList);
			$dirsMax       = count($dirsList) - 1;
			for ($a = 0; $a < $dirsCount; $a++) {
				$dirName = $dirsList[$a]->getName();
				$rl->log($dirName);
				$prevDirIndex       = max(0, $a-1);
				$targetDirName      = $dirsList[$prevDirIndex]->getName();
				$incrementalDirName = (!$a ? '' : $dirName);
				$command            = $this->getPrepareCommandString($restoreDir, $targetDirName, $incrementalDirName, ($a==$dirsMax));
				$this->runCommand($command);
			}
		});
	}



	protected function getFullBackupCommandString (Directory $dir): string {
		$command = "$this->appName --backup --target-dir=\"$dir\" --parallel=4 --compress";
		return $command;
	}

	protected function getIncrementalBackupCommandString (Directory $baseDir, Directory $dir): string {
		$command = "$this->appName --backup --incremental-basedir=\"$baseDir\" --target-dir=\"$dir\" --parallel=4 --compress";
		return $command;
	}



	protected function getPrepareCommandString (Directory $restoreDir, string $targetDirName, string $incrementalDirName, bool $isFinal): string {
		$command = "$this->appName --prepare --target-dir=\"{$restoreDir}$targetDirName/\"";
		if ($incrementalDirName) {
			$command .= " --incremental-dir=\"{$restoreDir}$incrementalDirName/\"";
		}
		if (!$isFinal) {
			$command .= " --apply-log-only";
		}
		return $command;
	}

	protected function getCopyBackCommandString   (Directory $restoreDir): string { return "$this->appName --copy-back --target-dir=\"$restoreDir\""; }
	protected function getMysqlStopCommandString  (): string { return "service mysql stop"; }
	protected function getMysqlStartCommandString (): string { return "service mysql start"; }
	protected function getChownCommandString      (): string { return "chown -R $this->chown /var/lib/mysql/"; }



	protected function runCommand (string $command) {
		if ($this->isDumpCommands) {
			echo "$command\n";
			return;
		}

		if (is_a($this->runLogger, EmptyRunLogger::class)) {
			System::exec($command, true);
		} else {
			System::passthru($command, true);
		}
	}



	protected function isFullBackupDirName (Directory $dir) {
		return preg_match('/^\d\d\d\d-\d\d-\d\d$/', $dir->getName());
	}



	protected function lock (): bool {
		$this->lockFile = new File("$this->rootDirectory/lock");
		return $this->lockFile->lock();
	}

	protected function unlock () {
		$this->lockFile->unlock();
	}

	protected function initLock (Result $result): Result {
		if (!$this->lock()) {
			$this->runLogger->warning("Файл блокировки недоступен: бэкап уже идёт");
			$result->code = $result::LOCKED;
		}
		return $result;
	}



	protected function getNowTimestamp (): int {
		return time();
	}
}
