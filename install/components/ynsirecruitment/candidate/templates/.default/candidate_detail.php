<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->IncludeComponent(
    'ynsirecruitment:candidate.detail',
    '.default',
    Array(
        "FOLDER" => $arResult["FOLDER"],
        "URL_TEMPLATES" => $arResult["URL_TEMPLATES"],
        "VARIABLES" => $arResult["VARIABLES"],
        "ALIASES" => $arResult["ALIASES"],
    )
);
?>