<?php
namespace S5\Forms;



class FormContainer extends FormItem {
	use \S5\ConstructTrait;

    /** @var array<string, FormBlock> */
    public array $blocksMap = [];

    /** @var array<string, FormField> */
    public array $fieldsMap = [];



    /**
     * @template TField of FormField
     * @param array<string, FormItem|array> $itemsMap
     * @param class-string<TField> $className
     *
     * @return TField[]
     */
    protected function convertDataMapToFormObjectsMap (array $itemsMap, string $className): array {
        foreach ($itemsMap as $itemCode => &$item) {
            if (!is_a($item, $className)) {
                $item = new $className($item);
            }
            if (!$item->code) {
                $item->code = $itemCode;
            }
        }
        unset($item);
        return $itemsMap;
    }



    public function toArray (): array {
        $r = (array)$this;

        foreach (['blocksMap', 'fieldsMap'] as $fieldName) {
            foreach ($this->$fieldName as $mapKey => $mapValue) {
                $r[$fieldName][$mapKey] = $mapValue->toArray();
            }
        }

        return $r;
    }
}
