<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('ynsirecruitment'))
{
	ShowError(GetMessage('YNSIR_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('bizproc') || !CBPRuntime::isFeatureEnabled())
{
	ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
	return;
}

$YNSIRPerms_ = new YNSIRPerms_($USER->GetID());

if (!$YNSIRPerms_->HavePerm('CONFIG', YNSIR_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
	return;
}

$arTypes = Array(
	YNSIR_JOB_ORDER => array(
		'ID' => YNSIR_JOB_ORDER,
		'NAME' => GetMessage('YNSIR_BP_JOB_ORDER'),
		'DESC' => GetMessage('YNSIR_BP_JOB_ORDER_DESC')
	)
);

foreach($arTypes as $key => $ar)
{
	$arResult['ROWS'][$ar['ID']] = $ar;
	$arResult['ROWS'][$ar['ID']]['LINK_LIST'] = str_replace('#entity_id#', $ar['ID'], $arParams['~BP_LIST_URL']);
	$arResult['ROWS'][$ar['ID']]['LINK_ADD'] = str_replace(	array('#entity_id#', '#bp_id#'),	array($ar['ID'], 0), $arParams['~BP_EDIT_URL']);
}

$this->IncludeComponentTemplate();

$APPLICATION->AddChainItem(GetMessage('YNSIR_BP_ENTITY_LIST'), $arResult['~ENTITY_LIST_URL']);
?>