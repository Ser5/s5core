<?
namespace S5\Web\AntiSpam\Items;
use S5\Web\AntiSpam\CheckResult;


class BitrixCaptcha extends BaseItem {
	protected string    $htmlTemplate;
	protected bool      $isCaptchaInitialized = false;
	protected \CCaptcha $captcha;
	protected string    $safeCode;

	public function __construct (array $params = []) {
		parent::__construct();
		$this->htmlTemplate = $params['htmlTemplate'] ?? '';
	}



	/**
	 * Инициализация капчи для отображения на форме
	 * 
	 * Этот класс на первом запросе используется для отображения капчи на форме,
	 * а на втором запросе - для проверки капчи.
	 * На втором запросе, понятное дело, лишняя инициализация не нужна,
	 * поэтому она вынесена сюда, вызывается из showHtml().
	 * 
	 * Один объект - одна капча. Поэтому сколько бы раз showHtml() не вызывался, капча будет одна и та же,
	 * для одного объекта initCaptchaForForm() вызывается только один раз.
	 */
	protected function initCaptchaForForm () {
		if ($this->isCaptchaInitialized) {
			return;
		}
		if (!isset($this->htmlTemplate)) {
			$this->htmlTemplate =
<<<HEREDOC
<div>Капча</div>
<div>\$image</div>
<div>\$input</div>
HEREDOC;
		}
		require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/general/captcha.php';
		$this->captcha   = new \CCaptcha();
		$captchaPassword = \COption::GetOptionString('main', 'captcha_password', '');
		if (strlen($captchaPassword) <= 0) {
			$captchaPassword = randString(10);
			\COption::SetOptionString('main', 'captcha_password', $captchaPassword);
		}
		$this->captcha->SetCodeCrypt($captchaPassword);
		$this->safeCode = $this->getSafeCodeCrypt();
	}




	public function showHtml () {
		$this->initCaptchaForForm();
		$imageHtml =
			'<input type="hidden" name="captcha_sid" value="'.$this->safeCode.'" />'.
			'<img class="bitrixCaptcha_image" src="/bitrix/tools/captcha.php?captcha_sid='.$this->safeCode.'" alt="CAPTCHA" />'
		;
		$inputHtml = '<input class="bitrixCaptcha_word" type="text" name="captcha_word">';
		$html      = str_replace(array('$image','$input'), array($imageHtml,$inputHtml), $this->htmlTemplate);
		echo $html;
	}



	public function checkForm (): CheckResult {
		global $APPLICATION;
		$message = $APPLICATION->CaptchaCheckCode($_POST["captcha_word"], $_POST["captcha_sid"])
			? false
			: 'Капча введена неверно';
		return new CheckResult($message);
	}



	public function getCodeCrypt (): string {
		return $this->captcha->GetCodeCrypt();
	}

	public function getSafeCodeCrypt (): string {
		return htmlspecialchars($this->captcha->GetCodeCrypt());
	}
}
