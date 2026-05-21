<?php
namespace S5\Forms;



class FormBlock extends FormContainer {
	use \S5\ConstructTrait;



	public function __construct (array $params = []) {
		$this->_copyConstructParams($params);

		$this->fieldsMap = $this->convertDataMapToFormObjectsMap($this->fieldsMap, FormField::class);
	}
}
