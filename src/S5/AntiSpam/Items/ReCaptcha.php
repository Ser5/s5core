<?
namespace S5\Web\AntiSpam\Items;

class ReCaptcha implements IItem {
	protected $params;

	/**
	 * Ctor.
	 *
	 * $params
	 * - private_key
	 * - public_key
	 */
	public function __construct ($params) {
		$this->params = $params;
	}



	public function showHtml () {
		echo recaptcha_get_html($this->params['public_key']);
	}



	public function checkForm () {
		$response = recaptcha_check_answer(
			$this->params['private_key'],
			$_SERVER["REMOTE_ADDR"],
			$_POST["recaptcha_challenge_field"],
			$_POST["recaptcha_response_field"]
		);
		if ($response->is_valid) {
			$return = new \S5\Web\AntiSpam\CheckResult();
		} else {
			$return = new \S5\Web\AntiSpam\CheckResult("Капча введена неверно");
		}
		return $return;
	}
}
