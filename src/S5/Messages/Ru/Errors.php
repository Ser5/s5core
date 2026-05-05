<?
namespace S5\Messages\Ru;

use S5\Text\Lang;



class Errors extends RuMessages {
	public function __construct (Lang|string|null $lang = null) {
		parent::__construct($lang);

		$this->messagesMap = [
			'required'       => "значение не указано",
			'min_length'     => fn($v) => "длина должна быть от $v[0] "    . $this->symbolsPlurals('0'),
			'max_length'     => fn($v) => "длина должна быть до $v[0] "    . $this->symbolsPlurals('0'),
			'length_between' => fn($v) => "длина должна быть $v[0]-$v[1] " . $this->symbolsPlurals('1'),
			'min_value'      => fn($v) => "значение должно быть от $v[0]",
			'max_value'      => fn($v) => "значение должно быть до $v[0]",
			'value_between'  => fn($v) => "значение должно быть в пределах $v[0]-$v[1]",
			'int'            => "значение должно быть целым числом",
			'number'         => "значение должно быть числом",
			'string'         => "значение должно быть строкой",
			'bool'           => "значение должно быть булевым",
			'object'         => "значение должно быть объектом",
			'object_of'      => fn($v) => "значение должно быть объектом $v[0]",
			'email'          => "неверный e-mail",
			'url'            => "неверная ссылка",
			'image' => function($v) {
				if (!$v) return "значение должно быть файлом с изображением";
				return "допустимые форматы изображения: " . join(', ', $v);
			},
			'choice' => fn($v) => "допустимые варианты: " . join(', ', $v),
		];
	}
}
