<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('YNSIR_BP_LIST_NAME'),
	'DESCRIPTION' => GetMessage('YNSIR_BP_LIST_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 30,
	'CACHE_PATH' => 'Y',
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('YNSIR_NAME'),
		'CHILD' => array(
			'ID' => 'config',
			'NAME' => GetMessage('YNSIR_CONFIG_NAME'),
    		'CHILD' => array(
    			'ID' => 'config_bp',
                'SORT' => 30
            )
        )
	),
);

?>