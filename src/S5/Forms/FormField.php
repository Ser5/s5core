<?php
namespace S5\Forms;



class FormField extends FormItem {
	use \S5\ConstructTrait;

    public string  $name         = '';
    public string  $type         = 'text';
    public string  $htmlType     = '';
    public array   $optionsMap   = [];
    public string  $placeholder  = '';
    public ?string $autocomplete = null;

    public bool  $isRequired        = false;
    public bool  $isMultiple        = false;
    public array $multipleIdsList   = [];
    public array $multipleNamesList = [];



	public function __construct (array $params = []) {
        $this->_copyConstructParams($params);

        if ($this->multipleIdsList and !$this->multipleNamesList) {
            foreach ($this->multipleIdsList as $id) {
                $this->multipleNamesList[] = $this->name .'[' . $id .']';
            }
        }

        if (isset($params['multipleIdsList'])) {
            $this->isMultiple = true;
        }

        static $typeToHtmlTypeMap = [
            'choice'          => 'radio',
            'choice_multiple' => 'checkbox',
        ];
        if (!$this->htmlType) {
            $this->htmlType = $typeToHtmlTypeMap[$this->type] ?? $this->type;
        }
	}
}
