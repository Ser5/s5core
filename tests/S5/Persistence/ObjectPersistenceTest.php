<?
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'S5/IO/Path.php';
require_once 'S5/IO/Item.php';
require_once 'S5/IO/ItemsList.php';
require_once 'S5/IO/File.php';
require_once 'S5/IO/Directory.php';
require_once 'S5/Persistence/Object.php';
require_once 'S5/Persistence/Adapters/IO/IAdapter.php';
require_once 'S5/Persistence/Adapters/IO/File.php';
require_once 'S5/Persistence/Adapters/IO/VarExport.php';
require_once 'S5/Persistence/Adapters/IO/PDO.php';
require_once 'S5/Persistence/Adapters/Formatters/IAdapter.php';
require_once 'S5/Persistence/Adapters/Formatters/VarExport.php';
require_once 'S5/Persistence/Adapters/Formatters/Serialize.php';
//error_reporting(E_ERROR);

class S5_Persistence_ObjectTest extends \PHPUnit\Framework\TestCase {
	private $_dirPath;
	private $_dir;
	private $_dbh;

	public function __construct () {
		$this->_createTestDir();
		$this->_createTestTable();
	}

	private function _createTestDir () {
		$this->_dirPath = __DIR__.'/S5_Persistence_Object/';
		$this->_dir = new S5_IO_Directory($this->_dirPath);
		$this->_dir->create();
	}

	private function _createTestTable () {
		$this->_dbh = new PDO('mysql:host=localhost;dbname=unit_tests', 'root', '');
		$this->_dbh->exec('SET NAMES utf-8');
	}

	public function setUp () {
	}

	private function _deleteTestData () {
		$this->_dir->clear();
		$this->_dbh->exec('DELETE FROM s5_persistence_pdo');
	}



	public function test () {
		$filePath  = $this->_dirPath."/1.php";
		$adaptersList = array(
			array(
				new S5_Persistence_Adapters_IO_VarExport($filePath),
				new S5_Persistence_Adapters_Formatters_VarExport()
			),
			array(
				new S5_Persistence_Adapters_IO_File($filePath),
				new S5_Persistence_Adapters_Formatters_Serialize()
			),
			array(
				new S5_Persistence_Adapters_IO_PDO(array(
					'dbh'              => $this->_dbh,
					'table_name'       => 's5_persistence_pdo',
					'id_column_name'   => 'id',
					'data_column_name' => 'value',
					'id'               => 1,
				)),
				new S5_Persistence_Adapters_Formatters_Serialize()
			),
		);
		$data = array(
			'a' => 1,
			'b' => 2,
			'c' => 3,
		);
		foreach ($adaptersList as $adapters) {
			$this->_deleteTestData();
			$data = array(
				'a' => 1,
				'b' => 2,
				'c' => 3,
			);
			$io        = $adapters[0];
			$formatter = $adapters[1];
			$p = S5_Persistence_Object::create($data, $io, $formatter);
			$p->save();
			$data['d'] = 4;
			$data['e'] = 5;
			$p->save();
			unset($p);
			$p = S5_Persistence_Object::load($io, $formatter);
			$gotData = &$p->get();
			$this->assertEquals($data, $gotData);
			$gotData['x'] = 10;
			$gotData['y'] = 20;
			$p->save();
			$data = $gotData;
			unset($p);
			unset($gotData);
			$p = S5_Persistence_Object::load($io, $formatter);
			$gotData = &$p->get();
			$this->assertEquals($data, $gotData);
			unset($p);
			unset($data);
			unset($gotData);
		}
	}
}
