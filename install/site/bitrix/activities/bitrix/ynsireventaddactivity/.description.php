<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('YNSIR_ACTIVITY_EVENT_ADD_NAME'),
	'DESCRIPTION' => GetMessage('YNSIR_ACTIVITY_EVENT_ADD_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'YNSIREventAddActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'YNSIRecruitment',
		"OWN_NAME" => 'YNSIRecruitment',
	),
);
?>
