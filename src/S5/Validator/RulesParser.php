<?
namespace S5\Validator;

const BLOCK_START = 0; //Блок
const BLOCK       = 1;
const NAME        = 2; //Название функции
const P_BRACKET   = 3; //Параметры функции - после открывающейся скобки
const P_PARAM     = 4; // - после параметра
const P_COMMA     = 5; // - после запятой
const DPARAM      = 6; //Числовой параметр
const SPARAM      = 7; //Строковой параметр
const POST_LOGIC  = 8; //После логического оператора
const POST_BLOCK  = 9; //После закрывающейся скобки блока
const POST_FN     = 10;//После функции



class RulesParser {
	private $_isEnableCache = true;
	private $_cache         = [];

	public function __construct () {}



	public function parse ($rulesString) {
		if ($this->_isEnableCache) {
			if (!isset($this->_cache[$rulesString])) {
				$this->_cache[$rulesString] = $this->_parse($rulesString);
			}
			$block = $this->_cache[$rulesString];
		} else {
			$block = $this->_parse($rulesString);
		}

		return $block;
	}



	public function run ($validator, $value, $rulesString) {
		$block = $this->parse($rulesString);
		return $this->_runBlock($validator, $value, $block);
	}



	private function _runBlock ($validator, $value, $block) {
		$result = true;
		$logic  = false;

		array_shift($block); //Убираем "b" - индикатор блока
		foreach ($block as $e) {
			if (is_array($e)) {
				if ($e[0] == 'f') {
					//Запуск функции
					$params = array_merge([$value], $e[2]);
					$r      = $validator->{$e[1]}(...$params);
				} else {
					//Запуск вложенного блока
					$r = $this->_runBlock($validator, $value, $e);
				}
			} else {
				$logic = $e;
			}
			if (!$logic) {
				//Блоки начинаются с функций, логических операторов на этот момент ещё нет
				$result = $r;
			} else {
				if     ( $r and $logic == '&') $result  = true;
				elseif (!$r and $logic == '&') {$result = false; break;}
				elseif ( $r and $logic == '|') {$result = true;  break;}
				elseif (!$r and $logic == '|') $result  = false;
			}
		}

		return $result;
	}



	private function _parse ($rulesString) {
		$rulesLength = strlen($rulesString);

		$state       = BLOCK_START;
		$blocksStack = [];
		$block       = ['b'];
		$fn          = ['f', '', []];
		$dparam      = '';
		$sparam      = '';

		$char    = '';
		$isError = false;

		$startBlock = function () use (&$state, &$blocksStack, &$block) {
			$blocksStack[] = $block;
			$block         = ['b'];
			$state         = BLOCK_START;
		};
		$finishBlock = function () use (&$state, &$blocksStack, &$block, &$fn, &$isError) {
			if ($blocksStack) {
				$blocksStack[count($blocksStack)-1][] = $block;
				$block = array_pop($blocksStack);
				$state = POST_BLOCK;
			} else {
				$isError = true;
			}
		};

		$startFunction = function () use (&$state, &$fn, &$char) {
			$fn    = ['f',$char,[]];
			$state = NAME;
		};
		$finishFunction = function ($logic = false) use (&$state, &$block, &$fn) {
			$block[] = $fn;
			if (!$logic) {
				$state = POST_FN;
			} else {
				$block[] = $logic;
				$state   = POST_LOGIC;
			}
		};

		$startDparam = function () use (&$state, &$dparam, &$char) {
			$dparam = $char;
			$state  = DPARAM;
		};
		$finishDparam = function ($newState) use (&$state, &$fn, &$dparam) {
			$fn[2][] = (int)$dparam;
			$state   = $newState;
		};

		$startSparam = function () use (&$state, &$sparam) {
			$sparam = '';
			$state  = SPARAM;
		};
		$finishSparam = function () use (&$state, &$fn, &$sparam) {
			$fn[2][] = $sparam;
			$state   = P_PARAM;
		};

		$addLogic = function ($logic) use (&$state, &$block) {
			$block[] = $logic;
			$state   = POST_LOGIC;
		};

		for ($index = 0; $index <= $rulesLength; $index++) {
			if ($index != $rulesLength) {
				$char    = $rulesString[$index];
				$charOrd = ord($char);
				if     ($charOrd == 32 or $charOrd == 9 or $charOrd == 10 or $charOrd == 13)         $type = 's'; //пробельный символ
				elseif (($charOrd >= 65 and $charOrd <= 90) or ($charOrd >= 97 and $charOrd <= 122)) $type = 'w'; //буква
				elseif ($charOrd >= 48 and $charOrd <= 57)                   $type = 'd'; //цифра
				elseif ($charOrd == 39 or $charOrd == 34)                    $type = 'q'; //кавычка
				elseif ($charOrd == 44 or $charOrd == 38 or $charOrd == 124) $type = 'l'; //логический оператор
				else   $type = false; //что-то другое
			} else {
				$char = '';
				$type = 'e'; //конец строки, выход
			}

			//$prevState = $state;
			$isError = false;
			switch ($state) {
				case BLOCK_START:
					if     ($type == 's') {}
					elseif ($type == 'e') {}
					elseif ($type == 'w') $startFunction();
					elseif ($char == '(') $startBlock();
					elseif ($char == ')') $finishBlock();
					else   $isError = true;
				break;
				case BLOCK:
					if     ($type == 's')                 {}
					elseif ($type == 'e')                 {}
					elseif ($type == 'w')                 $startFunction();
					elseif ($char == ',' or $char == '&') $addLogic('&');
					elseif ($char == '|')                 $addLogic('|');
					elseif ($char == '(')                 $startBlock();
					elseif ($char == ')')                 $finishBlock();
					else   $isError = true;
				break;
				case POST_BLOCK:
					if     ($type == 's')                 {}
					elseif ($type == 'e')                 {}
					elseif ($char == ',' or $char == '&') $addLogic('&');
					elseif ($char == '|')                 $addLogic('|');
					elseif ($char == '(')                 $startBlock();
					elseif ($char == ')')                 $finishBlock();
					else   $isError = true;
				break;
				case POST_LOGIC:
					if     ($type == 's') {}
					elseif ($type == 'w') $startFunction();
					elseif ($char == '(') $startBlock();
					else   $isError = true;
				break;
				case NAME:
					if     ($type == 'w' or $type == 'd') $fn[1] .= $char;
					elseif ($char == ')')                 {$block[] = $fn; $finishBlock();}
					elseif ($char == '(')                 $state = P_BRACKET;
					elseif ($type == 's')                 $finishFunction();
					elseif ($type == 'e')                 $block[] = $fn;
					elseif ($char == ',' or $char == '&') $finishFunction('&');
					elseif ($char == '|')                 $finishFunction('|');
					else   $isError = true;
				break;
				case P_BRACKET:
					if     ($type == 's') {}
					elseif ($type == 'd') $startDparam();
					elseif ($type == 'q') $startSparam();
					elseif ($char == ')') $finishFunction();
					else   $isError = true;
				break;
				case DPARAM:
					if     ($type == 'd') $dparam .= $char;
					elseif ($type == 's') $finishDparam(P_PARAM);
					elseif ($char == ',') $finishDparam(P_COMMA);
					elseif ($char == ')') {$finishDparam(BLOCK); $block[] = $fn;}
					else   $isError = true;
				break;
				case SPARAM:
					if     ($type != 'q') $sparam .= $char;
					elseif ($type == 'q') $finishSparam();
					else   $isError = true;
				break;
				case P_PARAM:
					if     ($type == 's') {}
					elseif ($char == ',') $state = P_COMMA;
					elseif ($char == ')') $finishFunction();
					else   $isError = true;
				break;
				case P_COMMA:
					if     ($type == 's') {}
					elseif ($type == 'd') $startDparam();
					elseif ($type == 'q') $startSparam();
					else   $isError = true;
				break;
				case POST_FN:
					if     ($type == 's')                 {}
					elseif ($type == 'e')                 {}
					elseif ($char == ',' or $char == '&') $addLogic('&');
					elseif ($char == '|')                 $addLogic('|');
					elseif ($char == ')')                 $finishBlock();
					else   $isError = true;
				break;
			}

			//echo "[$char] is [$type], $prevState => $state\n";

			if ($isError) {
				throw new RulesException("Unexpected [$char] at [$index]");
			}
		}

		return $block;
	}



	public function enableCache ($isEnable) {
		$this->_isEnableCache = (bool)$isEnable;
	}
}
