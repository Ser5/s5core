<?
namespace S5\Bitrix;

class ComponentParams {
	protected $componentParams;
	protected $params;

	public function __construct (&$arComponentParameters, $arCurrentValues) {
		$this->componentParams = &$arComponentParameters;
		$this->params          = $arCurrentValues;
	}



	public function getIblockParams (array $overrideParams = []): array {
		\CModule::IncludeModule('iblock');
		$arIBlockType = \CIBlockParameters::GetIBlockTypes();

		$iblockNamesHash = [];
		$r = \CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $this->params["IBLOCK_TYPE"], "ACTIVE"=>"Y"));
		while ($e = $r->Fetch()) {
			$iblockNamesHash[$e["ID"]] = "[".$e["ID"]."] ".$e["NAME"];
		}

		$params = array(
			"IBLOCK_TYPE" => array(
				"PARENT"  => "BASE",
				"NAME"    => GetMessage("BN_P_IBLOCK_TYPE"),
				"TYPE"    => "LIST",
				"VALUES"  => $arIBlockType,
				"REFRESH" => "Y",
			),
			"IBLOCK_ID" => array(
				"PARENT"            => "BASE",
				"NAME"              => GetMessage("BN_P_IBLOCK"),
				"TYPE"              => "LIST",
				"VALUES"            => $iblockNamesHash,
				"REFRESH"           => "Y",
				"ADDITIONAL_VALUES" => "Y",
			),
		);

		$params = array_merge_recursive_distinct($params, $overrideParams);
		return $params;
	}


	/**
	 * Добавляет в настройки компонента пункты выбора типа инфоблока и ID инфоблока.
	 *
	 * ```
	 * $componentParams = new ComponentParams($arCurrentValues);
	 * $componentParams->addIblockParams(array(
	 *    'IBLOCK_TYPE' => array('NAME'=> 'Иблок типа'),
	 *    'IBLOCK_ID'   => array('NAME'=> 'Иблок идэ'),
	 * ))
	 * ```
	 *
	 * @param  array $overrideParams
	 * @return array
	 */
	public function addIblockParams ($overrideParams = []) {
		$params = $this->getIblockParams($overrideParams);
		$this->componentParams['PARAMETERS'] = array_merge($this->componentParams['PARAMETERS'], $params);
	}
}
