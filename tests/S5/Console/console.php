<?
require_once __DIR__.'/../../phpunit_bootstrap.php';



class Api {
	public function run ($params = []) {
		$p = $params + [
			'value' => false,
			'flag'  => false,
		];

		$outputString = "run result ";
		if ($p['value']) {
			if (!is_array($p['value'])) {
				$p['value'] = [$p['value']];
			}
			$outputString .= join(' ', $p['value']) . ' ';
		}
		if ($p['flag']) {
			$outputString .= 'yes ';
		}
		$outputString .= "\n";

		echo $outputString;
	}
}



$api = new Api();



$commandsDataList = [
	[
		'name'        => 'test:run',
		'description' => 'Run description',
		'callback'    => [$api, 'run'],
		'options' => [
			['label' => 'Value description', 'code' => 'value', 'flags' => ['is_array','required']],
			['label' => 'Flag description',  'code' => 'flag',  'flags' => 'none'],
		],
	],
];



$app = new \S5\Console\Application('App', '1.0.0', $commandsDataList);
$app->run();
