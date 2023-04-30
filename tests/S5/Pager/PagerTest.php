<?
namespace S5\Pager;

class PagerTest extends PagerTestCase {
	public function testGetParams () {
		$this->_comparePagers([]);
		$this->_comparePagers(['page_number' => 1]);
		$this->_comparePagers([
			'items_amount'   => 100,
			'items_per_page' => 20,
			'template'       => '4*5',
			'linker'         => fn($pageNumber)=>"/news/?page=$pageNumber",
			'page_number'    => 2,
		]);
	}

	private function _comparePagers ($customParams) {
		$defaultPager            = $this->getPager();
		$customPager             = new Pager($customParams + $this->pagerParams);
		$customizedDefaultResult = $defaultPager->get($customParams);
		$customResult            = $customPager->get();
		$this->assertEquals($customizedDefaultResult, $customResult);
	}



	public function testPageNumberFixing () {
		$p = $this->getPager();
		$r = $p->get();

		$this->assertEquals(15, $r->getOriginalPageNumber());
		$this->assertEquals(15, $r->getPageNumber());
		$this->assertFalse($r->isPageNumberFixed());

		$params = $this->pagerParams;
		unset($params['page_number']);
		$r = (new Pager($params))->get();
		$this->assertEquals(false, $r->getOriginalPageNumber());
		$this->assertEquals(1,     $r->getPageNumber());
		$this->assertTrue($r->isPageNumberFixed());

		$r = $p->get(['page_number' => 0]);
		$this->assertEquals(0, $r->getOriginalPageNumber());
		$this->assertEquals(1, $r->getPageNumber());
		$this->assertTrue($r->isPageNumberFixed());

		$r = $p->get(['page_number' => 31]);
		$this->assertEquals(31, $r->getOriginalPageNumber());
		$this->assertEquals(30, $r->getPageNumber());
		$this->assertTrue($r->isPageNumberFixed());
	}



	public function testPagesMerging () {
		//От начала
		//1 [2 3 4 5 [6] 7 8 9 10 11] ... 28 29 30
		$r = $this->getPagerResult(['page_number' => 6]);
		$s = $r->getSequence();
		$this->assertCount(15, $s);

		for ($ix = 0, $expectedPageNumber = 1; $ix < 11; $ix++, $expectedPageNumber++) {
			$this->assertEquals($expectedPageNumber, $s[$ix]->getNumber(), "Testing sequence entry $ix");
		}
		$this->assertTrue($s[11]->isGap());
		$this->assertEquals(28, $s[12]->getNumber());
		$this->assertEquals(29, $s[13]->getNumber());
		$this->assertEquals(30, $s[14]->getNumber());

		//От конца
		//1 2 3 ... [18 19 20 21 [22] 23 24 25 26 27] 28 29 30
		$r = $this->getPagerResult(['page_number' => 22]);
		$s = $r->getSequence();
		$this->assertCount(17, $s);

		$this->assertEquals(1, $s[0]->getNumber());
		$this->assertEquals(2, $s[1]->getNumber());
		$this->assertEquals(3, $s[2]->getNumber());
		$this->assertTrue($s[3]->isGap());
		for ($ix = 4, $expectedPageNumber = 18; $ix < 17; $ix++, $expectedPageNumber++) {
			$this->assertEquals($expectedPageNumber, $s[$ix]->getNumber(), "Testing sequence entry $ix");
		}
	}
}
