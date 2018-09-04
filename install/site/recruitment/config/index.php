<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
CModule::IncludeModule('ynsirecruitment');
$APPLICATION->IncludeComponent(
    "ynsirecruitment:controll_panel",
    "",
    array(
        'ACTIVE' => YNSIR_SETTING,
    )
);
$APPLICATION->IncludeComponent(
    'ynsirecruitment:configs',
    '.default',
    Array(
    	'SEF_FOLDER' => '/recruitment/config/',
    	'SEF_MODE' => 'Y',
        'SEF_URL_TEMPLATES' => array(
            'bizproc' => 'bizproc/',
            'hr_mananger' => 'hr_mananger/',
            'perms_relation' => 'perms/',
            'role_edit' => 'perms/#role_id#/edit',
            'list' => 'lists/#entity#/',
        )
    )
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>