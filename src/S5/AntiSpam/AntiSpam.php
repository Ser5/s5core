<?
namespace S5\Web\AntiSpam;

class AntiSpam {
	protected $params;
	protected $itemsList = [];

	public function __construct ($params = []) {
		$this->params = $params + [
			'skip_request_uris' => false,
			'is_debug'          => false,
			'debug_email_to'    => false,
			'debug_email_from'  => false,
		];
	}



	public function addItem (Items\IItem $item) {
		$this->itemsList[] = $item;
	}



	public function showHtml () {
		foreach ($this->itemsList as $item) {
			$item->showHtml();
		}
	}



	public function checkForm () {
		if ($this->params['skip_request_uris']) {
			foreach ($this->params['skip_request_uris'] as $uri) {
				if (strpos($_SERVER['REQUEST_URI'], $uri) === 0) {
					return new CheckResult();
				}
			}
		}
		foreach ($this->itemsList as $item) {
			$result = $item->checkForm();
			if (!$result->isOk()) {
				break;
			}
		}
		if ($this->params['is_debug'] and $this->params['debug_email_to']) {
			$className  = $result->isOk() ? '' : get_class($item)."\n";
			$mailParams = $this->params['debug_email_from'] ? 'From: '.$this->params['debug_email_from'] : '';
			$r = mail(
				$this->params['debug_email_to'],
				'S5_Web_AntiSpam debug',
				(
					'IP :'.$_SERVER['REMOTE_ADDR']."\n".
					'Host: '.$_SERVER['REMOTE_HOST']."\n".
					($result->isOk() ? 'Human' : 'Bot')."\n".
					$className.
					$_SERVER['REQUEST_URI']."\n".
					print_r($_REQUEST,1)
				),
				$mailParams
			);
		}
		return $result;
	}
}
