<?
namespace S5\Bitrix;

/**
 * Что-то для комплексных компонентов.
 *
 * ```
 * $cc = new ComplexComponent();
 * $cc
 *    ->setParams($arParams)
 *    ->setSefModeData(array( //Для .parameters.php и component.php
 *       "index" => array(
 *          "NAME" => 'Главная',
 *          "DEFAULT" => "",
 *          "VARIABLES" => [],
 *       ),
 *       "section" => array(
 *          "NAME" => 'Секция',
 *          "DEFAULT" => "#SECTION_ID#/",
 *          "VARIABLES" => array('SECTION_ID'),
 *       ),
 *       "detail" => array(
 *          "NAME" => "Детальный просмотр",
 *          "DEFAULT" => "#SECTION_ID#/#ELEMENT_ID#/",
 *          "VARIABLES" => array('SECTION_ID', "ELEMENT_ID"),
 *       ),
 *    ))
 *    ->setComponentVariables(array('SECTION_ID','ELEMENT_ID'))
 *    ->setDefaultComponentPage('index')
 *    ->setPageSelectorLogic(function ($arVariables) {
 *       @$arVariables['SECTION_ID'] = (int)$arVariables['SECTION_ID'];
 *       @$arVariables['ELEMENT_ID'] = (int)$arVariables['ELEMENT_ID'];
 *       if ($arVariables["SECTION_ID"] != 0 and $arVariables["ELEMENT_ID"] == 0) {
 *          $componentPage = "section";
 *       }
 *       elseif ($arVariables["ELEMENT_ID"] != 0) {
 *          $componentPage = "detail";
 *       }
 *       //В прочих ситуациях $componentPage получает данные из setDefaultComponentPage()
 *    });
 *    return $cc;
 * ```
 *
 * .parameters.php:
 * ```
 * $cc = require_once __DIR__.'/cc.php';
 * $arComponentParameters = array(
 *    "PARAMETERS" => array(
 *       "VARIABLE_ALIASES" => Array(
 *          "SECTION_ID" => Array("NAME" => "Секция"),
 *          "ELEMENT_ID" => Array("NAME" => "ID элемента"),
 *       ),
 *       "SEF_MODE" => $cc->getSefModeData(),
 *       "CACHE_TIME" => Array("DEFAULT"=>"3600000"),
 *    ),
 * );
 * ```
 *
 * component.php:
 * ```
 * $cc = require_once __DIR__.'/cc.php';
 * extract($cc->run());
 * $this->IncludeComponentTemplate($componentPage);
 * ```
 */
class ComplexComponent {
	private $_params;
	private $_sefModeData;
	private $_defaultComponentPage;
	private $_pageSelectorLogic;



	public function __construct () {}

	public function setParams ($params) {
		$this->_params['arParams'] = $params;
		return $this;
	}



	public function setSefModeData ($data) {
		$this->_sefModeData = $data;
		$arDefaultUrlTemplates404 = [];
		foreach ($data as $k => $v) {
			$arDefaultUrlTemplates404[$k] = $v['DEFAULT'];
		}
		$this->_params['arDefaultUrlTemplates404'] = $arDefaultUrlTemplates404;
		return $this;
	}



	public function setComponentVariables ($variablesList) {
		$this->_params['arComponentVariables'] = $variablesList;
		return $this;
	}



	public function setDefaultComponentPage ($page) {
		$this->_defaultComponentPage = $page;
		return $this;
	}



	public function setPageSelectorLogic ($callback) {
		$this->_pageSelectorLogic = $callback;
		return $this;
	}



	public function run () {
		global $APPLICATION;
		extract($this->_params);

		$arDefaultVariableAliases404 = [];
		$arDefaultVariableAliases    = [];

		if ($arParams["SEF_MODE"] == "Y") {
			$arVariables = [];

			$arUrlTemplates    = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
			$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams["VARIABLE_ALIASES"]);

			//На основе полученных шаблонов URL и значений переменных вычисляется страница компонента.
			$componentPage = CComponentEngine::ParseComponentPath(
				$arParams["SEF_FOLDER"],
				$arUrlTemplates,
				$arVariables
			);

			if(!$componentPage) {
				//Если страница не найдена, мы можем показать какую-то страницу по умолчанию...
				$componentPage = $this->_defaultComponentPage;

				//...или отправить пользователя на 404.
				if($arParams["SET_STATUS_404"]==="Y") {
					$folder404 = str_replace("\\", "/", $arParams["SEF_FOLDER"]);
					if ($folder404 != "/")
						$folder404 = "/".trim($folder404, "/ \t\n\r\0\x0B")."/";
					if (substr($folder404, -1) == "/")
						$folder404 .= "index.php";

		 			if($folder404 != $APPLICATION->GetCurPage(true))
						CHTTP::SetStatus("404 Not Found");
				}
			}

			CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

			//Вот тут складываем результаты всех обработок - потом может пригодиться
			$arResult = array(
				"FOLDER"        => $arParams["SEF_FOLDER"],
				"URL_TEMPLATES" => $arUrlTemplates,
				"VARIABLES"     => $arVariables,
				"ALIASES"       => $arVariableAliases,
			);
		} else {
			$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams["VARIABLE_ALIASES"]);
			CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

			$callback = $this->_pageSelectorLogic;
			$componentPage = $callback($arVariables);
			if (!$componentPage) {
				$componentPage = $this->_defaultComponentPage;
			}

			$arResult = array(
				"FOLDER" => "",
				"URL_TEMPLATES" => Array(
					"index"  => htmlspecialchars($APPLICATION->GetCurPage()),
					"all"    => htmlspecialchars($APPLICATION->GetCurPage()."?".$arVariableAliases["SECTION_ID"]."=#SECTION_ID#"),
					"detail" => htmlspecialchars($APPLICATION->GetCurPage()."?".$arVariableAliases["ELEMENT_ID"]."=#ELEMENT_ID#"),
				),
				"VARIABLES" => $arVariables,
				"ALIASES" => $arVariableAliases
			);
		}

		return compact('arResult', 'componentPage');
	}



	public function getSefModeData () {
		return $this->_sefModeData;
	}
}
