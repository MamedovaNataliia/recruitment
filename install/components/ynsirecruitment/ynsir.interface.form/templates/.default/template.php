<?php
use Bitrix\Disk\File;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->IncludeComponent(
		'bitrix:main.interface.form',
		'ynsir.view',
		array(
			'FORM_ID' => $arParams['FORM_ID'],
			'THEME_GRID_ID' => $arParams['GRID_ID'],
			'TABS' => $arParams['TABS'],
			'BUTTONS' =>  $arParams['BUTTONS'],
			'DATA' => $arParams['DATA'],
            'SHOW_FORM_TAG' => 'N'
		),
		$component, array('HIDE_ICONS' => 'Y')
	);
