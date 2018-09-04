<?php
/**
 * Created by PhpStorm.
 * User: nhatth
 * Date: 6/12/17
 * Time: 11:09 AM
 */
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->IncludeComponent(
    'ynsirecruitment:type.list.edit',
    '.default',
    Array(
        'entity'=> $arResult['VARIABLES']['entity'],
        'ELEMENT_ID'=> $arResult['VARIABLES']['element_id']
    )
);
?>