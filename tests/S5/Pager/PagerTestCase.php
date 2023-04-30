<?
namespace S5\Pager;

class PagerTestCase extends \S5\TestCase {
	protected array $pagerParams;

	public function __construct ($p = []) {
		parent::__construct(...$p);
		$this->pagerParams = [
			'items_amount'   => 300,
			'items_per_page' => 10,
			'template'       => '[3 4*5 3]',
			'linker'         => fn($pageNumber)=>"/articles/?page=$pageNumber",
			'page_number'    => 15,
		];
	}



	public function getPager ($params = []) {
		return new Pager($params + $this->pagerParams);
	}

	public function getPagerResult ($params = []) {
		$p = new Pager($params + $this->pagerParams);
		return $p->get();
	}
}
