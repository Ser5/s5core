<?
require_once __DIR__.'/../../../src/S5/Validator/RulesParser.php';
require_once __DIR__.'/../../../src/S5/Validator/RulesException.php';
require_once __DIR__.'/TestValidator.php';




$validator = new \S5\Validator\TestValidator();

$parser = new \S5\Validator\RulesParser();
$parser->enableCache(false);



$rulesString = str_repeat('(empty | ((email | phone) & length(3,32))) | ', 20);
$rulesString = substr($rulesString, 0, -3);
echo "Rules string length: ", strlen($rulesString), "\n";

$t1 = microtime(true);
for ($a = 0; $a < 10000; $a++) {
	$parser->parse($rulesString);
}
$t2 = microtime(true);
echo $t2 - $t1, "\n";
//3.0756449699402
//2 900 000 символов в секунду.
