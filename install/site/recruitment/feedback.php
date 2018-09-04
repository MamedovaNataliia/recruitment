<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
CModule::IncludeModule('ynsirecruitment');

$APPLICATION->IncludeComponent(
    'ynsirecruitment:feedback',
    '.default',
    Array(
        'SEF_FOLDER' => '/recruitment/feedback/',
        'SEF_MODE' => 'Y',
        'SEF_URL_TEMPLATES' => array(
            'feedback_edit' => 'edit/#id#/',
            'feedback_detail' => 'detail/#id#/',
        )
    )
);
?>