<?
namespace S5\Web\AntiSpam;
use S5\Web\AntiSpam\Items\IItem;


class AntiSpam {
	/** @var array|false */
	protected $skipRequestUris;
	protected bool   $isDebug;
	protected string $debugEmailTo;
	protected string $debugEmailFrom;

	protected array $itemsList = [];


	public function __construct (array $params = []) {
		$params += [
			'skipRequestUris' => false, //Какие ссылки пропускать при валидации
			'isDebug'         => false,
			'debugEmailTo'    => '',
			'debugEmailFrom'  => '',
			'itemsList'       => [],
		];
		foreach ($params as $k => $v) {
			$this->{$k} = $v;
		}
	}



	public function addItem (IItem $item) {
		$this->itemsList[] = $item;
	}



	public function showHtml () {
		foreach ($this->itemsList as $item) {
			$item->showHtml();
		}
	}



	public function checkForm (): CheckResult {
		if ($this->skipRequestUris) {
			foreach ($this->skipRequestUris as $uri) {
				if (strpos($_SERVER['REQUEST_URI'], $uri) === 0) {
					return new CheckResult();
				}
			}
		}

		if (!$this->itemsList) {
			throw new \InvalidArgumentException("Список \$this->itemsList пуст. Должен содержать хотя бы один объект для проверки форм.");
		}

		foreach ($this->itemsList as $item) {
			$result = $item->checkForm();
			if (!$result->isOk()) {
				break;
			}
		}
		if ($this->isDebug and $this->debugEmailTo) {
			$className  = $result->isOk()       ? '' : get_class($item)."\n";
			$mailParams = $this->debugEmailFrom ? 'From: '.$this->debugEmailFrom : '';
			mail(
				$this->debugEmailTo,
				'S5 AntiSpam debug',
				(
					'IP :'.$_SERVER['REMOTE_ADDR']."\n".
					'Host: '.$_SERVER['REMOTE_HOST']."\n".
					($result->isOk() ? 'Human' : 'Bot')."\n".
					$className.
					$_SERVER['REQUEST_URI']."\n".
					print_r($_REQUEST,true)
				),
				$mailParams
			);
		}

		return $result;
	}
}
