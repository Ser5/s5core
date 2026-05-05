<?
namespace S5\Validation;

use S5\Messages;



/**
 * Проверка данных.
 *
 * Пример поддерживаемых форматов написания правил:
 * ```
 * $v = new Validator();
 * $rules = [
 *    'email'  => 'email',         //Простое условие
 *    'login'  => 'length:5-24',   //Диапазон
 *    'image'  => 'image:jpg,png', //Список
 *    'height' => 'optional | int | min_value:0', //Несколько условий
 * ];
 * ```
 */
class Validator {
	public function __construct (
		protected ?Messages         $errorMessages    = null,
		protected ?ValidatorMethods $validatorMethods = null,
	) {
		if (!$this->errorMessages) {
			$this->errorMessages = new \S5\Messages\Ru\Errors();
		}
		if (!$this->validatorMethods) {
			$this->validatorMethods = new ValidatorMethods();
		}
	}



	/**
	 * @param  array<string, string|array> $rules
	 * @return array<string, array<string, string>>
	 */
	public function validate (array $rules, array $data): array {
		$errorMessagesMap = [];

		foreach ($rules as $fieldName => $rule) {
			$fieldValue = $data[$fieldName] ?? null;
			$errorMessagesMap[$fieldName] = $this->validateField($rule, $fieldValue);
		}

		return $errorMessagesMap;
	}



	/**
	 * @return array<string, string>
	 */
	protected function validateField (string|array $rule, mixed $fieldValue): array {
		$errorMessagesList = [];

		$map = $this->getRulePartsMap($rule);
		if (!key_exists('optional', $map) or !$this->validatorMethods->optional($fieldValue)) {
			foreach ($map as $method => $data) {
				if ($method == 'optional') {
					continue;
				}
				$vars = $data['vars'];
				$isValid = match ($data['args_type']) {
					'empty'           => $this->validatorMethods->{$method}($fieldValue),
					'range'           => $this->validatorMethods->{$method}($fieldValue, ...$vars),
					'single','choice' => $this->validatorMethods->{$method}($fieldValue, $vars),
				};
				if (!$isValid) {
					$errorMessagesList[] = $this->errorMessages->get(
						$method,
						(is_scalar($vars) ? [$vars] : $vars)
					);
				}
			}
		}

		return $errorMessagesList;
	}



	/**
	 * @return array<string, string|array|null>
	 */
	protected function getRulePartsMap (string|array $rule): array {
		$rulePartsMap = [];

		$split = fn($string, $symbol) => preg_split('/\s*' .$symbol. '+\s*/', $string);

		if (is_array($rule)) {
			$rulePartsMap = $rule;
		} else {
			if (is_string($rule)) {
				$rule          = trim($rule);
				$rule          = trim($rule, '|');
				$rulePartsList = $split($rule, '\|');
				foreach ($rulePartsList as $partString) {
					$key      = '';
					$vars     = '';
					$partData = [
						'args_type' => '',
						'vars'      => '',
					];
					if (!str_contains($partString, ':')) {
						//email
						$partData['args_type'] = 'empty';
						$key                   = $partString;
					} else {
						[$key, $vars]   = $split($partString, ':');
						if (str_contains($vars, '-')) {
							//length:1-10
							$partData['args_type'] = 'range';
							$partData['vars']      = $split($vars, '-');
						} elseif (str_contains($vars, ',')) {
							//choice:1,2,3
							$partData['args_type'] = 'choice';
							$partData['vars']      = $split($vars, ',');
						} else {
							//min_length:0
							$partData['args_type'] = 'single';
							$partData['vars']      = $vars;
						}
					}
					$rulePartsMap[$key] = $partData;
				}
			}
		}

		return $rulePartsMap;
	}
}
