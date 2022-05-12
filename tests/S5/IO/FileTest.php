<?
namespace S5\IO;

class FileTest extends TestCase {
	private $_filePath;
	private $_fileContent = 'one two three.';


	public function __construct (...$p) {
		parent::__construct(...$p);

		$this->setTestDirSubpath('File');
		$this->_fileName = 'test.txt';
		$this->_filePath = $this->testDirPath.$this->_fileName;
	}



	public function testConstruct () {
		$filePath = $this->testDirPath.'construct.txt';
		$file     = new File($filePath);
		$this->assertTrue(true);
	}



	public function testConstructFromHandle () {
		$fh   = fopen($this->_filePath, 'w+');
		$file = new File($fh);
		$this->assertEquals($fh, $file->open('w+'));
		$this->assertEquals($this->_filePath, $file->getPath());
		fclose($fh);
	}



	public function testConstructWithInvalidChars () {
		$errorsCounter = 0;
		$pathsList     = array('+','*','?');
		foreach ($pathsList as $path) {
			try {
				new File($path);
			} catch (\InvalidArgumentException $e) {
				$errorsCounter++;
			}
		}
		$this->assertEquals(count($pathsList), $errorsCounter);
	}



	public function testPathNormalization () {
		$f = new File('c:\dir1//wrongdir/../dir2/./1.txt');
		$this->assertEquals('c:/dir1/dir2/1.txt', (string)$f);
	}



	public function testPutContents () {
		$file = new File($this->_filePath);
		$file->putContents($this->_fileContent);
		$this->assertEquals($this->_fileContent, file_get_contents($this->_filePath));
		unlink($this->_filePath);
	}



	public function testPutContentsWithPathAutocreation () {
		$filePath = $this->testDirPath.'/dir1/dir2/dir3/putContents.txt';
		$file     = new File($filePath);
		$file->putContents($this->_fileContent);
		$this->assertEquals($this->_fileContent, file_get_contents($filePath));
		$this->deleteDirectory('dir1');
	}



	public function testGetContents () {
		$file = new File($this->_filePath);
		$file->putContents($this->_fileContent);
		$this->assertEquals($this->_fileContent, $file->getContents());
		unlink($this->_filePath);
	}



	public function testInvalidGetContents () {
		$this->expectException(\Exception::class);
		$file = new File($this->_filePath);
		$this->assertFileDoesNotExist($this->_filePath);
		$file->getContents();
	}



	public function testIsExists () {
		$file = new File($this->_filePath);
		$this->assertFalse($file->isExists());
		$file->putContents($this->_fileContent);
		$this->assertTrue($file->isExists());
		unlink($this->_filePath);
		$this->assertFalse($file->isExists());
	}



	public function testIsFileAndIsDir () {
		$file = new File($this->_filePath);
		$this->assertTrue($file->isFile());
		$this->assertFalse($file->isDirectory());
	}



	public function testDelete () {
		$file = new File($this->_filePath);
		$this->assertFileDoesNotExist($this->_filePath);

		$file->putContents($this->_fileContent);
		$this->assertFileExists($this->_filePath);

		$file->delete();
		$this->assertFileDoesNotExist($this->_filePath);
	}



	public function testRename () {
		$currentPath = $this->_filePath;

		$file = new File($this->_filePath);
		$file->putContents($this->_fileContent);
		$this->assertFileExists($currentPath);
		$this->assertEquals($currentPath, $file->getPath());

		//Переименование в той же директории, без перемещения
		$newName     = 'renamed.txt';
		$renamedPath = $this->testDirPath.$newName;
		$file->rename($newName);
		$this->assertFileDoesNotExist($currentPath);
		$this->assertFileExists($renamedPath);
		$this->assertEquals($renamedPath, $file->getPath());

		//Переименование с перемещением
		$movedPath = $this->testDirPath.'dir1/dir2/dir3/moved.txt';
		$file->rename($movedPath);
		$this->assertFileDoesNotExist($renamedPath);
		$this->assertFileExists($movedPath);
		$this->assertEquals($movedPath, $file->getPath());
	}



	public function testMove () {
		$targetDirectoryPath = $this->testDirPath.'dir1/dir2/dir3/';
		$targetFilePath      = $targetDirectoryPath.$this->_fileName;

		$file = new File($this->_filePath);
		$file->putContents($this->_fileContent);
		$this->assertFileExists($this->_filePath);
		$this->assertFileDoesNotExist($targetFilePath);

		$file->move($targetDirectoryPath);
		$this->assertFileDoesNotExist($this->_filePath);
		$this->assertFileExists($targetFilePath);
	}



	public function testOpen () {
		$file = new File($this->_filePath);
		$fh   = $file->open('w+');
		$text = 'test';

		$r    = fwrite($fh, $text);
		$this->assertEquals(strlen($text), $r);

		$file->close();
		$this->assertEquals($text, $file->getContents());
	}



	public function testLock () {
		$file = new File($this->_filePath);
		$this->assertFalse($file->isLocked());
		$file->lock('w', LOCK_EX);
		$this->assertTrue($file->isLocked());
		$file->unlock();
		$this->assertFalse($file->isLocked());
		$file->lock('w', LOCK_EX);
		$this->assertTrue($file->isLocked());
		$file->lock('w', LOCK_UN);
		$this->assertFalse($file->isLocked());
	}



	public function testInitTemp () {
		$file = File::initTemp();
		$this->assertTrue($file->isExists());
		$file->putContents('temp');
		$this->assertEquals('temp', $file->getContents());
	}
}
