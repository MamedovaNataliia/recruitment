<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('YNSIR_BP_TYPES_NAME'),
	'DESCRIPTION' => GetMessage('YNSIR_BP_TYPES_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 20,
	'CACHE_PATH' => 'Y',
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('YNSIR_NAME'),
		'CHILD' => array(
			'ID' => 'config',
			'NAME' => GetMessage('YNSIR_CONFIG_NAME'),
    		'CHILD' => array(
    			'ID' => 'config_bp',
                'SORT' => 20
            )
        )
	),
);

?>