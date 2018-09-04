<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CJSCore::Init(array("jquery"));

$arDefaultUrlTemplates404 = array(
	'job_order_list' => 'list/',
    'job_order_edit' => 'edit/#id#/',
    'job_order_detail' => 'detail/#id#/',
);

if ($arParams["SEF_MODE"] == "Y") {
	$arVariables = array();
	$engine = new CComponentEngine($this);
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
	$componentPage = $engine->guessComponentPath(
		$arParams["SEF_FOLDER"],
		$arUrlTemplates,
		$arVariables
	);
	$arResult = array(
		'FOLDER' => $arParams['SEF_FOLDER'],
		'URL_TEMPLATES' => $arUrlTemplates,
		'VARIABLES' => $arVariables,
		'ALIASES' => $arVariableAliases
	);
	$this->IncludeComponentTemplate($componentPage, "");
}
?>
