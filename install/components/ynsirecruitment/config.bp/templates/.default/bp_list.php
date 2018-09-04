<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->IncludeComponent(
	'ynsirecruitment:config.bp.list',
	'',
	Array(
		'BP_ENTITY_ID' => $arResult['VARIABLES']['entity_id'],
		'ENTITY_LIST_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['entity_list'],
		'BP_LIST_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['bp_list'],
		'BP_EDIT_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['bp_edit']
	),
	$component
);
?>