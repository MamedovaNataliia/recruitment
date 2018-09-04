<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('YNSIR_CONTACT_LIST_NAME'),
	'DESCRIPTION' => GetMessage('YNSIR_CONTACT_LIST_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 20,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('YNSIR_NAME'),
		'CHILD' => array(
			'ID' => 'contact',
			'NAME' => GetMessage('YNSIR_CONTACT_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>