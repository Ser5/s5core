<?php
require_once 'S5/ParamsChecker.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Некоторые тесты для класса S5_ParamsChecker
 * 
 * Проверяются:
 * - типы: int, uint, float, bool_int, string.
 * - минимальные/максимальные значения для int.
 * - то, что правильные значения проходят проверку: для int, uint, float, string.
 * - то, что int и float с правильной длиной проходят проверку на длину.
 */
class S5_ParamsCheckerTest extends \PHPUnit\Framework\TestCase {
	public function testTypeChecking () {
		$check_list = array(
			array(
				'value' => -3,
				'allowed' => array('int'=>1, 'float'=>1)
			),
			array(
				'value' => 3,
				'allowed' => array('uint'=>1, 'int'=>1, 'float'=>1)
			),
			array(
				'value' => 0.5,
				'allowed' => array('float'=>1)
			),
			array(
				'value' => 0,
				'allowed' => array('bool_int'=>1, 'uint'=>1, 'int'=>1, 'float'=>1)
			),
			array(
				'value' => 1,
				'allowed' => array('bool_int'=>1, 'uint'=>1, 'int'=>1, 'float'=>1)
			),
			array(
				'value' => 'string',
				'allowed' => array('string'=>1)
			)
		);
		foreach ($check_list as $check_item) {
			static $check_types = array('string', 'int', 'uint', 'float', 'bool_int');
			foreach ($check_types as $type) {
				try {
					S5_ParamsChecker::check($check_item['value'], array('type' => $type));
				} catch (S5_ParamsChecker_CheckFailedException $e) {
					//Проверка на правомерность брошенного исключения.
					if (isset($check_item['allowed'][$type])) {
						$this->fail("Для значения $check_item[value] тип $type является допустимым. Но было выкинуто исключение. Вот дерьмо.");
					} else {
						continue;
					}
				}
				//Исключения не было. Так и должно быть?
				if (!isset($check_item['allowed'][$type])) {
					$this->fail("Для значения $check_item[value] тип $type недопустим. Но исключения не было.");
				}
			}
		}
	}
	
	public function testMinMaxChecking () {
		$check_list = array(
			array(
				'type' => 'int',
				'value' => 5,
				'>=' => array(3,4,5), '>' => array(3,4), '<' => array(6,7), '<=' => array(5,6)
			)
		);
		//Проверки диапазонов.
		foreach ($check_list as $check_item) {
			for ($min = 3; $min <=7; $min++) {
				for ($max = $min; $max <= 7; $max++) {
					//Проверки включительно.
					try {
						S5_ParamsChecker::check(
							$check_item['value'],
							array('type'=>$check_item['type'], '>='=>$min, '<='=>$max)
						);
					} catch (S5_ParamsChecker_CheckFailedException $e) {
						//Правомерно ли исключение?
						if (in_array($min, $check_item['>=']) && in_array($max, $check_item['<='])) {
							$this->fail("Значению $check_item[value] можно находиться в диапазоне (>=$min; <=$max). Однако, возникло исключение.");
						}
						//Проверка сообщения об ошибке.
						$expected = "в диапазоне (>=$min; <=$max)";
						if (!strpos($e->getMessage(), $expected)) {
							$this->fail("Ожидаемая часть сообщения: $expected Получено: {$e->getMessage()}");
						}
					}
					//Проверки исключительно.
					try {
						S5_ParamsChecker::check(
							$check_item['value'],
							array('type'=>$check_item['type'], '>'=>$min, '<'=>$max)
						);
					} catch (S5_ParamsChecker_CheckFailedException $e) {
						//Правомерно ли исключение?
						if (in_array($min, $check_item['>']) && in_array($max, $check_item['<'])) {
							$this->fail("Значению $check_item[value] можно находиться в диапазоне (>$min; <$max). Однако, возникло исключение.");
						}
						//Проверка сообщения об ошибке.
						$expected = "в диапазоне (>$min; <$max)";
						if (!strpos($e->getMessage(), $expected)) {
							$this->fail("Ожидаемая часть сообщения: $expected Получено: {$e->getMessage()}");
						}
					}
				}
			}
		}
		//Проверки с одной указанной границей.
		foreach ($check_list as $check_item) {
			//С нижней.
			for ($min = 3; $min <=7; $min++) {
				//Проверки "больше или равно".
				try {
					S5_ParamsChecker::check($check_item['value'], array('type'=>$check_item['type'], '>='=>$min));
				} catch (S5_ParamsChecker_CheckFailedException $e) {
					//Правомерно ли исключение?
					if (in_array($min, $check_item['>='])) {
						$this->fail("Значение $check_item[value] может быть больше или равно $min. Однако, возникло исключение.");
					}
					//Проверка сообщения об ошибке.
					$expected = "больше или равно $min";
					if (!strpos($e->getMessage(), $expected)) {
						$this->fail("Ожидаемая часть сообщения: $expected Получено: {$e->getMessage()}");
					}
				}
				//Проверки "больше".
				try {
					S5_ParamsChecker::check($check_item['value'], array('type'=>$check_item['type'], '>'=>$min));
				} catch (S5_ParamsChecker_CheckFailedException $e) {
					//Правомерно ли исключение?
					if (in_array($min, $check_item['>'])) {
						$this->fail("Значение $check_item[value] может быть больше $min. Однако, возникло исключение.");
					}
					//Проверка сообщения об ошибке.
					$expected = "больше $min";
					if (!strpos($e->getMessage(), $expected)) {
						$this->fail("Ожидаемая часть сообщения: $expected Получено: {$e->getMessage()}");
					}
				}
			}
			//С верхней.
			for ($max = 3; $max <=7; $max++) {
				//Проверки "меньше или равно".
				try {
					S5_ParamsChecker::check($check_item['value'], array('type'=>$check_item['type'], '<='=>$max));
				} catch (S5_ParamsChecker_CheckFailedException $e) {
					//Правомерно ли исключение?
					if (in_array($max, $check_item['<='])) {
						$this->fail("Значение $check_item[value] может быть меньше или равно $max. Однако, возникло исключение.");
					}
					//Проверка сообщения об ошибке.
					$expected = "меньше или равно $max";
					if (!strpos($e->getMessage(), $expected)) {
						$this->fail("Ожидаемая часть сообщения: $expected Получено: {$e->getMessage()}");
					}
				}
				//Проверки "больше".
				try {
					S5_ParamsChecker::check($check_item['value'], array('type'=>$check_item['type'], '<'=>$max));
				} catch (S5_ParamsChecker_CheckFailedException $e) {
					//Правомерно ли исключение?
					if (in_array($max, $check_item['<'])) {
						$this->fail("Значение $check_item[value] может быть меньше $max. Однако, возникло исключение.");
					}
					//Проверка сообщения об ошибке.
					$expected = "меньше $max";
					if (!strpos($e->getMessage(), $expected)) {
						$this->fail("Ожидаемая часть сообщения: $expected Получено: {$e->getMessage()}");
					}
				}
			}
			
		}
	}
	
	public function testGoodIntValues () {
		$value = 10;
		S5_ParamsChecker::check($value, array('type' => 'int', '>'  => 5,   '<'  => 15));
		S5_ParamsChecker::check($value, array('type' => 'int', '>=' => 5,   '<=' => 15));
		S5_ParamsChecker::check($value, array('type' => 'int', '>=' => 10,  '<'  => 15));
		S5_ParamsChecker::check($value, array('type' => 'int', '>'  => 5,   '<=' => 10));
		$value = 0;
		S5_ParamsChecker::check($value, array('type' => 'int', '>=' => -10, '<=' => 10));
		$value = -3;
		S5_ParamsChecker::check($value, array('type' => 'int', '>=' => -10, '<=' => 10));
	}

	public function testGoodUintValues () {
		$value = 10;
		S5_ParamsChecker::check($value, array('type' => 'uint', '>'  => 5,   '<'  => 15));
		S5_ParamsChecker::check($value, array('type' => 'uint', '>=' => 5,   '<=' => 15));
		S5_ParamsChecker::check($value, array('type' => 'uint', '>=' => 10,  '<'  => 15));
		S5_ParamsChecker::check($value, array('type' => 'uint', '>'  => 5,   '<=' => 10));
		$value = 0;
		S5_ParamsChecker::check($value, array('type' => 'uint', '>=' => 0,   '<=' => 10));
	}

	public function testGoodFloatValues () {
		$value = 10;
		S5_ParamsChecker::check($value, array('type' => 'float', '>'  => 5,   '<'  => 15));
		S5_ParamsChecker::check($value, array('type' => 'float', '>=' => 5,   '<=' => 15));
		S5_ParamsChecker::check($value, array('type' => 'float', '>=' => 10,  '<'  => 15));
		S5_ParamsChecker::check($value, array('type' => 'float', '>'  => 5,   '<=' => 10));
		$value = 0;
		S5_ParamsChecker::check($value, array('type' => 'float', '>=' => 0,   '<=' => 10));
	}

	public function testGoodNumericLengths () {
		$value = 100;
		S5_ParamsChecker::check($value, array('type' => 'int', 'length>'  => 2, 'length<'   => 5));
		S5_ParamsChecker::check($value, array('type' => 'int', 'length>=' => 3, 'length<='  => 4));
		S5_ParamsChecker::check($value, array('type' => 'int', 'length>=' => 2, 'length<='  => 3));
		$value = 100.5;
		S5_ParamsChecker::check($value, array('type' => 'float', 'length>'  => 4, 'length<'   => 7));
		S5_ParamsChecker::check($value, array('type' => 'float', 'length>=' => 5, 'length<='  => 6));
		S5_ParamsChecker::check($value, array('type' => 'float', 'length>=' => 4, 'length<='  => 5));
	}
	
	public function testGoodStringValues () {
		//Проверки на длину
		$value = 'string';
		S5_ParamsChecker::check($value, array('type' => 'string', 'length>'  => 5, 'length<'   => 7));
		S5_ParamsChecker::check($value, array('type' => 'string', 'length>=' => 6, 'length<='  => 10));
		S5_ParamsChecker::check($value, array('type' => 'string', 'length>=' => 5, 'length<='  => 6));
		//Проверки на шаблоны.
		$value = 'test@mail.ru';
		S5_ParamsChecker::check($value, array('type' => 'string', 'template' => 'mail'));
		$value = '2000-01-01';
		S5_ParamsChecker::check($value, array('type' => 'string', 'template' => 'date'));
		$value = '2000-01-01';
		S5_ParamsChecker::check($value, array('type' => 'string', 'template' => 'datetime'));
		$value = '2000-01-01 00:00';
		S5_ParamsChecker::check($value, array('type' => 'string', 'template' => 'datetime'));
		$value = '2000-01-01 00:00:00';
		S5_ParamsChecker::check($value, array('type' => 'string', 'template' => 'datetime'));
	}

	public function testTypeErrorText () {
		$value = 'string';
		try {
			S5_ParamsChecker::check($value, array('type'=>'int'), '$value', 'Значение');
		} catch (S5_ParamsChecker_CheckFailedException $e) {
			$expected =
				"Значение (\$value)\n".
				"Ожидаемый тип: целое число.\n".
				"Полученное значение: $value";
			$got = $e->getMessage();
			$this->assertEquals($expected, $got);
			return;
		}
		$this->fail('Ожидалась ошибка.');
	}
	
	public function testMinMaxAndLengthErrorText () {
		$value = 5000;
		try {
			$params = array(
				'type' => 'int',
				'>=' => 10, '<=' => 900,
				'length>=' => 2, 'length<=' => 3
			);
			S5_ParamsChecker::check($value, $params, '$value', 'Значение');
		} catch (S5_ParamsChecker_CheckFailedException $e) {
			$expected =
				"Значение (\$value)\n".
				"Ожидаемый минимум/максимум: в диапазоне (>=10; <=900).\n".
				"Ожидаемая длина: в диапазоне (>=2; <=3).\n".
				"Полученное значение: $value";
			$got = $e->getMessage();
			$this->assertEquals($expected, $got);
			return;
		}
		$this->fail('Ожидалась ошибка.');
	}

	public function testPresenceChecking () {
		$paramsHash = array(
			'key1' => 1,
			'key2' => 2,
			'key3' => 3,
		);
		$requiredParamsList = array('key1', 'key2', 'key3');
		S5_ParamsChecker::checkPresence($paramsHash, $requiredParamsList);
		foreach (array_reverse(array_keys($paramsHash)) as $key) {
			unset($paramsHash[$key]);
			try {
				S5_ParamsChecker::checkPresence($paramsHash, $requiredParamsList);
			} catch (S5_ParamsChecker_CheckFailedException $e) {
				$this->assertRegExp("/$key/", $e->getMessage());
				continue;
			}
			$this->fail();
		}
	}
}
