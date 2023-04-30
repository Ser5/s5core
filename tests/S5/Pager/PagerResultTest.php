<?
namespace S5\Pager;

class PagerResultTest extends PagerTestCase {
	public function testPagerResult () {
		$r = $this->getPagerResult(['linker' => fn($pageNumber)=>"/?p=$pageNumber"]);

		$this->assertEquals(15,        $r->getOriginalPageNumber());
		$this->assertEquals(15,        $r->getPageNumber());
		$this->assertEquals(false,     $r->isPageNumberFixed());
		$this->assertEquals(30,        $r->countPages());
		$this->assertEquals(300,       $r->countItems());
		$this->assertEquals(140,       $r->getItemsFrom());
		$this->assertEquals(149,       $r->getItemsTo());
		$this->assertEquals([140, 10], $r->getLimit());
		$this->assertEquals(10,        $r->getPagesWindowWidth());
		$this->assertEquals(24,        count($r->getPagesList()));
		$this->assertEquals(18,        count($r->getSequence()));

		//1 2 3
		$this->_checkPage($r->getSequence()[0], [
			'getNumber'   => 1,
			'getUrl'      => '/?p=1',
			'isClickable' => true,
			'isSequence'  => true,
			'isNumber'    => true,
		]);
		$this->_checkPage($r->getSequence()[2], [
			'getNumber'   => 3,
			'getUrl'      => '/?p=3',
			'isClickable' => true,
			'isSequence'  => true,
			'isNumber'    => true,
		]);

		//Gap 1
		$this->_checkPage($r->getSequence()[3], [
			'getNumber'   => 0,
			'getUrl'      => '',
			'isClickable' => false,
			'isSequence'  => true,
			'isGap'       => true,
		]);

		//11 12 13 14 [15] 16 17 18 19 20
		$this->_checkPage($r->getSequence()[4], [
			'getNumber'   => 11,
			'getUrl'      => '/?p=11',
			'isClickable' => true,
			'isSequence'  => true,
			'isNumber'    => true,
		]);
		$this->_checkPage($r->getSequence()[7], [
			'getNumber'   => 14,
			'getUrl'      => '/?p=14',
			'isClickable' => true,
			'isSequence'  => true,
			'isNumber'    => true,
		]);
		$this->_checkPage($r->getSequence()[8], [
			'getNumber'   => 15,
			'getUrl'      => '/?p=15',
			'isClickable' => false,
			'isSequence'  => true,
			'isNumber'    => true,
		]);
		$this->_checkPage($r->getSequence()[9], [
			'getNumber'   => 16,
			'getUrl'      => '/?p=16',
			'isClickable' => true,
			'isSequence'  => true,
			'isNumber'    => true,
		]);

		//Gap 2
		$this->_checkPage($r->getSequence()[14], [
			'getNumber'   => 0,
			'getUrl'      => '',
			'isClickable' => false,
			'isSequence'  => true,
			'isGap'       => true,
		]);

		// 28 29 30
		$this->_checkPage($r->getSequence()[15], [
			'getNumber'   => 28,
			'getUrl'      => '/?p=28',
			'isClickable' => true,
			'isSequence'  => true,
			'isNumber'    => true,
		]);
		$this->_checkPage($r->getSequence()[17], [
			'getNumber'   => 30,
			'getUrl'      => '/?p=30',
			'isClickable' => true,
			'isSequence'  => true,
			'isNumber'    => true,
		]);

		$this->_checkPage($r->getFirst(), [
			'getNumber'   => 1,
			'getUrl'      => '/?p=1',
			'isClickable' => true,
			'isButton'    => true,
			'isFirst'     => true,
		]);
		$this->_checkPage($r->getRew(), [
			'getNumber'   => 5,
			'getUrl'      => '/?p=5',
			'isClickable' => true,
			'isButton'    => true,
			'isRew'       => true,
		]);
		$this->_checkPage($r->getPrev(), [
			'getNumber'   => 14,
			'getUrl'      => '/?p=14',
			'isClickable' => true,
			'isButton'    => true,
			'isPrev'      => true,
		]);
		$this->_checkPage($r->getNext(), [
			'getNumber'   => 16,
			'getUrl'      => '/?p=16',
			'isClickable' => true,
			'isButton'    => true,
			'isNext'      => true,
		]);
		$this->_checkPage($r->getFF(), [
			'getNumber'   => 25,
			'getUrl'      => '/?p=25',
			'isClickable' => true,
			'isButton'    => true,
			'isFF'        => true,
		]);
		$this->_checkPage($r->getLast(), [
			'getNumber'   => 30,
			'getUrl'      => '/?p=30',
			'isClickable' => true,
			'isButton'    => true,
			'isLast'      => true,
		]);
	}



	public function testFirstPage () {
		$r = $this->getPagerResult([
			'page_number' => 1,
			'linker'      => fn($pageNumber)=>"/?p=$pageNumber",
		]);

		$this->assertEquals(20, count($r->getPagesList()));
		$this->assertEquals(14, count($r->getSequence()));
		$this->_checkPage($r->getFirst(), ['isClickable' => false, 'isButton' => true, 'isFirst' => true]);
		$this->_checkPage($r->getRew(),   ['isClickable' => false, 'isButton' => true, 'isRew'   => true]);
		$this->_checkPage($r->getPrev(),  ['isClickable' => false, 'isButton' => true, 'isPrev'  => true]);
		$this->_checkPage($r->getNext(),  ['isClickable' => true,  'isButton' => true, 'isNext'  => true]);
		$this->_checkPage($r->getFF(),    ['isClickable' => true,  'isButton' => true, 'isFF'    => true]);
		$this->_checkPage($r->getLast(),  ['isClickable' => true,  'isButton' => true, 'isLast'  => true]);
	}



	public function testLastPage () {
		$r = $this->getPagerResult([
			'page_number' => 30,
			'linker'      => fn($pageNumber)=>"/?p=$pageNumber",
		]);

		$this->assertEquals(20, count($r->getPagesList()));
		$this->assertEquals(14, count($r->getSequence()));
		$this->_checkPage($r->getFirst(), ['isClickable' => true,  'isButton' => true, 'isFirst' => true]);
		$this->_checkPage($r->getRew(),   ['isClickable' => true,  'isButton' => true, 'isRew'   => true]);
		$this->_checkPage($r->getPrev(),  ['isClickable' => true,  'isButton' => true, 'isPrev'  => true]);
		$this->_checkPage($r->getNext(),  ['isClickable' => false, 'isButton' => true, 'isNext'  => true]);
		$this->_checkPage($r->getFF(),    ['isClickable' => false, 'isButton' => true, 'isFF'    => true]);
		$this->_checkPage($r->getLast(),  ['isClickable' => false, 'isButton' => true, 'isLast'  => true]);
	}



	private function _checkPage (Page $page, array $expectedData) {
		$expectedData += [
			'isClickable' => false,
			'isButton'    => false,
			'isSequence'  => false,
			'isFirst'     => false,
			'isRew'       => false,
			'isPrev'      => false,
			'isNext'      => false,
			'isFF'        => false,
			'isLast'      => false,
			'isGap'       => false,
			'isNumber'    => false,
		];
		foreach ($expectedData as $methodName => $expectedValue) {
			$this->assertEquals($expectedValue, $page->$methodName(), "Testing {$page->getType()}::$methodName()");
		}
	}
}
