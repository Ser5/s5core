<?php
namespace S5\Forms;

use S5\Forms\Form;
use S5\Forms\Errors\AllErrors;



class FormData {
    public function __construct (
        public ?Form      $form       = null,
        public array      $valuesData = [],
        public ?AllErrors $errors     = null,
    ) {
    }



    public function toArray (): array {
        $r = [
            'form'       => $this?->form?->toArray(),
            'valuesData' => $this->valuesData,
            'errors'     => $this?->errors?->toArray() ?? [],
        ];

        return $r;
    }



    /*public function fillvaluesDataDefaults () {
        if ($this->form and $this->valuesData) {
            foreach (array_keys($this->form->fieldsMap) as $name) {

            }
        }
    }*/
}
