<?
require_once __DIR__.'/../../../../vendor/autoload.php';

$options = new \S5\Cli\LongOptions();

$optionsHash = [];
foreach (array('value1','value2','novalue1','novalue2','noarg1','noarg2') as $name) {
	$optionsHash[$name] = $options->get($name);
}

$optionsHash['defaultvalue1'] = $options->get('defaultvalue1', 10);
$optionsHash['defaultvalue2'] = $options->get('defaultvalue2', 20);

echo serialize($optionsHash);
