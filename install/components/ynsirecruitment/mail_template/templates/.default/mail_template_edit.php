<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

//$APPLICATION->IncludeComponent(
//	'bitrix:crm.mail_template.menu',
//	'',
//	array(
//		'PATH_TO_MAIL_TEMPLATE_LIST' => $arResult['PATH_TO_MAIL_TEMPLATE_LIST'],
//		'PATH_TO_MAIL_TEMPLATE_ADD' => $arResult['PATH_TO_MAIL_TEMPLATE_ADD'],
//		'PATH_TO_MAIL_TEMPLATE_EDIT' => $arResult['PATH_TO_MAIL_TEMPLATE_EDIT'],
//		'ELEMENT_ID' => $arResult['VARIABLES']['element_id'],
//		'TYPE' => 'edit'
//	),
//	$component
//);

$APPLICATION->IncludeComponent(
	'ynsirecruitment:mail_template.edit',
	'', 
	array(
		'ELEMENT_ID' => $arResult['ELEMENT_ID'],
	),
	$component
);
