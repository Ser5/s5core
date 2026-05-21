<?php
namespace S5\Forms\Errors;



class AllErrors {
    public FormErrors   $form;
    public EntityErrors $blocks;
    public EntityErrors $fields;

    public function __construct () {
        $this->form   = new FormErrors();
        $this->blocks = new EntityErrors();
        $this->fields = new EntityErrors();
    }



    public function isOK (): bool {
        return ($this->form->isOK() and $this->blocks->isOK() and $this->fields->isOK());
    }



    public function toArray (): array {
        $data = [
            'isOK'   => $this->isOK(),
            'form'   => (array)$this->form,
            'blocks' => (array)$this->blocks,
            'fields' => (array)$this->fields,
        ];

        $data['form']['isOK']   = $this->form->isOK();
        $data['blocks']['isOK'] = $this->blocks->isOK();
        $data['fields']['isOK'] = $this->fields->isOK();

        return $data;
    }
}
