<?php

require_once 'S5/Text/WordEnding.php';

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * S5_Text_WordEnding test case.
 */
class S5_Text_WordEndingTest extends \PHPUnit\Framework\TestCase {
	private function concat ($number, $men) {
		$we = S5_Text_WordEnding::getWord($number, $men[0], $men[1], $men[2]);
		$string = "На горе стоит $number $we";
		return $string;
	}
	
	public function test1 () {
		$men = array('мужик', 'мужика', 'мужиков');
		
		//Проверка чисел до сотни.
		$this->assertEquals(3, S5_Text_WordEnding::get(0));
		$this->assertEquals(1, S5_Text_WordEnding::get(1));
		for ($a = 2; $a <= 4; $a++) {
			$this->assertEquals(2, S5_Text_WordEnding::get($a));
		}
		for ($a = 5; $a <= 20; $a++) {
			$this->assertEquals(3, S5_Text_WordEnding::get($a));
		}
		
		//Проверка чисел после сотни.
		for ($a = 110; $a <= 120; $a++) $this->assertEquals(3, S5_Text_WordEnding::get($a));
		for ($a = 310; $a <= 320; $a++) $this->assertEquals(3, S5_Text_WordEnding::get($a));
		for ($a = 1510; $a <= 1520; $a++) $this->assertEquals(3, S5_Text_WordEnding::get($a));
		
		//Отрицательные числа.
		$this->assertEquals(1, S5_Text_WordEnding::get(-1));
		for ($a = -2; $a <= -4; $a--) {
			$this->assertEquals(2, S5_Text_WordEnding::get($a));
		}
		for ($a = -5; $a <= -20; $a--) {
			$this->assertEquals(3, S5_Text_WordEnding::get($a));
		}
		
		//getWord()
		$this->assertEquals('На горе стоит 21 мужик', $this->concat(21, $men));
		for ($a = 22; $a <= 24; $a++) {
			$this->assertEquals("На горе стоит $a мужика", $this->concat($a, $men));
		}
		for ($a = 25; $a <= 30; $a++) {
			$this->assertEquals("На горе стоит $a мужиков", $this->concat($a, $men));
		}
	}
}

