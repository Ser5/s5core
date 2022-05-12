<?
namespace S5\Web\AntiSpam;
use S5\Web\AntiSpam\Items\Hidden;
use S5\Web\AntiSpam\Items\ReCaptcha;

require_once 'ReCaptchaMock.php';


class AntiSpamTest extends \S5\TestCase {
	public function testHidden () {
		//ID по умолчанию
		list($as, $html) = $this->_getAntiSpam([new Hidden()]);
		$this->assertStringStartsWith('<div id="s5as1">', $html);
		$this->assertStringEndsWith('</div>', $html);

		//Свой ID
		list($as, $html) = $this->_getAntiSpam([new Hidden(['rootId' => 'custom'])]);
		$this->assertStringStartsWith('<div id="custom">', $html);

		//Неправильное заполнение
		$_REQUEST = [];
		$r = $as->checkForm();
		$this->assertFalse($r->isOK());
		$this->assertNotEquals('', $r->getErrorMessage());

		$_REQUEST['last_name'] = 'Vasya';
		$this->assertFalse($as->checkForm()->isOK());

		$_REQUEST['qwe'] = '';
		$this->assertFalse($as->checkForm()->isOK());

		$_REQUEST['fft'] = 1;
		$this->assertFalse($as->checkForm()->isOK());

		//Правильное заполнение
		$_REQUEST = ['last_name' => '', 'qwe' => 'asd', 'fft' => 10];
		$r = $as->checkForm();
		$this->assertTrue($r->isOK());
		$this->assertEquals('', $r->getErrorMessage());
	}



	public function testReCaptcha () {
		$rc = $this->_setupReCaptcha();

		list($as, $html) = $this->_getAntiSpam([$rc]);
		$this->assertEquals('<div id="recaptcha"></div>', $html);

		$r = $as->checkForm();
		$this->assertTrue($r->isOK());
		$this->assertEquals('', $r->getErrorMessage());
	}



	public function testMultipleItems () {
		$rc = $this->_setupReCaptcha();

		list($as, $html) = $this->_getAntiSpam([
			new Hidden(['rootId' => 'hidden']),
			$rc
		]);

		$this->assertStringContainsString('id="hidden"',    $html);
		$this->assertStringContainsString('id="recaptcha"', $html);

		//Надо проверить, что если хотя бы один проверяльщик даёт отрицательный ответ,
		//то антиспам тоже выдаёт отрицательный результат.
		//Положительный результат выдаётся только при положительном ответе всех проверяльщиков.
		//Проверяем, что будет, если отрицательный ответ даст hidden.
		$_REQUEST['last_name'] = 'Vasya';
		$r = $as->checkForm();
		$this->assertFalse($r->isOK());
		$this->assertNotEquals('', $r->getErrorMessage());
		$_REQUEST['last_name'] = '';

		//Если отрицательный ответ будет от рекапчи
		$_REQUEST['recaptcha_response_field'] = 'unresponsive';
		$r = $as->checkForm();
		$this->assertFalse($r->isOK());
		$this->assertNotEquals('', $r->getErrorMessage());
		$_REQUEST['recaptcha_response_field'] = 'challenge';

		//Правильное заполнение
		$r = $as->checkForm();
		$this->assertTrue($r->isOK());
		$this->assertEquals('', $r->getErrorMessage());
	}



	private function _getAntiSpam (array $itemsList = []) {
		$as = new AntiSpam(['itemsList' => $itemsList]);
		ob_start();
		$as->showHtml();
		$html = trim(ob_get_clean());
		return [$as, $html];
	}



	private function _setupReCaptcha () {
		$privateKey = 'private';
		$publicKey  = 'public';

		//Тут надо только проверить, что функции
		//recaptcha_get_html() и recaptcha_check_answer()
		//вызываются с правильными параметрами.
		//Ниже идут ожидаемые правильные значения:
		$GLOBALS['rcPrivateKey']      = $privateKey;
		$GLOBALS['rcPublicKey']       = $publicKey;
		$GLOBALS['rcRemoteAddr']      = '127.0.0.1';
		$GLOBALS['rcChallengeString'] = 'challenge';

		//Здесь - значения, которые передаются в качестве аргументов тех функций
		$_SERVER["REMOTE_ADDR"]                = '127.0.0.1';
		$_REQUEST["recaptcha_challenge_field"] = 'challenge';
		$_REQUEST["recaptcha_response_field"]  = 'challenge';

		return new ReCaptcha(compact('privateKey', 'publicKey'));
	}
}
