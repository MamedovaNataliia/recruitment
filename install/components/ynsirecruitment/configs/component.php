<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetTitle(GetMessage('C_SETTINGS'));
if (!CModule::IncludeModule('ynsirecruitment')){
	if($_SESSION["LICENSE_HRM"] == false){
		$APPLICATION->SetTitle(GetMessage('YNSIR_NO_LICENCE_TITLE'));
		ShowError(GetMessage('YNSIR_NO_LICENCE_MESSAGE'));
	}
	else {
		ShowError(GetMessage('YNSIR_MODULE_NOT_INSTALLED'));
	}
	return false;
}
$APPLICATION->SetAdditionalCSS("/bitrix/js/crm/css/crm.css");
$arDefaultUrlTemplates404 = array(
    'configs' => 'configs/',
    'bizproc' => 'bizproc/',
    'hr_mananger' => 'hr_mananger/',
	'job_order_template' => 'job-order-template/#cate_id#/',
	'mailtemplate' => 'mailtemplate/',
	'mailtemplate_edit' => 'mailtemplate/edit/#element_id#/',
	'perms_relation' => 'perms/',
	'role_edit' => 'perms/#role_id#/edit',
	'list' => 'lists/#entity#/',
    'listadd' => 'lists/#entity#/add/',
    'listedit' => 'lists/#entity#/edit/#element_id#',
    'listdelete' => 'lists/#entity#/delete/#element_id#',
);
global $USER;
$ynsirPerms = YNSIRPerms_::getCurrentUserPermissions();
if(!$ynsirPerms->HavePerm('CONFIG', YNSIR_PERM_NONE))
    $permConfig = true;
//$arResult['ACCESS_PERMS']['READ']    = !$ynsirPerms->HavePerm(YNSIR_PERM_ENTITY_LIST, YNSIR_PERM_NONE, 'READ');
//if($arResult['ACCESS_PERMS']['READ'])
//    $permList = true;
if(!$permConfig) {
    ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
    return;
}

//if($ynsirPerms->IsAccessEnabled())
//    $arResult['IS_ACCESS_ENABLED'] = true;

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
		'ALIASES' => $arVariableAliases,
		'TEMPLATE' => $componentPage,
        'PERM_CONFIG' => $permConfig,
        'PERM_LIST' => true,

	);
	CUtil::InitJSCore();
	$this->IncludeComponentTemplate($componentPage, "");
}
?>