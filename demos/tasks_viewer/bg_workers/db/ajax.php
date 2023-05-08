<?
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/db_manager.php';
$tm = $DIC['tasksManager'];
initStorageIfNotExists();



$limit      = $_REQUEST['limit'] ?? 3;
$pageNumber = $_REQUEST['page']  ?? 1;



$pager = new \S5\Pager\Pager([
	'items_amount'   => $tm->count(),
	'items_per_page' => $limit,
	'page_number'    => $pageNumber,
	'linker'         => fn($p) => "/?limit=$limit&page=$p",
]);
$pagerResult = $pager->get();

$pagesDataList = [];
foreach ($pagerResult->getSequence() as $page) {
	$pagesDataList[] = [
		'number'      => $page->getNumber(),
		'url'         => $page->getUrl(),
		'isActive'    => ($page->getNumber() == $pagerResult->getPageNumber()),
		'isClickable' => $page->isClickable(),
		'isGap'       => $page->isGap(),
	];
}

$tasksDataList = [];
foreach ($tm->getList(['order_by' => 'q.id DESC', 'limit' => $pagerResult->getLimit()]) as $task) {
	$leftTimeData = $task->_progress->getLeftTimeData();
	$task->_progress = [
		'left_time_data' => [
			'hms' => $leftTimeData ? $leftTimeData->hms : '-:-:-',
		],
	];
	$task->_logs = array_map(fn($l)=>['id'=>$l->id, 'type'=>false, 'level'=>1, 'message'=>$l->text], $task->_logs);
	$tasksDataList[] = $task;
}

$data = [
	'pagesList' => $pagesDataList,
	'tasksList' => $tasksDataList,
];

echo json_encode($data);
