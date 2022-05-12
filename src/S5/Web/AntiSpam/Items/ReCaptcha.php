<?
namespace S5\Web\AntiSpam\Items;
use S5\Web\AntiSpam\CheckResult;


class ReCaptcha extends BaseItem {
	protected string $privateKey;
	protected string $publicKey;

	public function __construct (array $params) {
		parent::__construct();

		foreach (['privateKey', 'publicKey'] as $k) {
			if (!isset($params[$k])) {
				throw new \InvalidArgumentException("Missing param: [$k]");
			}
			$this->$k = $params[$k];
		}
	}



	public function showHtml () {
		echo recaptcha_get_html($this->publicKey);
	}



	public function checkForm (): CheckResult {
		$response = recaptcha_check_answer(
			$this->privateKey,
			$_SERVER["REMOTE_ADDR"],
			$_REQUEST["recaptcha_challenge_field"],
			$_REQUEST["recaptcha_response_field"]
		);
		if ($response->is_valid) {
			$return = new CheckResult();
		} else {
			$return = new CheckResult("Капча введена неверно");
		}
		return $return;
	}
}
