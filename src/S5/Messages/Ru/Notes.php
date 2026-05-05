<?
namespace S5\Messages\Ru;

use S5\Text\Lang;



class Notes extends RuMessages {
	public function __construct (Lang|string|null $lang = null) {
		parent::__construct($lang);

		$this->messagesMap = [
			'required'       => "обязательное",
			'min_length'     => fn($v) => "длина от $v[0] "    . $this->symbolsPlurals('0'),
			'max_length'     => fn($v) => "длина до $v[0] "    . $this->symbolsPlurals('0'),
			'length_between' => fn($v) => "длина $v[0]-$v[1] " . $this->symbolsPlurals('1'),
			'min_value'      => fn($v) => "от $v[value]",
			'max_value'      => fn($v) => "до $v[value]",
			'value_between'  => fn($v) => "в пределах $v[0]-$v[1]",
			'int'            => "целое число",
			'number'         => "число",
			'string'         => "строка",
			'bool'           => "булево",
			'object'         => "объект",
			'object_of'      => fn($v) => "объект $v[0]",
			'email'          => "e-mail",
			'url'            => "Ссылка",
			'image' => function($v) {
				$message = "Картинка";
				if (!$v) return $message;
				return "$message: " . join(', ', $v);
			},
			'choice' => "",
		];
	}
}
