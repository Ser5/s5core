<?
namespace S5\Validation;



class ValidatorMethods {
	public function required (mixed $value): bool {
		$isOK = true;
		if (is_string($value) or is_numeric($value)) {
			$isOK = (strlen($value) > 0);
		} else {
			$isOK = (bool)$value;
		}
		return $isOK;
	}



	public function optional (mixed $value): bool {
		return ($value === '' or $value === null);
	}



	public function min_length (mixed $value, int $length): bool {
		return ($this->strlen($value) >= $length);
	}

	public function max_length (mixed $value, int $length): bool {
		return ($this->strlen($value) <= $length);
	}

	public function length_between (mixed $value, int $minLength, int $maxLength): bool {
		$strlen = $this->strlen($value);
		return ($strlen >= $minLength and $strlen <= $maxLength);
	}

	public function length (mixed $value, int $length): bool {
		return ($this->strlen($value) == $length);
	}



	public function min_value (mixed $value, int|float $min): bool {
		return ($value >= $min);
	}

	public function max_value (mixed $value, int|float $max): bool {
		return ($value <= $max);
	}

	public function value_between (mixed $value, int|float $min, int|float $max): bool {
		return ($value >= $min and $value <= $max);
	}



	public function int (mixed $value): bool {
		//filter_var(true, FILTER_VALIDATE_INT) возвращает 1, поэтому дополнительно проверяем тип
		return (
			(is_numeric($value) or is_string($value)) and
			filter_var($value, FILTER_VALIDATE_INT) !== false
		);
	}

	public function number (mixed $value): bool {
		return is_numeric($value);
	}

	public function string (mixed $value): bool {
		return is_string($value);
	}

	public function bool (mixed $value): bool {
		return is_bool($value);
	}

	public function object (mixed $value): bool {
		return is_object($value);
	}

	public function object_of (mixed $value, string $className): bool {
		return  (is_object($value) and is_a($value, $className));
	}



	public function email (mixed $value): bool {
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}

	public function url (mixed $value): bool {
		return filter_var($value, FILTER_VALIDATE_URL);
	}



	public function image (mixed $filePath, array $mimeTypesList = []): bool {
		$isImage  = false;
		$mimeType = '';

		do {
			//Строка?
			if (!is_string($filePath)) {
				break;
			}
			//Доступный файл?
			if (!is_file($filePath) or !is_readable($filePath)) {
				break;
			}
			//Картинка?
			$mimeType = mime_content_type($filePath);
			if (!str_starts_with($mimeType, 'image/')) {
				break;
			}
			//Если не нужно определять тип картинки
			if (!$mimeTypesList) {
				$isImage = true;
				break;
			}
			//Проверяем дозволенные типы картинок
			foreach ($mimeTypesList as $t) {
				//Приводим тип к общему виду
				if ($t == 'jpg')                    $t = 'jpeg';
				if (!str_starts_with($t, 'image/')) $t = "image/$t";
				//Проверяем тип
				if ($mimeType == $t) {
					$isImage = true;
					break 2;
				}
			}
		} while (false);

		return $isImage;
	}



	public function choice (mixed $value, array $choicesList): bool {
		$isOK = false;

		if (!is_string($value)) {
			$isOK = true;
		} else {
			foreach ($choicesList as $e) {
				if ($value == $e) {
					$isOK = true;
					break;
				}
			}
		}

		return $isOK;
	}



	protected function strlen (string $string) {
		return mb_strlen($string , 'UTF-8');
	}
}
