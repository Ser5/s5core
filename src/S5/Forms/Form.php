<?php
namespace S5\Forms;



class Form extends FormContainer {
	use \S5\ConstructTrait;



	public function __construct (array $params = []) {
		$this->_copyConstructParams($params);

		$this->blocksMap = $this->convertDataMapToFormObjectsMap($this->blocksMap, FormBlock::class);
		$this->fieldsMap = $this->convertDataMapToFormObjectsMap($this->fieldsMap, FormField::class);
	}
}
