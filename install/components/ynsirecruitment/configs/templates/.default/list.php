<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
if ($arResult['VARIABLES']['entity'] == 'order_job_status') {
    $APPLICATION->IncludeComponent(
        'ynsirecruitment:oj.config.status',
        '.default',
        Array(
            'entity' => $arResult['VARIABLES']['entity']
        )
    );
}else {
    $APPLICATION->IncludeComponent(
        'ynsirecruitment:candidate.config.status',
        '.default',
        Array(
            'entity' => $arResult['VARIABLES']['entity']
        )
    );
}
?>