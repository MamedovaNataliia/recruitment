<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetTitle(GetMessage('YNSIR_PERMS_ROLE_EDIT'));
$YNSIRPerms = YNSIRPerms::havePermsConfig();
if (!$YNSIRPerms)
{
	ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_ROLE_EDIT'] = "/recruitment/config/" . $arParams['URL_TEMPLATES']['role_edit'];
$arParams['PATH_TO_ENTITY_LIST'] = "/recruitment/config/" . $arParams['URL_TEMPLATES']['perms_relation'];

$arParams['ROLE_ID'] = (int) $arParams['VARIABLES']['role_id'];
$bVarsFromForm = false;

$arResult['PATH_TO_ROLE_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
	array(
		'role_id' => $arParams['ROLE_ID']
	)
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])) && check_bitrix_sessid())
{
	$bVarsFromForm = true;
	$arFields = array(
		'NAME' => $_POST['NAME'],
		'RELATION' => isset($_POST['ROLE_PERMS'])? $_POST['ROLE_PERMS']: Array()
	);

	$YNSIRRole = new YNSIRRole();
	if ($arParams['ROLE_ID'] > 0)
	{
		if (!$YNSIRRole->Update($arParams['ROLE_ID'], $arFields))
			$arResult['ERROR_MESSAGE'] = $arFields['RESULT_MESSAGE'];
	}
	else
	{
		$arParams['ROLE_ID'] = $YNSIRRole->Add($arFields);
		if ($arParams['ROLE_ID'] === false)
			$arResult['ERROR_MESSAGE'] = $arFields['RESULT_MESSAGE'];
	}

	if(strlen(trim($arFields['NAME'])) <= 0){
        $arResult['ERROR_MESSAGE'] = GetMessage('YNSIR_NAME_REQUIRED');
    }

	if (empty($arResult['ERROR_MESSAGE']))
	{
		if (isset($_POST['apply']))
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
				array(
					'role_id' => $arParams['ROLE_ID']
				)
			));
		else
			LocalRedirect($arParams['PATH_TO_ENTITY_LIST']);
	}
	else
		ShowError($arResult['ERROR_MESSAGE']);

	$arResult['ROLE'] = array(
		'ID' => $arParams['ROLE_ID'],
		'NAME' => $arFields['NAME']
	);
	$arResult['ROLE_PERMS'] = $arFields['RELATION'];
}
else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete']) && check_bitrix_sessid() && $arParams['ROLE_ID'] > 0)
{
	$YNSIRRole = new YNSIRRole();
	$YNSIRRole->Delete($arParams['ROLE_ID']);
	LocalRedirect($arParams['PATH_TO_ENTITY_LIST']);
}

if (!$bVarsFromForm)
{
	if ($arParams['ROLE_ID'] > 0)
	{
		$obRes = YNSIRRole::GetList(array(), array('ID' => $arParams['ROLE_ID']));
		$arResult['ROLE'] = $obRes->Fetch();
		if ($arResult['ROLE'] == false)
			$arParams['ROLE_ID'] = 0;
	}

	if ($arParams['ROLE_ID'] <= 0)
	{
		$arResult['ROLE']['ID'] = 0;
		$arResult['ROLE']['NAME'] = '';
	}

	$arResult['ROLE_PERMS'] = array();

}
if ($arParams['ROLE_ID'] > 0 && !$bVarsFromForm)
	$arResult['~ROLE_PERMS'] = YNSIRRole::GetRolePerms($arParams['ROLE_ID']);
if (!$bVarsFromForm)
	$arResult['ROLE_PERMS'] = $arResult['~ROLE_PERMS'];

$arResult['ENTITY'] = YNSIRConfig::getEntityPerms();
$arResult['FULL_PERMS'] = YNSIRConfig::getFullPerms();
$arPerms = array_keys($arResult['FULL_PERMS']);
$arAllowedEntityPerms = YNSIRConfig::getAllowedEntityPerms();

$arResult['ENTITY_FIELDS'] = YNSIRConfig::getSectionCandidatePerms();

$arResult['ROLE_PERM'] = YNSIRConfig::getPermissionSet();

foreach($dealCategoryConfigs as $typeName => $config)
{
	if(isset($config['FIELDS']) && is_array($config['FIELDS']))
	{
		$arResult['ENTITY_FIELDS'][$typeName] = $config['FIELDS'];
	}

	$arResult['ROLE_PERM'][$typeName] = $permissionSet;
}

$arResult['PATH_TO_ROLE_DELETE'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
	array(
		'role_id' => $arResult['ROLE']['ID']
	)),
	array('delete' => '1', 'sessid' => bitrix_sessid())
);

foreach ($arPerms as $perm)
{
	foreach ($arResult['ENTITY'] as $entityType => $entityName)
	{
		if(!isset($arAllowedEntityPerms[$entityType]) || in_array($perm, $arAllowedEntityPerms[$entityType]))
		{
			$arResult['ENTITY_PERMS'][$entityType][] = $perm;
		}
		
		if (isset($arResult['ENTITY_FIELDS'][$entityType]))
		{
			foreach ($arResult['ENTITY_FIELDS'][$entityType] as $fieldID => $arFieldValue)
			{
				foreach ($arFieldValue as $fieldValueID => $fieldValue)
				{
					if (!isset($arResult['ROLE_PERMS'][$entityType][$perm][$fieldID][$fieldValueID]) || $arResult['ROLE_PERMS'][$entityType][$perm][$fieldID][$fieldValueID] == '-')
						$arResult['ROLE_PERMS'][$entityType][$perm][$fieldID][$fieldValueID] = $arResult['ROLE_PERMS'][$entityType][$perm]['-'];
				}
			}
		}
	}
}

$this->IncludeComponentTemplate();
$APPLICATION->AddChainItem(GetMessage('YNSIR_PERMS_ENTITY_LIST'), $arParams['PATH_TO_ENTITY_LIST']);
$APPLICATION->AddChainItem(GetMessage('YNSIR_PERMS_ROLE_EDIT'), $arResult['PATH_TO_ROLE_EDIT']);
?>