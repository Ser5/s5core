<?php
namespace S5\Forms\Errors;



class FormErrors {
    /**
     * @param string[] $messages
     */
    public function __construct (public array $messages = []) {
    }



    public function isOK (): bool {
        return (count($this->messages) == 0);
    }
}
