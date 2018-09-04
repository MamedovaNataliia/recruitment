<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm')) {
    ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
    return;
}

if (!CModule::IncludeModule('ynsirecruitment')) {
    return;
}
global $APPLICATION;


$componentPage = $arParams['template_page'];


$curPage = $APPLICATION->GetCurPage();

$arResult =
    array_merge(
        array(
            'VARIABLES' => $arVariables,
            'ALIASES' => $arParams['SEF_MODE'] == 'Y' ? array() : $arVariableAliases,
            'ELEMENT_ID' => isset($arParams['ELEMENT_ID']) ? $arParams['ELEMENT_ID'] : ''
        ),
        $arResult
    );

$this->IncludeComponentTemplate($componentPage);
?>