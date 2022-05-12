<?
namespace S5;

class InnerDbTest extends \S5\TestCase {
	private $_source;
	private $_db;

	public function setUp (): void {
		$this->_source = [
			['section' => 'news', 'type' => 'dogs', 'id' => 1, 'active' => true],
			['section' => 'news', 'type' => 'dogs', 'id' => 2, 'active' => true],
			['section' => 'news', 'type' => 'cats', 'id' => 3, 'active' => true],
			['section' => 'news', 'type' => 'cats', 'id' => 4, 'active' => true],
			['section' => 'shop', 'type' => 'dogs', 'id' => 5, 'active' => true],
			['section' => 'shop', 'type' => 'dogs', 'id' => 6, 'active' => false],
			['section' => 'shop', 'type' => 'cats', 'id' => 7, 'active' => false],
			['section' => 'shop', 'type' => 'cats', 'id' => 8, 'active' => true],
		];

		$this->_db = new InnerDb($this->_source);
	}



	public function testSingleFieldFilters () {
		extract($this->_getData());

		//Единичные фильтры: получение списка, одной записи, одного столбца
		$r = $db->getList(['section' => 'news']);
		$this->assertEquals(4, count($r));
		$this->assertEquals($source[0], $r[0]);
		$this->assertEquals($source[1], $r[1]);
		$this->assertEquals($source[2], $r[2]);
		$this->assertEquals($source[3], $r[3]);

		$r = $db->getList(['section' => 'news'], 'id');
		$this->assertEquals(4, count($r));
		$this->assertEquals(1, $r[0]);
		$this->assertEquals(2, $r[1]);
		$this->assertEquals(3, $r[2]);
		$this->assertEquals(4, $r[3]);

		$r = $db->get(['section' => 'news']);
		$this->assertEquals($source[0], $r);

		$r = $db->get(['section' => 'news'], 'id');
		$this->assertEquals(1, $r);

		//Фильтр по второй колонке
		$r = $db->getList(['type' => 'dogs']);
		$this->assertEquals(4, count($r));
		$this->assertEquals($source[0], $r[0]);
		$this->assertEquals($source[1], $r[1]);
		$this->assertEquals($source[4], $r[2]);
		$this->assertEquals($source[5], $r[3]);

		//Фильтр для выбора одной записи по id
		$r = $db->getList(['id' => '4']);
		$this->assertEquals(1, count($r));
		$this->assertEquals($source[3], $r[0]);
	}



	public function testMultipleFieldFilters () {
		extract($this->_getData());

		$r = $db->getList(['section' => 'news', 'type' => 'dogs']);
		$this->assertEquals(2, count($r));
		$this->assertEquals($source[0], $r[0]);
		$this->assertEquals($source[1], $r[1]);

		$r = $db->getList(['section' => 'news', 'type' => 'dogs', 'id' => 1]);
		$this->assertEquals(1, count($r));
		$this->assertEquals($source[0], $r[0]);
	}



	public function testTrueFalseFilters () {
		extract($this->_getData());

		$r = $db->getList(['active' => true]);
		$this->assertEquals(6, count($r));

		$r = $db->getList(['active' => 1]);
		$this->assertEquals(6, count($r));

		$r = $db->getList(['active' => '1']);
		$this->assertEquals(6, count($r));

		$r = $db->getList(['active' => false]);
		$this->assertEquals(2, count($r));

		$r = $db->getList(['active' => 0]);
		$this->assertEquals(2, count($r));

		$r = $db->getList(['active' => '0']);
		$this->assertEquals(2, count($r));
	}



	public function testWithoutFilters () {
		extract($this->_getData());

		$this->assertEquals(8, count($db->getList()));
		$this->assertEquals(8, count($db->getList([])));
	}



	public function testInvalidFilters () {
		extract($this->_getData());

		$r = $db->getList(['section' => 'articles']);
		$this->assertEquals(0, count($r));

		$r = $db->getList(['id' => 10]);
		$this->assertEquals(0, count($r));

		$r = $db->getList(['section' => 'news', 'type' => 'mice', 'id' => 1]);
		$this->assertEquals(0, count($r));
	}



	public function testInitWithKeys () {
		$db1 = new InnerDb([
			['section' => 'news', 'type' => 'dogs'],
			['section' => 'shop', 'type' => 'cats'],
		]);

		$db2 = new InnerDb(
			[
				['news', 'dogs'],
				['shop', 'cats'],
			],
			['section', 'type']
		);

		$this->assertEquals($db1->getList(), $db2->getList());
	}



	private function _getData () {
		return ['source' => $this->_source, 'db' => $this->_db];
	}
}
