<?php
namespace S5\Forms;



class FormItem {
    public int    $id    = 0;
    public string $code  = '';
    public string $label = '';
    public string $note  = '';



    public function toArray (): array {
        return json_decode(json_encode($this), true);
    }
}
