<?
namespace S5\IO;

class ItemsListTest extends TestCase {
	public function __construct (...$p) {
		parent::__construct(...$p);
		$this->setTestDirSubpath('Directory');
	}



	public function testDelete () {
		//2 директории, 3 файла
		$createItems = function () {
			$d = new Directory($this->testDirPath);
			$d->clear();
			for ($a = 1; $a <= 2; $a++) {
				$name = "d$a";
				$d    = new Directory($this->testDirPath."/$name");
				$this->assertFalse($d->isExists());
				$d->create();
				$this->assertTrue($d->isExists());
			}

			for ($a = 1; $a <= 3; $a++) {
				$name = "f$a";
				$f    = new File($this->testDirPath."/$name.txt");
				$this->assertFalse($f->isExists());
				$f->putContents('1');
				$this->assertTrue($f->isExists());
			}
		};

		$d = new Directory($this->testDirPath);

		//Удаление без фильтра
		$createItems();
		$list = $d->getItemsList();
		$this->assertEquals(5, $list->count());
		$list->sort();
		$this->assertEquals('d1',     $list[0]->getName());
		$this->assertEquals('d2',     $list[1]->getName());
		$this->assertEquals('f1.txt', $list[2]->getName());
		$this->assertEquals('f2.txt', $list[3]->getName());
		$this->assertEquals('f3.txt', $list[4]->getName());

		$list->delete();
		foreach ($list as $item) {
			$this->assertFalse($item->isExists());
		}

		//Удаление с фильтром - удалим только папки
		$createItems();
		$d->getItemsList()->delete(fn($item) => $item->isDirectory());
		$list = $d->getItemsList();
		$this->assertEquals(3, $list->count());
		$list->sort();
		$this->assertEquals('f1.txt', $list[0]->getName());
		$this->assertEquals('f2.txt', $list[1]->getName());
		$this->assertEquals('f3.txt', $list[2]->getName());

		//Удаление с фильтром - оставим только записи с единичкой в названии
		$createItems();
		$d->getItemsList()->delete(fn($item) => (strpos($item->getName(), '1') === false));
		$list = $d->getItemsList();
		$this->assertEquals(2, $list->count());
		$list->sort();
		$this->assertEquals('d1',     $list[0]->getName());
		$this->assertEquals('f1.txt', $list[1]->getName());
	}



	public function testSort () {
		$l = $this->_initItemsList([
			'2/alfa.txt',
			'2/charlie.txt',
			'2/bravo.txt',
			'2/delta.txt',
			'1/alfa.txt',
			'1/charlie.txt',
			'1/bravo.txt',
			'1/delta.txt',
		]);
		$tp = $this->testDirPath;

		$this->assertEquals($tp.'2/alfa.txt',    (string)$l[0]);
		$this->assertEquals($tp.'2/charlie.txt', (string)$l[1]);
		$this->assertEquals($tp.'2/bravo.txt',   (string)$l[2]);
		$this->assertEquals($tp.'2/delta.txt',   (string)$l[3]);
		$this->assertEquals($tp.'1/alfa.txt',    (string)$l[4]);
		$this->assertEquals($tp.'1/charlie.txt', (string)$l[5]);
		$this->assertEquals($tp.'1/bravo.txt',   (string)$l[6]);
		$this->assertEquals($tp.'1/delta.txt',   (string)$l[7]);

		$l->sort('path', 'asc');
		$this->assertEquals($tp.'1/alfa.txt',    (string)$l[0]);
		$this->assertEquals($tp.'1/bravo.txt',   (string)$l[1]);
		$this->assertEquals($tp.'1/charlie.txt', (string)$l[2]);
		$this->assertEquals($tp.'1/delta.txt',   (string)$l[3]);
		$this->assertEquals($tp.'2/alfa.txt',    (string)$l[4]);
		$this->assertEquals($tp.'2/bravo.txt',   (string)$l[5]);
		$this->assertEquals($tp.'2/charlie.txt', (string)$l[6]);
		$this->assertEquals($tp.'2/delta.txt',   (string)$l[7]);

		$l->sort('path', 'desc');
		$this->assertEquals($tp.'2/delta.txt',   (string)$l[0]);
		$this->assertEquals($tp.'2/charlie.txt', (string)$l[1]);
		$this->assertEquals($tp.'2/bravo.txt',   (string)$l[2]);
		$this->assertEquals($tp.'2/alfa.txt',    (string)$l[3]);
		$this->assertEquals($tp.'1/delta.txt',   (string)$l[4]);
		$this->assertEquals($tp.'1/charlie.txt', (string)$l[5]);
		$this->assertEquals($tp.'1/bravo.txt',   (string)$l[6]);
		$this->assertEquals($tp.'1/alfa.txt',    (string)$l[7]);

		$l->sort('name', 'asc');
		$this->assertEquals('alfa.txt',    $l[0]->getName());
		$this->assertEquals('alfa.txt',    $l[1]->getName());
		$this->assertEquals('bravo.txt',   $l[2]->getName());
		$this->assertEquals('bravo.txt',   $l[3]->getName());
		$this->assertEquals('charlie.txt', $l[4]->getName());
		$this->assertEquals('charlie.txt', $l[5]->getName());
		$this->assertEquals('delta.txt',   $l[6]->getName());
		$this->assertEquals('delta.txt',   $l[7]->getName());

		$l->sort('name', 'desc');
		$this->assertEquals('delta.txt',   $l[0]->getName());
		$this->assertEquals('delta.txt',   $l[1]->getName());
		$this->assertEquals('charlie.txt', $l[2]->getName());
		$this->assertEquals('charlie.txt', $l[3]->getName());
		$this->assertEquals('bravo.txt',   $l[4]->getName());
		$this->assertEquals('bravo.txt',   $l[5]->getName());
		$this->assertEquals('alfa.txt',    $l[6]->getName());
		$this->assertEquals('alfa.txt',    $l[7]->getName());

		$this->assertException(
			fn() => $l->sort('error', 'asc'),
			"Erroneous sort by is not catched"
		);

		$this->assertException(
			fn() => $l->sort('name', 'error'),
			"Erroneous sort order is not catched"
		);
	}



	public function testFilter () {
		$list = new ItemsList();
		for ($a = 1; $a <= 10; $a++) {
			$list->append(new File("/f$a.txt"));
		}
		//Оставим только записи с единичкой в названии
		$list->filter(fn($item) => (strpos($item->getName(), '1') !== false));
		$this->assertEquals(2, $list->count());
	}



	private function _initItemsList ($filePathsList) {
		$list = new ItemsList();
		foreach ($filePathsList as $path) {
			$list->append(new File($this->testDirPath.$path));
		}
		return $list;
	}
}
