<?
namespace S5\IO;

class DirectoryTest extends TestCase {
	public function __construct (...$p) {
		parent::__construct(...$p);
		$this->setTestDirSubpath('Directory');
	}



	public function setUp (): void {
		parent::setUp();

		if (!mkdir($this->testDirPath.'/1',   null, true)) $this->fail('failed');
		if (!mkdir($this->testDirPath.'/1/a', null, true)) $this->fail('failed');
		if (!mkdir($this->testDirPath.'/1/b', null, true)) $this->fail('failed');
		if (!mkdir($this->testDirPath.'/1/c', null, true)) $this->fail('failed');
		if (!file_put_contents($this->testDirPath.'/1/1.txt', '1')) $this->fail('failed');
		if (!file_put_contents($this->testDirPath.'/1/2.txt', '1')) $this->fail('failed');
		if (!file_put_contents($this->testDirPath.'/1/3.txt', '1')) $this->fail('failed');
	}



	public function testPathNormalization () {
		$d = new Directory('c:\dir1//wrongdir/../dir2/./1');
		$this->assertEquals('c:/dir1/dir2/1/', $d->getPath());
	}



	public function testInitTemp () {
		$tempDir = Directory::initTemp($this->testDirPath.'/tmp/');
		$this->assertDirectoryExists($tempDir);
		$tempDir->delete();

		$tempDir = Directory::initTemp($this->testDirPath.'/tmp/', 'abc');
		$this->assertDirectoryExists($tempDir);
		$this->assertTrue(strpos(basename($tempDir), 'abc') === 0);
		$tempDir->delete();
	}



	public function testExistance () {
		$this->assertTrue((new Directory($this->testDirPath.'/1/'))->isExists());
		$this->assertFalse((new Directory($this->testDirPath.'/0/'))->isExists());
	}



	public function testIsType () {
		$dir = new Directory($this->testDirPath);
		$this->assertTrue($dir->isDirectory());
		$this->assertFalse($dir->isFile());
	}



	public function testCreate () {
		$dirPath = $this->testDirPath;

		//Пытаемся создать директорию, которая уже существует.
		//Ничего не должно произойти.
		$this->assertDirectoryExists($dirPath);
		$dir = new Directory($dirPath);
		$r   = $dir->create();
		$this->assertDirectoryExists($dirPath);
		$this->assertFalse($r);

		//Пытаемся создать директорию с путём, по которому уже лежит файл
		$filePath = $dirPath.'test_file';
		file_put_contents($filePath, 'file text');
		$dir = new Directory($filePath);
		$this->assertException(
			fn() => $dir->create(),
			"Trying to create directory with the same name as the existing file should throw an exception"
		);

		//Успешно создаём новую директорию
		$dir = new Directory($dirPath.'test_create/');
		$this->assertDirectoryDoesNotExist($dir);
		$r = $dir->create();
		$this->assertDirectoryExists($dir);
		$this->assertTrue($r);
	}



	public function testRename () {
		$beforeDirPath = $this->testDirPath.'0/';
		$afterDirPath  = $this->testDirPath.'0_renamed/';

		//Пробуем переименовать директорию, которой нет.
		//Ничего не должно произойти.
		$dir = new Directory($beforeDirPath);
		$this->assertDirectoryDoesNotExist($beforeDirPath);
		$this->assertDirectoryDoesNotExist($afterDirPath);

		$r   = $dir->rename('0_renamed');
		$this->assertDirectoryDoesNotExist($beforeDirPath);
		$this->assertDirectoryDoesNotExist($afterDirPath);
		$this->assertFalse($r);

		//Теперь переименовываем существующую директорию
		$beforeDirPath = $this->testDirPath.'1/';
		$afterDirPath  = $this->testDirPath.'1_renamed/';

		$dir = new Directory($beforeDirPath);
		$this->assertDirectoryExists($beforeDirPath);
		$this->assertDirectoryDoesNotExist($afterDirPath);
		$this->assertEquals($beforeDirPath, $dir->getPath());
		$this->assertEquals('1',            $dir->getName());

		$r = $dir->rename('1_renamed');
		$this->assertDirectoryDoesNotExist($beforeDirPath);
		$this->assertDirectoryExists($afterDirPath);
		$this->assertTrue($r);
		$this->assertEquals($afterDirPath, $dir->getPath());
		$this->assertEquals('1_renamed',   $dir->getName());

		//Пытаемся переименовать директорию при существующем
		//файле с таким же названием
		$newName       = '1_renamed2';
		$filePath      = $this->testDirPath.$newName;
		$beforeDirPath = $this->testDirPath.'1_renamed/';
		$afterDirPath  = $filePath.'/';

		file_put_contents($filePath, 'file text');
		$dir = new Directory($beforeDirPath);
		$this->assertException(
			fn() => $dir->rename($newName),
			"Trying to rename directory to the name of the existing file should throw an exception"
		);

		//Форсируем переименование, хоть файл и существует
		$r = $dir->rename($newName, true);
		$this->assertDirectoryExists($afterDirPath);
		$this->assertTrue($r);
		$this->assertEquals($afterDirPath, $dir->getPath());
		$this->assertEquals($newName,      $dir->getName());
	}



	public function testClear () {
		$d = new Directory($this->testDirPath.'/1/');
		$d->clear();
		$entries = scandir($this->testDirPath.'/1/');
		$this->assertEquals(2, count($entries));
		$this->assertEquals('.',  $entries[0]);
		$this->assertEquals('..', $entries[1]);
	}



	public function testDelete () {
		$d = new Directory($this->testDirPath.'/1/');
		$d->delete();
		$this->assertFalse(file_exists($this->testDirPath.'/1/'));
	}



	public function testGetItemsList () {
		mkdir($this->testDirPath.'/2', null, true);
		mkdir($this->testDirPath.'/3', null, true);

		file_put_contents($this->testDirPath.'/1.txt', '1');
		file_put_contents($this->testDirPath.'/2.txt', '1');
		file_put_contents($this->testDirPath.'/3.txt', '1');

		$d    = new Directory($this->testDirPath);
		$list = $d->getItemsList()->sort('name');
		$this->assertEquals(6, $list->count());
		$this->_checkItem($list[0], '\S5\IO\Directory', '1');
		$this->_checkItem($list[1], '\S5\IO\File',      '1.txt');
		$this->_checkItem($list[2], '\S5\IO\Directory', '2');
		$this->_checkItem($list[3], '\S5\IO\File',      '2.txt');
		$this->_checkItem($list[4], '\S5\IO\Directory', '3');
		$this->_checkItem($list[5], '\S5\IO\File',      '3.txt');
	}



	public function testDeleteOldFilesList () {
		$weekAgo = time() - 86400*7;
		$dayAgo  = time() - 86400;
		$dirPath = "$this->testDirPath/1/";
		$f1      = "$dirPath/1.txt";
		$f2      = "$dirPath/2.txt";
		$f3      = "$dirPath/3.txt";
		touch($f1, $weekAgo, $weekAgo);
		touch($f2, $dayAgo,  $dayAgo);

		$dir = new Directory($dirPath);
		$this->assertFileExists($f1);
		$this->assertFileExists($f2);
		$this->assertFileExists($f3);

		//Ничего не удалится
		$dir->deleteOldFilesList('2w');
		$this->assertFileExists($f1);
		$this->assertFileExists($f2);
		$this->assertFileExists($f3);

		//Удаляем файл недельной давности
		$dir->deleteOldFilesList('6d');
		$this->assertFileDoesNotExist($f1);
		$this->assertFileExists($f2);
		$this->assertFileExists($f3);

		//Удаляем файл дневной давности
		$dir->deleteOldFilesList('23h');
		$this->assertFileDoesNotExist($f1);
		$this->assertFileDoesNotExist($f2);
		$this->assertFileExists($f3);

		//Проверяем допустимые варианты написания времени
		$dir->deleteOldFilesList(123);
		$dir->deleteOldFilesList('123');
		$dir->deleteOldFilesList('1s');
		$dir->deleteOldFilesList('1m');
		$dir->deleteOldFilesList('1h');
		$dir->deleteOldFilesList('1d');
		$dir->deleteOldFilesList('1w');

		//Теперь - недопустимые варианты
		$this->assertException(fn() => $dir->deleteOldFilesList(false));
		$this->assertException(fn() => $dir->deleteOldFilesList(''));
		$this->assertException(fn() => $dir->deleteOldFilesList('a'));
		$this->assertException(fn() => $dir->deleteOldFilesList('a1'));
		$this->assertException(fn() => $dir->deleteOldFilesList('1a'));
	}



	private function _checkItem ($item, $expectedClassName, $expectedItemName) {
		$this->assertTrue($item instanceof $expectedClassName);
		$this->assertEquals($expectedItemName, $item->getName());
	}
}
