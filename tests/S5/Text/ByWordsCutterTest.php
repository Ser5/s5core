<?
namespace S5\Text;

class ByWordsCutterTest extends \S5\TestCase {
	public function testCut () {
		$initialText = "Это довольно длинная строка, содержащая приличное количество слов. Должна подойти для тестов.";
		$result = ByWordsCutter::cut($initialText, 70, '');
		$this->assertEquals("Это довольно длинная строка, содержащая приличное количество слов.", $result);
		
		$initialText = "Раз два";
		$result = ByWordsCutter::cut($initialText, 10);
		$this->assertEquals("Раз два", $result);
		
		$result = ByWordsCutter::cut($initialText, 7);
		$this->assertEquals("Раз два", $result);
		
		$initialText = "Раз два три четыре пять";
		$this->assertEquals("Раз два", ByWordsCutter::cut($initialText, 8, ''));
		$this->assertEquals("Раз два", ByWordsCutter::cut($initialText, 7, ''));
		$this->assertEquals("Раз",     ByWordsCutter::cut($initialText, 6, ''));
		
		$initialText = "Раз два";
		$this->assertEquals("Раз два", ByWordsCutter::cut($initialText, 8, '...'));
		$this->assertEquals("Раз два", ByWordsCutter::cut($initialText, 7, '...'));
		$this->assertEquals("Раз...",  ByWordsCutter::cut($initialText, 6, '...'));
	}
}

