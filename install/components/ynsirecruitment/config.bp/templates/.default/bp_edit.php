<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'ynsirecruitment:config.bp.edit',
	'',
	Array(
		'BP_ENTITY_ID' => $arResult['VARIABLES']['entity_id'],
		'BP_BP_ID' => $arResult['VARIABLES']['bp_id'],
		'ENTITY_LIST_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['entity_list'],
		'BP_LIST_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['bp_list'],
		'BP_EDIT_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['bp_edit']
	),
	$component
);
?>