<?
namespace S5\Web\AntiSpam\Items;
use S5\Web\AntiSpam\CheckResult;


class Hidden extends BaseItem {
	protected string $rootId;
	protected int    $minFillTime;


	public function __construct (array $params = []) {
		parent::__construct();

		$this->rootId      = $params['rootId']      ?? 's5as'.static::$itemNumber;
		$this->minFillTime = $params['minFillTime'] ?? 2;
	}



	public function showHtml () {?>
<div id="<?=$this->rootId?>">
<input type="text"   name="last_name" value="" style="display:none;" autocomplete="off"><?//Должно остаться пустым?>
<input type="hidden" name="qwe"       value=""><?//Клиент должен поддерживать JS - value будет заполнено нижележащим кодом?>
<input type="hidden" name="fft"       value="0"><?//Сколько секунд заполнялась форма?>
<script>$(document).ready(function () {
	let root = document.querySelector('#<?=$this->rootId?>');
	let form = root.closest('form');
	root.querySelectorAll('[name=qwe]').value = 'asd';
	let fftField = root.querySelector('[name=fft]');
	let focusHandler = function () {
		setInterval(
			function () {
				fftField.val(fftField.val()*1 + 1);
			},
			1000
		);
		form.removeEventListener('focus', focusHandler);
	};
	form.addEventListener('focus', focusHandler);<?//Начинаем считать время заполнения после первого клика на любое поле формы?>
</script>
</div>
	<?}



	public function checkForm (): CheckResult {
		if (
			@$_REQUEST['last_name'] === ''    and
			@$_REQUEST['qwe']       === 'asd' and
			@$_REQUEST['fft']       >=  $this->minFillTime
		) {
			$return = new CheckResult();
		} else {
			$return = new CheckResult("Неизвестная ошибка");
		}
		return $return;
	}
}
