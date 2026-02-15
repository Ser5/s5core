<?
namespace S5\Xtrabackup;

use S5\IO\{Directory, File};

use S5\Xtrabackup\Results\{BackupResult, RestoreResult};



class BackupTest extends \S5\TestCase {
	protected Directory $rootDirectory;
	protected Directory $backupsDirectory;
	protected Directory $restoreDirectory;



	public function testBackup () {
		$b               = $this->_makeBackup();
		$b->nowTimestamp = strtotime('2000-01-01 00:00:00');

		//Новый полный бэкап
		$this->_assertFullBackup($b, '2000-01-01');

		//Слишком рано для нового бэкапа
		$this->_assertEarlyBackup($b);

		//Тоже рано для нового бэкапа
		$this->_assertEarlyBackup($b, '2000-01-01 05:50:00');

		//Новый инкрементальный бэкап 06:00
		$this->_assertIncrementalBackup($b, '2000-01-01', '2000-01-01 06:10:00');

		//Рано
		$this->_assertEarlyBackup($b, '2000-01-01 09:00:00');

		//Новый инкрементальный бэкап 23:00
		$this->_assertIncrementalBackup($b, '2000-01-01_06', '2000-01-01 23:30:00');


		//Новые бэкапы 2000-01-02
		$this->_assertFullBackup       ($b, '2000-01-02');
		$this->_assertEarlyBackup      ($b, '2000-01-02 01:00:00');
		$this->_assertIncrementalBackup($b, '2000-01-02', '2000-01-02 12:00:00');


		//Новые бэкапы 2000-01-10
		$this->_assertFullBackup       ($b, '2000-01-10');
		$this->_assertEarlyBackup      ($b, '2000-01-10 01:00:00');
		$this->_assertIncrementalBackup($b, '2000-01-10', '2000-01-10 12:00:00');


		//Удаление старых бэкапов
		$this->_assertFullBackup($b, '2000-01-24');
		$this->_assertIncrementalBackup($b, '2000-01-24', '2000-01-24 12:00:00');
		$backupDirsList = (new Directory("{$this->backupsDirectory}"))->getItemsList();
		$this->assertCount(2, $backupDirsList);
		$this->assertEquals('2000-01-24',    $backupDirsList[0]->getName());
		$this->assertEquals('2000-01-24_12', $backupDirsList[1]->getName());


		//Lock
		$lockFile = new File("{$this->rootDirectory}lock");
		$lockFile->lock();
		$r = $b->backup();
		$this->assertEquals($r::LOCKED, $r->code);
		$lockFile->unlock();
	}



	public function testBackupWithCustomFullDaysPeriod () {
		$b               = $this->_makeBackup(['fullDaysPeriod' => 3]);
		$b->nowTimestamp = strtotime('2000-01-15');
		//15
		$this->_assertFullBackup       ($b, '2000-01-15');
		$this->_assertIncrementalBackup($b, '2000-01-15',    '2000-01-15 12:00:00');
		//16
		$this->_assertIncrementalBackup($b, '2000-01-15_12', '2000-01-16 00:00:00');
		$this->_assertIncrementalBackup($b, '2000-01-16_00', '2000-01-16 12:00:00');
		//17
		$this->_assertIncrementalBackup($b, '2000-01-16_12', '2000-01-17 00:00:00');
		//Новый полный бэкап
		$this->_assertFullBackup       ($b, '2000-01-18');
		$this->_assertIncrementalBackup($b, '2000-01-18',    '2000-01-18 12:00:00');
	}



	public function testBackupDumpCommands () {
		$b               = $this->_makeBackup();
		$b->nowTimestamp = strtotime('2000-01-01 00:00:00');

		ob_start();

		$b->backup(true);
		$this->_createBackupDir('2000-01-01/');

		$b->nowTimestamp = (new DateTime('2000-01-01 06:10:00'))->timestamp;
		$b->backup(true);
		$this->_createBackupDir('2000-01-01_06/');

		$b->nowTimestamp = (new DateTime('2000-01-01 23:30:00'))->timestamp;
		$b->backup(true);
		$this->_createBackupDir('2000-01-01_23/');

		$outputStringsList = $this->_getCommandOutputStringsList(ob_get_clean());

		$this->assertCount(6, $outputStringsList);

		//Full
		$this->assertMatchesRegularExpression(
			"~^mkdir[^\"]\"{$this->backupsDirectory}2000-01-01/\"~ui",
			$outputStringsList[0]
		);
		$this->_assertFullBackupCommandString('2000-01-01', $outputStringsList[1]);
		//Incremental
		$this->assertMatchesRegularExpression(
			"~^mkdir[^\"]\"{$this->backupsDirectory}2000-01-01_06/\"~ui",
			$outputStringsList[2]
		);
		$this->_assertIncrementalBackupCommandString('2000-01-01', '2000-01-01_06', $outputStringsList[3]);
		//Incremental
		$this->assertMatchesRegularExpression(
			"~^mkdir[^\"]\"{$this->backupsDirectory}2000-01-01_23/\"~ui",
			$outputStringsList[4]
		);
		$this->_assertIncrementalBackupCommandString('2000-01-01_06', '2000-01-01_23', $outputStringsList[5]);
	}



	public function testRestore () {
		$b = $this->_backupForRestore();

		$b->restore('2000-01-03_15');

		$restoreDirPath      = "{$this->restoreDirectory}2000-01-03_15/";
		$restoreDirNamesList = $this->_getRestoreDirNamesList();
		foreach ($restoreDirNamesList as $dirName) {
			$this->assertDirectoryExists("{$restoreDirPath}$dirName/");
		}

		$this->assertCount(11, $b->restoreCommandStringsList);

		//Подготовка
		$commandString = "xtrabackup --prepare";
		//Полный
		$this->assertEquals(
			"$commandString --target-dir=\"{$restoreDirPath}2000-01-01/\" --apply-log-only",
			$b->restoreCommandStringsList[0]
		);
		//Инкрементальные
		for ($a = 1; $a < count($restoreDirNamesList); $a++) {
			$targetDirName        = $restoreDirNamesList[$a - 1];
			$incrementalDirName = $restoreDirNamesList[$a];
			if ($incrementalDirName != '2000-01-03_15') {
				//Промежуточные инкрементальные бэкапы
				$this->assertEquals(
					"$commandString --target-dir=\"{$restoreDirPath}$targetDirName/\" --incremental-dir=\"{$restoreDirPath}$incrementalDirName/\" --apply-log-only",
					$b->restoreCommandStringsList[$a]
				);
			} else {
				//Последний инкрементальный бэкап
				$this->assertEquals(
					"$commandString --target-dir=\"{$restoreDirPath}$targetDirName/\" --incremental-dir=\"{$restoreDirPath}$incrementalDirName/\"",
					$b->restoreCommandStringsList[$a]
				);
			}
		}
		//Запуск
		$commandIndex = 7;
		$this->assertEquals(
			"service mysql stop",
			$b->restoreCommandStringsList[$commandIndex++]
		);
		$this->assertEquals(
			"xtrabackup --copy-back --target-dir=\"$restoreDirPath\"",
			$b->restoreCommandStringsList[$commandIndex++]
		);
		$this->assertEquals(
			"chown -R mysql:mysql /var/lib/mysql/",
			$b->restoreCommandStringsList[$commandIndex++]
		);
		$this->assertEquals(
			"service mysql start",
			$b->restoreCommandStringsList[$commandIndex++]
		);

		//Восстановление за несуществующие даты
		$b = $this->_backupForRestore();
		$this->assertException(fn() => $b->restore('1999-12-30'));
		$b = $this->_backupForRestore();
		$this->assertException(fn() => $b->restore('2001-01-02'));

		//Восстановление без полного бэкапа
		(new Directory("{$restoreDirPath}2000-01-01/"))->delete();
		$this->assertException(fn() => $b->restore('2000-01-01_12'));
	}



	public function testRestoreDumpCommands () {
		$b = $this->_backupForRestore();

		$restoreDirPath      = "{$this->restoreDirectory}2000-01-03_15/";
		$restoreDirNamesList = $this->_getRestoreDirNamesList();

		foreach ($restoreDirNamesList as $dirName) {
			(new Directory("$restoreDirPath/$dirName/"))->create();
		}

		ob_start();
		$b->restore('2000-01-03_15', true);
		$outputStringsList   = $this->_getCommandOutputStringsList(ob_get_clean());

		$this->assertCount(11, $outputStringsList);
		for ($a = 0; $a < 7; $a++) {
			$restoreDirName = $restoreDirNamesList[$a];
			$this->assertMatchesRegularExpression("~^(cp -r|xcopy) \"{$this->backupsDirectory}$restoreDirName/\" \"{$restoreDirPath}\"~ui", $outputStringsList[$a]);
		}
		$commandIndex = 7;
		$this->assertEquals("service mysql stop", $outputStringsList[$commandIndex++]);
		$this->assertEquals("xtrabackup --copy-back --target-dir=\"{$restoreDirPath}\"", $outputStringsList[$commandIndex++]);
		$this->assertEquals("chown -R mysql:mysql /var/lib/mysql/", $outputStringsList[$commandIndex++]);
		$this->assertEquals("service mysql start", $outputStringsList[$commandIndex++]);
	}



	private function _makeBackup (array $ctorParams = []): TestBackup {
		$b = new TestBackup(
			...$ctorParams,
			osName:            (PHP_OS_FAMILY == 'Linux' ? TestBackup::OS_LINUX : TestBackup::OS_WINDOWS),
			rootDirectoryPath: $this->rootDirectory,
		);

		return $b;
	}



	private function _createBackupDir (string $dateHourString) {
		(new Directory("{$this->backupsDirectory}$dateHourString/"))->create();
	}



	private function _getCommandOutputStringsList (string $text): array {
		$text              = trim($text);
		$text              = str_replace('//', '/',    $text);
		$outputStringsList = preg_split('/[\n\r]+/ui', $text);

		return $outputStringsList;
	}



	private function _backupForRestore (): TestBackup {
		$this->_deleteTestFiles();

		$b = $this->_makeBackup([
			'fullDaysPeriod' => 3,
		]);
		$b->nowTimestamp = strtotime('2000-01-01 00:00:00');

		//2000-01-01
		$this->_assertFullBackup($b, '2000-01-01');
		$this->_assertIncrementalBackup($b, '2000-01-01',    '2000-01-01 06:10:00');
		$this->_assertIncrementalBackup($b, '2000-01-01_06', '2000-01-01 12:20:00');
		$this->_assertIncrementalBackup($b, '2000-01-01_12', '2000-01-01 18:30:00');
		//2000-01-02
		$this->_assertIncrementalBackup($b, '2000-01-01_18', '2000-01-02 00:00:00');
		//2000-01-03
		$this->_assertIncrementalBackup($b, '2000-01-02_00', '2000-01-03 02:00:00');
		$this->_assertIncrementalBackup($b, '2000-01-03_02', '2000-01-03 15:15:00');

		return $b;
	}



	private function _getRestoreDirNamesList (): array {
		static $restoreDirNamesList = [
			'2000-01-01', '2000-01-01_06', '2000-01-01_12', '2000-01-01_18',
			'2000-01-02_00',
			'2000-01-03_02', '2000-01-03_15',
		];
		return $restoreDirNamesList;
	}



	private function _assertEarlyBackup (TestBackup $b, string $datetimeString = ''): BackupResult {
		if ($datetimeString) {
			$b->nowTimestamp = strtotime($datetimeString);
		}
		$r = $b->backup();
		$this->assertEquals($r::EARLY, $r->code);
		return $r;
	}



	private function _assertFullBackup (TestBackup $b, string $dateString): BackupResult {
		$b->nowTimestamp = strtotime("$dateString 00:00:00");
		$r               = $b->backup();

		$fullDirPath = "{$this->backupsDirectory}$dateString/";

		$this->assertEquals($r::FULL, $r->code);
		$this->assertDirectoryExists($fullDirPath);
		$this->_assertFullBackupCommandString($dateString, $b->lastCommandString);

		return $r;
	}

	private function _assertFullBackupCommandString (string $dateString, string $gotCommandString) {
		$this->assertEquals(
			"xtrabackup --backup --target-dir=\"{$this->backupsDirectory}$dateString/\" --parallel=4 --compress",
			$gotCommandString
		);
	}



	private function _assertIncrementalBackup (TestBackup $b, string $baseDirName, string $datetimeString): BackupResult {
		$datetime = new DateTime($datetimeString);

		$b->nowTimestamp = $datetime->timestamp;
		$r               = $b->backup();

		$incrementalDirPath = "{$this->backupsDirectory}$datetime->dateHourString/";

		$this->assertEquals($r::INCREMENTAL, $r->code);
		$this->assertDirectoryExists($incrementalDirPath);
		$this->_assertIncrementalBackupCommandString($baseDirName, $datetime->dateHourString, $b->lastCommandString);

		return $r;
	}

	private function _assertIncrementalBackupCommandString (string $baseDirDatetimeString, string $targetDirDatetimeString, string $gotCommandString) {
		$this->assertEquals(
			"xtrabackup --backup --incremental-basedir=\"{$this->backupsDirectory}$baseDirDatetimeString/\" --target-dir=\"{$this->backupsDirectory}$targetDirDatetimeString/\" --parallel=4 --compress",
			$gotCommandString
		);
	}



	public function setUp (): void {
		parent::setUp();
		$this->rootDirectory    = new Directory(__DIR__.'/files/');
		$this->backupsDirectory = new Directory($this->rootDirectory.'backups/');
		$this->restoreDirectory = new Directory($this->rootDirectory.'restore/');
		$this->_deleteTestFiles();
	}

	public function tearDown(): void {
		$this->_deleteTestFiles();
		parent::tearDown();
	}

	private function _deleteTestFiles () {
		$this->rootDirectory->delete();
	}
}



class TestBackup extends Backup {
	public int    $nowTimestamp              = 0;
	public string $lastCommandString         = '';
	public array  $restoreCommandStringsList = [];


	protected function getNowTimestamp (): int {
		return $this->nowTimestamp;
	}

	protected function runFullBackup (Directory $dir) {
		//dump($this->isDumpCommands);
		if ($this->isDumpCommands) {
			parent::runFullBackup($dir);
			return;
		}
		$this->lastCommandString = $this->getFullBackupCommandString($dir);
	}

	protected function runIncrementalBackup (Directory $prevDir, Directory $dir) {
		if ($this->isDumpCommands) {
			parent::runIncrementalBackup($prevDir, $dir);
			return;
		}
		$this->lastCommandString = $this->getIncrementalBackupCommandString($prevDir, $dir);
	}

	protected function runRestorePreparationsList (Directory $restoreDir, DateTime $datetime, iterable $dirsList) {
		$this->restoreCommandStringsList = [];
		$dirsCount = count($dirsList);
		$dirsMax   = count($dirsList) - 1;
		for ($a = 0; $a < $dirsCount; $a++) {
			$dirName                           = $dirsList[$a]->getName();
			$prevDirIndex       = max(0, $a-1);
			$targetDirName      = $dirsList[$prevDirIndex]->getName();
			$incrementalDirName = (!$a ? '' : $dirName);
			$command            = $this->getPrepareCommandString($restoreDir, $targetDirName, $incrementalDirName, ($a==$dirsMax));
			$this->restoreCommandStringsList[] = $command;
		}
	}



	protected function runCommand (string $command) {
		if (
			!$this->isDumpCommands and (
				str_starts_with($command, 'service')    or
				str_starts_with($command, 'xtrabackup') or
				str_starts_with($command, 'chown')
			)
		) {
			$this->restoreCommandStringsList[] = $command;
			return;
		}

		parent::runCommand($command);
	}
}
