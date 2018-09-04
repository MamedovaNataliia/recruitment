<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->IncludeComponent(
    'ynsirecruitment:mail_template',
    '.default',
    Array(
        'template_page'=> 'mail_template_edit',
        'ELEMENT_ID'=> $arResult['VARIABLES']['element_id']
    )
);
?>