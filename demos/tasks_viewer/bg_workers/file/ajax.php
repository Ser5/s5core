<?
require_once __DIR__.'/../../config.php';
$tm = $DIC['tasksManager'];



$limit      = $_REQUEST['limit'] ?? 20;
$pageNumber = $_REQUEST['page']  ?? 1;
//$logId       = $_REQUEST['log_id'] ?? 0;



//$data = [];
//$tm->getList(['limit' => $limit, '' => $logId]);



$dbData = include $DIC['dbFile'] ?: [];
$pager = new \S5\Pager\Pager([
	'items_amount'   => count($dbData),
	'items_per_page' => $limit,
	'page_number'    => $pageNumber,
	'linker'         => fn($p) => "/?limit=$limit&page=$p",
]);
$r = $pager->get();
$dbData = array_slice($dbData, $r->getLimit()[0], $limit);

$pagesDataList = [];
foreach ($r->getSequence() as $page) {
	$pagesDataList[] = [
		'number'      => $page->getNumber(),
		'url'         => $page->getUrl(),
		'isActive'    => ($page->getNumber() == $r->getPageNumber()),
		'isClickable' => $page->isClickable(),
		'isGap'       => $page->isGap(),
	];
}

$data = [
	'pagesList' => $pagesDataList,
	'tasksList' => $dbData,
];

echo json_encode($data);
