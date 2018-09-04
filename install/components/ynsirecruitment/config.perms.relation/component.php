<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetTitle(GetMessage('YNSIR_PERMS_ENTITY_LIST'));
$YNSIRPerms = YNSIRPerms::havePermsConfig();
if (!$YNSIRPerms)
{
	ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
	return;
}

CJSCore::Init(array('access', 'window'));

$arParams['PATH_TO_ROLE_EDIT'] = "/recruitment/config/" . $arParams['URL_TEMPLATES']['role_edit'];
$arParams['PATH_TO_ENTITY_LIST'] = "/recruitment/config/" . $arParams['URL_TEMPLATES']['perms_relation'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['ACTION'] == 'save' && check_bitrix_sessid())
{
	$arPerms = isset($_POST['PERMS'])? $_POST['PERMS']: array();
	$YNSIRRole = new YNSIRRole();
	$YNSIRRole->SetRelation($arPerms);
	LocalRedirect($APPLICATION->GetCurPage());
}

$arResult['PATH_TO_ROLE_ADD'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
	array(
		'role_id' => 0
	)
);

$arResult['ROLE'] = array();
$obRes = YNSIRRole::GetList();
while ($arRole = $obRes->Fetch())
{
	$arRole['PATH_TO_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
		array(
			'role_id' => $arRole['ID']
		)
	);
	$arRole['PATH_TO_DELETE'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
		array(
			'role_id' => $arRole['ID']
		)),
		array('delete' => '1', 'sessid' => bitrix_sessid())
	);
	$arRole['NAME'] = htmlspecialcharsbx($arRole['NAME']);
	$arResult['ROLE'][$arRole['ID']] = $arRole;
}

$arResult['RELATION'] = array();
$arResult['RELATION_ENTITY'] = array();
$obRes = YNSIRRole::GetRelation();
while ($arRelation = $obRes->Fetch())
{
	$arResult['RELATION'][$arRelation['RELATION']] = $arRelation;
	$arResult['RELATION_ENTITY'][$arRelation['RELATION']] = true;
}

$CAccess = new CAccess();
$arNames = $CAccess->GetNames(array_keys($arResult['RELATION_ENTITY']));
foreach ($arResult['RELATION'] as &$arRelation)
{
	$arRelation['NAME'] = htmlspecialcharsbx($arNames[$arRelation['RELATION']]['name']);
	$providerName = $arNames[$arRelation['RELATION']]['provider'];
	if(!empty($providerName))
	{
		$arRelation['NAME'] = '<b>'.htmlspecialcharsbx($providerName).':</b> '.$arRelation['NAME'];
	}
}
unset($arRelation);

$this->IncludeComponentTemplate();
$APPLICATION->AddChainItem(GetMessage('YNSIR_PERMS_ENTITY_LIST'), $arParams['PATH_TO_ENTITY_LIST']);
?>