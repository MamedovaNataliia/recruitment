<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();
$APPLICATION->IncludeComponent(
	'bitrix:bizproc.workflow.list',
	'',
	Array(
		'MODULE_ID' => 'ynsirecruitment',
		'ENTITY' => $arResult['ENTITY_TYPE'],
		'DOCUMENT_ID' => $arResult['DOCUMENT_TYPE'],
		'EDIT_URL' => CComponentEngine::MakePathFromTemplate($arResult['~BP_EDIT_URL'],
			array(
				'bp_id' => '#ID#'
			)
		),
		'SET_TITLE'	=>	'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>