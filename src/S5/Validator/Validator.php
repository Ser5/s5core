<?
namespace S5\Validator;

class Validator {
	private $_messagesHash;

	public function __construct ($params) {
		foreach ($params as $k => $v) {
			$this->{'_'.$k} = $v;
		}
	}



	public function empty ($v) {
		return $this->_getMessage(!$v, 'empty', $v);
	}

	public function filled ($v) {
		return $this->_getMessage($v, 'filled', $v);
	}



	public function int ($v) {
		$v = preg_replace('/^-/', '', $v);
		return $this->_getMessage(ctype_digit("$v"), 'int', $v);
	}

	public function pint ($v) {
		return $this->_getMessage(ctype_digit("$v"), 'pint', $v);
	}



	public function length ($v, $min = 0, $max = 0) {
		if (!$min and !$max) {
			return $this->filled($v);
		}

		$length = mb_strlen($v, 'UTF-8');
		if ($min and !$max) {
			$isValid     = ($length >= $min);
			$messageCode = 'min_length';
		} elseif (!$min and $max) {
			$isValid     = ($length <= $min);
			$messageCode = 'max_length';
		} else {
			$isValid     = ($length >= $min and $length <= $min);
			$messageCode = 'length';
		}

		return $this->_getMessage($isValid, $messageCode, $v);
	}



	public function minmax ($v, $min = false, $max = false) {
		if ($min === false and $max === false) {
			throw new \InvalidArgumentException("min and/or max expected");
		}

		if ($min !== false and $max === false) {
			$isValid     = ($v >= $min);
			$messageCode = 'min';
		} elseif ($min === false and $max !== false) {
			$isValid     = ($v <= $min);
			$messageCode = 'max';
		} else {
			$isValid     = ($v >= $min and $v <= $min);
			$messageCode = 'minmax';
		}

		return $this->_getMessage($isValid, $messageCode, $v);
	}



	public function email ($v) {
		return $this->_getMessage(filter_var($v, FILTER_VALIDATE_EMAIL), 'email', $v);
	}



	public function phone ($v) {
		$digitsString = preg_replace('/[^\d]+/', '', $v);
		return $this->_getMessage(strlen($digitsString)==11, 'phone', $v);
	}



	public function inn ($v) {
		$length = strlen($v);
		return $this->_getMessage((ctype_digit($v) and ($length == 10 or $length == 12)), 'inn', $v);
	}



	public function date ($v) {
		return $this->_getMessage(false, 'date', $v);
	}

	public function time ($v) {
		return $this->_getMessage(false, 'time', $v);
	}

	public function datetime ($v) {
		return $this->_getMessage(false, 'datetime', $v);
	}



	private function _getMessage ($isValid, $messageCode, $value) {
		return ($isValid ? '' : \MessageFormatter::formatMessage($messageCode, [$value]));
	}
}
