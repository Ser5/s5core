<?
namespace S5\Pager;

class PagerTemplatesTest extends PagerTestCase {
	public function testFull () {
		$p = $this->getPager();
		$r = $p->get();

		$htmlStringsList = $this->_getHtmlStringsList('_showFull', $r);

		$this->_checkPages($htmlStringsList, [
			['button',   1,  'First'],
			['button',   5,  'Rew'],
			['button',   14, 'Prev'],
			['sequence', 1],
			['sequence', 2],
			['sequence', 3],
			['gap'],
			['sequence', 11],
			['sequence', 12],
			['sequence', 13],
			['sequence', 14],
			['active',   15],
			['sequence', 16],
			['sequence', 17],
			['sequence', 18],
			['sequence', 19],
			['sequence', 20],
			['gap'],
			['sequence', 28],
			['sequence', 29],
			['sequence', 30],
			['button',   16, 'Next'],
			['button',   25, 'FF'],
			['button',   30, 'Last'],
		]);
	}



	public function testWindow () {
		$p = $this->getPager(['template' => '4*5']);
		$r = $p->get();

		$htmlStringsList = $this->_getHtmlStringsList('_showWindow', $r);

		$this->_checkPages($htmlStringsList, [
			['sequence', 11],
			['sequence', 12],
			['sequence', 13],
			['sequence', 14],
			['active',   15],
			['sequence', 16],
			['sequence', 17],
			['sequence', 18],
			['sequence', 19],
			['sequence', 20],
		]);
	}



	public function testFirstWindowLast () {
		$p = $this->getPager(['template' => '4*5']);
		$r = $p->get();

		$htmlStringsList = $this->_getHtmlStringsList('_showFirstWindowLast', $r);

		$this->_checkPages($htmlStringsList, [
			['button',   1,  'First'],
			['sequence', 11],
			['sequence', 12],
			['sequence', 13],
			['sequence', 14],
			['active',   15],
			['sequence', 16],
			['sequence', 17],
			['sequence', 18],
			['sequence', 19],
			['sequence', 20],
			['button',   30, 'Last'],
		]);
	}



	private function _getHtmlStringsList (string $methodName, PagerResult $r) {
		ob_start();
		$this->$methodName($r);
		$html = ob_get_clean();
		$html = str_replace("\t", '', $html);
		$html = str_replace("\r", '', $html);
		$html = preg_replace('/\n{2,}/', "\n", $html);
		$html = trim($html);
		$htmlStringsList = explode("\n", $html);
		return $htmlStringsList;
	}



	private function _showPage (Page $page, string $text) {
		if ($page->isClickable()) {?>
			<a href="<?=$page?>"><?=$text?></a>
		<?} else {?>
			<span><?=$text?></span>
		<?}
	}



	private function _showFull (PagerResult $r) {
		$this->_showPage($r->getFirst(), 'First');
		$this->_showPage($r->getRew(),   'Rew');
		$this->_showPage($r->getPrev(),  'Prev');

		foreach ($r->getSequence() as $p) {
			if (!$p->isGap()) {
				$this->_showPage($p,  $p->getNumber());
			} else {
				$this->_showPage($p,  '...');
			}
		}

		$this->_showPage($r->getNext(), 'Next');
		$this->_showPage($r->getFF(),   'FF');
		$this->_showPage($r->getLast(), 'Last');
	}



	private function _showWindow (PagerResult $r) {
		foreach ($r->getSequence() as $p) {
			$this->_showPage($p,  $p->getNumber());
		}
	}



	private function _showFirstWindowLast (PagerResult $r) {
		$this->_showPage($r->getFirst(), 'First');

		foreach ($r->getSequence() as $p) {
			$this->_showPage($p,  $p->getNumber());
		}

		$this->_showPage($r->getLast(), 'Last');
	}



	private function _checkPages (array $htmlStringsList, array $rules) {
		$rulesAmount = count($rules);
		$this->assertCount($rulesAmount, $htmlStringsList);
		for ($a = 0; $a < $rulesAmount; $a++) {
			$htmlString = $htmlStringsList[$a];
			$rule       = $rules[$a];
			$type       = array_shift($rule);
			$this->$type(...array_merge([$htmlString], $rule));
		}
	}

	private function button (string $htmlString, int $pageNumber, string $text) {
		$this->assertEquals("<a href=\"/articles/?page=$pageNumber\">$text</a>", $htmlString);
	}

	private function sequence (string $htmlString, int $pageNumber) {
		$this->assertEquals("<a href=\"/articles/?page=$pageNumber\">$pageNumber</a>", $htmlString);
	}

	private function active (string $htmlString, int $pageNumber) {
		$this->assertEquals("<span>$pageNumber</span>", $htmlString);
	}

	private function gap (string $htmlString) {
		$this->assertEquals("<span>...</span>", $htmlString);
	}
}
