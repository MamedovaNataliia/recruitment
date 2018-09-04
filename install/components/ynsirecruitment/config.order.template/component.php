<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
$APPLICATION->SetTitle(GetMessage("YNSIR_COT_C_TITLE"));
$YNSIRPerms = YNSIRPerms::havePermsConfig();
if($YNSIRPerms == false){
    ShowError(GetMessage('YNSIR_COT_C_PERMISSION_DENIED'));
    return;
}
$arResult['HEADERS'] = array (
    'STATUS' => 'Lead statuses',
    'SOURCE' => 'Sources',
    'CONTACT_TYPE' => 'Contact Type',
);
$arResult['GRID_ID'] = 'jo_template';
$arResult['ACCESS_PERMS'] = 1;
$arResult['RAND_STRING'] = $this->randString();
$arResult['CATEGORY'] = YNSIRGeneral::getListType(array('ENTITY' => YNSIRConfig::TL_TEMPLATE_CATEGORY), true);
$arResult['CATE_ID'] = intval($arParams['VARIABLES']['cate_id']);
if(!isset($arResult['CATEGORY'][$arResult['CATE_ID']])){
	$arKeyTemp = array_keys($arResult['CATEGORY']);
	if(!empty($arKeyTemp) && $arKeyTemp[0] > 0){
		LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['FOLDER'] . $arParams['URL_TEMPLATES']['job_order_template'],
			array(
				'cate_id' => $arKeyTemp[0]
			)
		));
	}
}
$arResult['URL_CATEGORY'] = CComponentEngine::MakePathFromTemplate($arParams['FOLDER'] . $arParams['URL_TEMPLATES']['list'],
	array(
		'entity' => strtolower(YNSIRConfig::TL_TEMPLATE_CATEGORY)
	)
);
$arResult['URL_TEMPLATE'] = str_replace('#cate_id#/', '', $arParams['FOLDER'] . $arParams['URL_TEMPLATES']['job_order_template']);

$arResult['DATA'] = YNSIRJobOrderTemplate::getList(array('CATEGORY' => $arResult['CATE_ID']));
$arResult['DEFAULT_ACTIVE'] = isset($arResult['DATA'][0]['ID']) ? $arResult['DATA'][0]['ID'] : 0;
CUtil::InitJSCore();
$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;
$this->IncludeComponentTemplate();
?>