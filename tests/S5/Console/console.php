<?
require_once __DIR__.'/../../phpunit_bootstrap.php';



class Api {
	public function run ($params = []) {
		$p = $params + [
			'value' => false,
			'flag'  => false,
		];
		$output = '';
		if ($p['value']) {
			if (!is_array($p['value'])) {
				$p['value'] = [$p['value']];
			}
			$output = join(' ', $p['value']);
		}
		elseif ($p['flag']) {
			$output = 'yes';
		}
		$this->_showOutput($output);
	}

	public function boolNoDefault ($p) {
		$output = '';
		if (isset($p['value'])) {
			$output = ($p['value'] ? 'yes' : 'no');
		}
		$this->_showOutput($output);
	}

	public function boolWithDefault ($p) {
		$output = '';
		if (isset($p['value'])) {
			$output = ($p['value'] ? 'yes' : 'no');
		}
		$this->_showOutput($output);
	}

	private function _showOutput ($tail) {
		echo "run result $tail\n";
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
	[
		'name'        => 'test:bool:no-default',
		'description' => 'Description',
		'callback'    => [$api, 'boolNoDefault'],
		'options' => [
			['label' => 'Bool no default', 'code' => 'value', 'flags' => 'bool'],
		],
	],
	[
		'name'        => 'test:bool:with-default',
		'description' => 'Description',
		'callback'    => [$api, 'boolWithDefault'],
		'options' => [
			['label' => 'Bool with default', 'code' => 'value', 'flags' => 'bool', 'default' => false],
		],
	],
];



$app = new \S5\Console\Application('App', '1.0.0', $commandsDataList);
$app->run();
