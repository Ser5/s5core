<?php
require_once 'S5/Db/Command.php';

class S5_Db_CommandTest extends \PHPUnit\Framework\TestCase {
	private $dbServer   = 'localhost';
	private $dbLogin    = 'root';
	private $dbPassword = 'admin';
	private $con;
	private $commonQuery = 'mixed = @mixed';
	private $commonCommand;
	
	public function __construct () {
		$this->con = mysql_connect($this->dbServer, $this->dbLogin, $this->dbPassword);
		$this->commonCommand = new S5_Db_Command($this->commonQuery, $this->con);
	}
	
	public function test1 () {
		$query = 'insert into #__test (name, content, creator_id) values (@name, @content, @creator_id)';
		$cmd = new S5_Db_Command($query, $this->con);
		$cmd->setParam('name', 'name', 'string');
		$cmd->setParam('content', 'content', 'string');
		$cmd->setParam('creator_id', '1', 'int');
		$expected = "insert into #__test (name, content, creator_id) values ('name', 'content', 1)";
		$got = $cmd->getString();
		$this->assertEquals($expected, $got);
	}
	
	public function testStrings () {
		//OK
		$this->commonCommand->setParam('mixed', 'string', 'string');
		//OK
		$this->commonCommand->setParam('mixed', 'string', 'string(6)');
		//Error
		try {
			$this->commonCommand->setParam('mixed', 'string', 'string(5)');
		} catch (S5_Db_Exception $e) {
			return;
		}
		$this->fail('Проверка длины не сработала.');
	}
	
	public function testInts () {
		//OK
		$this->commonCommand->setParam('mixed', 1, 'int');
		//OK
		$this->commonCommand->setParam('mixed', -1, 'int');
		//Error
		try {
			$this->commonCommand->setParam('mixed', 'boo!', 'int');
		} catch (S5_Db_Exception $e) {
			return;
		}
		$this->fail('Проверка целого не сработала.');
	}

	public function testUints () {
		//OK
		$this->commonCommand->setParam('mixed', 1, 'uint');
		//Error
		try {
			$this->commonCommand->setParam('mixed', -1, 'uint');
		} catch (S5_Db_Exception $e) {
			return;
		}
		$this->fail('Проверка беззнакового целого не сработала.');
	}

	public function testFloats () {
		//OK
		$this->commonCommand->setParam('mixed', 10.1, 'float');
		//Error
		try {
			$this->commonCommand->setParam('mixed', 'boo!', 'float');
		} catch (S5_Db_Exception $e) {
			return;
		}
		$this->fail('Проверка флоата не сработала.');
	}

	public function testBools () {
		//OK
		$this->commonCommand->setParam('mixed', 0, 'bool');
		//OK
		$this->commonCommand->setParam('mixed', 1, 'bool');
		//Error
		try {
			$this->commonCommand->setParam('mixed', 2, 'bool');
		} catch (S5_Db_Exception $e) {
			return;
		}
		$this->fail('Проверка булеана-шмулеана не сработала.');
	}

	public function testDates () {
		//OK
		$this->commonCommand->setParam('mixed', '2000-01-01', 'date');
		//Error
		try {
			$this->commonCommand->setParam('mixed', '2000-01-02-03', 'date');
		} catch (S5_Db_Exception $e) {
			return;
		}
		$this->fail('Проверка даты не сработала.');
	}

	public function testDatetimes () {
		//OK
		$this->commonCommand->setParam('mixed', '2000-01-01', 'datetime');
		//OK
		$this->commonCommand->setParam('mixed', '2000-01-01 00:00', 'datetime');
		//OK
		$this->commonCommand->setParam('mixed', '2000-01-01 00:00:00', 'datetime');
		//Error
		try {
			$this->commonCommand->setParam('mixed', '2000-01-02 hack!', 'datetime');
		} catch (S5_Db_Exception $e) {
			return;
		}
		$this->fail('Проверка даты не сработала.');
	}
}
