<?
namespace S5\Web\AntiSpam\Items;

class Hidden implements IItem {
	protected array  $params;
	protected string $jsFieldId;
	protected string $fftFieldId;

	public function __construct (array $params = []) {
		$this->params = $params + [
			'empty_field_name'              => 'last_name',
			'js_field_name'                 => 'qwe',
			'js_field_value'                => 'asd',
			'form_filling_time_field_name'  => 'fft',
			'form_filling_time_field_value' => 0,
			'min_form_filling_time'         => 5,
			'ids_prefix'                    => 's5as',
		];
		$this->jsFieldId  = $this->params['ids_prefix'].'_'.$this->params['js_field_name'];
		$this->fftFieldId = $this->params['ids_prefix'].'_'.$this->params['form_filling_time_field_name'];
	}



	public function showHtml () {?>
<input type="text" value="" name="<?=$this->params['empty_field_name']?>" style="display:none;" autocomplete="off">
<input id="<?=$this->jsFieldId?>" type="hidden" name="<?=$this->params['js_field_name']?>" value="">
<input id="<?=$this->fftFieldId?>" type="hidden" name="<?=$this->params['form_filling_time_field_name']?>" value="<?=$this->params['form_filling_time_field_value']?>">
<script>$(document).ready(function () {
	$('#<?=$this->jsFieldId?>').val('<?=$this->params['js_field_value']?>');
	var isFocused = false;
	$('#<?=$this->jsFieldId?>').closest('form').find('input').focus(function () {
		if (!isFocused) {
			isFocused = true;
			var fftField = $('#<?=$this->fftFieldId?>');
			setInterval(
				function () {
					fftField.val(fftField.val()*1 + 1);
				},
				1000
			);
		}
	});
});</script>
	<?}



	public function checkForm () {
		$p = $this->params;
		if (
			@$_REQUEST[$p['empty_field_name']]             === ''    and
			@$_REQUEST[$p['js_field_name']]                === 'asd' and
			@$_REQUEST[$p['form_filling_time_field_name']] >=  $p['form_filling_time_field_value'] + $p['min_form_filling_time']
		) {
			$return = new \S5\Web\AntiSpam\CheckResult();
		} else {
			$return = new \S5\Web\AntiSpam\CheckResult("Неизвестная ошибка");
		}
		return $return;
	}
}
