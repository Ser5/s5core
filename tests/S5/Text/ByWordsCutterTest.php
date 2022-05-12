<?php
require_once 'S5/Text/ByWordsCutter.php';
require_once 'PHPUnit/Framework/TestCase.php';

class S5_Text_ByWordsCutterTest extends \PHPUnit\Framework\TestCase {
	public function testCut() {
		$initial_text = "Это довольно длинная строка, содержащая приличное количество слов. Должна подойти для тестов.";
		$result = S5_Text_ByWordsCutter::cut($initial_text, 70);
		$this->assertEquals("Это довольно длинная строка, содержащая приличное количество слов.", $result);
		
		$initial_text = "Раз два";
		$result = S5_Text_ByWordsCutter::cut($initial_text, 10);
		$this->assertEquals("Раз два", $result);
		
		$result = S5_Text_ByWordsCutter::cut($initial_text, 7);
		$this->assertEquals("Раз два", $result);
		
		$initial_text = "Раз два три четыре пять";
		$this->assertEquals("Раз два", S5_Text_ByWordsCutter::cut($initial_text, 8));
		$this->assertEquals("Раз два", S5_Text_ByWordsCutter::cut($initial_text, 7));
		$this->assertEquals("Раз", S5_Text_ByWordsCutter::cut($initial_text, 6));
		
		$initial_text = "Раз два";
		$this->assertEquals("Раз два", S5_Text_ByWordsCutter::cut($initial_text, 8, '...'));
		$this->assertEquals("Раз два", S5_Text_ByWordsCutter::cut($initial_text, 7, '...'));
		$this->assertEquals("Раз...", S5_Text_ByWordsCutter::cut($initial_text, 6, '...'));
	}
}

