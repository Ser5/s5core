<?
namespace S5\Messages;

use S5\Validation\Validator;



class ValidatorTest extends \S5\TestCase {
	public function testValidator () {
		$imagesDir = new \S5\IO\Directory(__DIR__.'/images/');

		$v = new Validator();

		$assertCount = function ($expected, $fieldName, $fieldValue, $errorMessagesList) {
			$punitMessage = "$fieldName: " . join("\n", $errorMessagesList) . "\nПередано:\n";
			ob_start();
			var_dump($fieldValue);
			$punitMessage .= ob_get_clean();
			$this->assertCount($expected, $errorMessagesList, $punitMessage);
		};

		$rules = [
			'name'       => 'optional | length_between:5-16',
			'email'      => 'email',
			'url'        => 'url',
			'password'   => 'min_length:8',
			'about'      => 'optional | max_length:10',
			'height'     => 'optional | int | min_value:100',
			'weight'     => 'optional | number | max_value:200',
			'year'       => 'optional | int | value_between:1900-2000',
			'anyObject'  => 'object',
			'someObject' => 'object_of:\S5\Messages\SomeClass',
			'image'      => 'image',
			'typedImage' => 'image:jpg,png,webp',
			'choice'     => 'choice:a,b,c',
		];


		//Верные данные
		$data = [
			'name'       => 'Василий',
			'email'      => 'vasya@example.com',
			'url'        => 'http://example.com',
			'password'   => '11111111',
			'about'      => 'Обо мне',
			'height'     => 183,
			'weight'     => 65,
			'year'       => 1981,
			'anyObject'  => new \stdClass(),
			'someObject' => new SomeClass(),
			'image'      => "$imagesDir/bmp.bmp",
			'typedImage' => "$imagesDir/png.png",
			'choice'     => 'a',
		];
		$r = $v->validate($rules, $data);
		foreach ($r as $fieldName => $errorMessagesList) {
			$assertCount(0, $fieldName, $data[$fieldName], $errorMessagesList);
		}

		//Кривые данные
		$data = [
			'name'       => 'Вася',
			'email'      => 'example.com',
			'url'        => 'example.com',
			'password'   => '1111',
			'about'      => 'Обо мне длинный текст',
			'height'     => 80,
			'weight'     => 210,
			'year'       => 1800,
			'anyObject'  => '',
			'someObject' => new \stdClass(),
			'image'      => 0,
			'typedImage' => "$imagesDir/bmp.bmp",
			'choice'     => 'd',
		];
		$r = $v->validate($rules, $data);
		foreach ($r as $fieldName => $errorMessagesList) {
			$assertCount(1, $fieldName, $data[$fieldName], $errorMessagesList);
		}
	}
}



class SomeClass {}
