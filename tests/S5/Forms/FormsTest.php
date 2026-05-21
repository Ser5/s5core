<?
namespace S5\Siteman;

use S5\Forms\{Form, FormBlock, FormField};



class FormsTest extends \S5\TestCase {
	public function testDataToFormObjectsConversion () {
		$fieldData = [
			'type' => 'text',
			'name' => 'Название поля',
		];
		$blockData = [
			'fieldsMap' => [
				'field' => $fieldData,
			],
		];

		$formWithObjects = new Form(['blocksMap' => [
			'block' => new FormBlock(['fieldsMap' => [
				'field' => new FormField($fieldData),
			]]),
		]]);

		$formWithData = new Form([
			'blocksMap' => [
				'block' => $blockData,
			],
		]);

		foreach (['formWithObjects', 'formWithData'] as $formVarName) {
			$block = $$formVarName->blocksMap['block'];
			$this->assertInstanceOf(FormBlock::class, $block);
			$this->assertEquals('block', $block->code);

			$field = $block->fieldsMap['field'];
			$this->assertInstanceOf(FormField::class, $field);
			$this->assertEquals('field', $field->code);
		}
	}



	public function testForms () {
		$form = new Form(['blocksMap' => [
				'registration' => ['fieldsMap' => [
					'name' => [
						'type' => 'text',
						'name' => 'Имя',
					],
					'email' => [
						'type' => 'email',
						'name' => 'Емейл',
					],
					'password' => [
						'type' => 'password',
						'name' => 'Пароль',
					],
				]],
				'personal' => ['fieldsMap' => [
					'sex' => [
						'type' => 'choice',
						'name' => 'Пол',
						'optionsMap' => [
							'm' => 'М',
							'f' => 'Ж',
						],
					],
					'color' => [
						'type'     => 'choice',
						'htmlType' => 'select',
						'name'     => 'Цвет',
						'optionsMap' => [
							'white' => 'Белый',
							'black' => 'Негр',
						],
					],
					'height' => [
						'type' => 'number',
						'name' => 'Рост',
					],
					'weight' => [
						'type' => 'text',
						'name' => 'Вес',
					],
					'image' => [
						'type' => 'file',
						'name' => 'Картинка',
					],
					'job' => [
						'type' => 'choice_multiple',
						'name' => 'Работа',
						'optionsMap' => [
							'dev'    => 'Разработка',
							'admin'  => 'Администрирование',
							'design' => 'Дизайн',
						],
					],
				]],
			],
		]);

		$this->assertEquals('text',     $form->blocksMap['registration']->fieldsMap['name']->htmlType);
		$this->assertEquals('radio',    $form->blocksMap['personal']->fieldsMap['sex']->htmlType);
		$this->assertEquals('select',   $form->blocksMap['personal']->fieldsMap['color']->htmlType);
		$this->assertEquals('checkbox', $form->blocksMap['personal']->fieldsMap['job']->htmlType);
	}
}
