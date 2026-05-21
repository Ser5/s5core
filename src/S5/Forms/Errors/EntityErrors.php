<?php
namespace S5\Forms\Errors;



class EntityErrors {
    /**
     * @param array<int|string, string[]> $messages
     */
    public function __construct (public array $messages = []) {
    }



    public function isOK (): bool {
        foreach ($this->messages as $entityMessagesList) {
            if ($entityMessagesList) {
                return false;
            }
        }
        return true;
    }
}
