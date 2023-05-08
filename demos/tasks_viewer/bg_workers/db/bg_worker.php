<?
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/db_manager.php';
$tm = $DIC['tasksManager'];



initStorage();
$tm->run();
