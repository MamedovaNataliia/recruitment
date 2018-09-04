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

if (!CModule::IncludeModule('bizprocdesigner'))
{
	ShowError(GetMessage('BIZPROCDESIGNER_MODULE_NOT_INSTALLED'));
	return;
}

$YNSIRPerms_ = new YNSIRPerms_($USER->GetID());
if (!$YNSIRPerms_->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
	return;
}

$arTypes = Array(
	YNSIR_JOB_ORDER => array(
		'ID' => YNSIR_JOB_ORDER,
		'NAME' => GetMessage('YNSIR_BP_JOB_ORDER'),
		'DOCUMENT' => 'YNSIRDocumentJobOrder',
		'TYPE' => YNSIR_JOB_ORDER
	),
);

$arResult['ENTITY_ID'] = isset($_REQUEST['entity_id']) ? $_REQUEST['entity_id']: $arParams['BP_ENTITY_ID'];
$arResult['BP_ID'] = isset($_REQUEST['bp_id']) ? $_REQUEST['bp_id']: $arParams['BP_BP_ID'];
$arResult['ENTITY_NAME'] = $arTypes[$arResult['ENTITY_ID']]['NAME'];
$arResult['DOCUMENT_TYPE'] = $arTypes[$arResult['ENTITY_ID']]['TYPE'];
$arResult['ENTITY_TYPE'] = $arTypes[$arResult['ENTITY_ID']]['DOCUMENT'];
define('CRM_ENTITY', $arResult['ENTITY_TYPE']);

$arResult['~ENTITY_LIST_URL'] = $arParams['~ENTITY_LIST_URL'];
$arResult['ENTITY_LIST_URL'] = htmlspecialcharsbx($arResult['~ENTITY_LIST_URL']);

$arResult['~BP_LIST_URL'] = str_replace('#entity_id#', $arResult['ENTITY_ID'], $arParams['~BP_LIST_URL']);
$arResult['BP_LIST_URL'] = htmlspecialcharsbx($arResult['~BP_LIST_URL']);

$arResult['~BP_EDIT_URL'] = str_replace(	array('#entity_id#'),	array($arResult['ENTITY_ID']),	$arParams['~BP_EDIT_URL']);
$arResult['BP_EDIT_URL'] = htmlspecialcharsbx($arResult['~BP_EDIT_URL']);

if (strlen($arResult['BP_ID']) > 0)
{
	$db_res = CBPWorkflowTemplateLoader::GetList(
		array($by => $order),
		array('DOCUMENT_TYPE' => array('ynsirecruitment', $arResult['ENTITY_TYPE'], $arResult['DOCUMENT_TYPE']), 'ID' => $arResult['BP_ID']),
		false,
		false,
		array('ID', 'NAME'));
	if ($db_res)
		$arTemplate = $db_res->Fetch();
}

$this->IncludeComponentTemplate();

$APPLICATION->SetTitle(GetMessage('YNSIR_BP_LIST_TITLE_EDIT', array('#NAME#' => $arResult['ENTITY_NAME'])));
$APPLICATION->AddChainItem(GetMessage('YNSIR_BP_ENTITY_LIST'), $arResult['~ENTITY_LIST_URL']);
$APPLICATION->AddChainItem($arResult['ENTITY_NAME'], $arResult['~BP_LIST_URL']);
if (strlen($arTemplate['NAME']) > 0)
	$APPLICATION->AddChainItem($arTemplate['NAME'],
		CComponentEngine::MakePathFromTemplate(
			$arResult['~BP_EDIT_URL'],
			array('bp_id' => $arResult['BP_ID'])
		)
	);

?>