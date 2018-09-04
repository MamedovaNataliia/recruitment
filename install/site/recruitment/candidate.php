<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
CModule::IncludeModule('ynsirecruitment');
$APPLICATION->IncludeComponent(
    "ynsirecruitment:controll_panel",
    "",
    array(
        'ACTIVE' => YNSIR_CANDIDATE,
    )
);
$APPLICATION->IncludeComponent(
    'ynsirecruitment:candidate',
    '.default',
    Array(
        'SEF_FOLDER' => '/recruitment/candidate/',
        'SEF_MODE' => 'Y',
        'SEF_URL_TEMPLATES' => array(
            'candidate_list' => 'list/',
            'candidate_edit' => 'edit/#id#/',
            'candidate_detail' => 'detail/#id#/',
        )
    )
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>