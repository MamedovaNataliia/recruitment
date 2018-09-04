<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->IncludeComponent(
    'ynsirecruitment:type.list.add',
    '.default',
    Array(
        'entity'=> $arResult['VARIABLES']['entity']
    )
);
?>