<?php
/**
 * Created by PhpStorm.
 * User: nhatth
 * Date: 6/12/17
 * Time: 2:45 PM
 */
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
echo $arResult['VARIABLES']['entity'];
echo $arResult['VARIABLES']['element_id'];

$APPLICATION->IncludeComponent(
    'ynsirecruitment:type.list.add',
    '.default',
    Array(
        'DELETE' => 'Y',
        'entity'=> $arResult['VARIABLES']['entity'],
        'ELEMENT_ID'=> $arResult['VARIABLES']['element_id']
    )
);
?>