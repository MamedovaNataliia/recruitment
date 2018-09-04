<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
CModule::IncludeModule('ynsirecruitment');
$APPLICATION->IncludeComponent(
    "ynsirecruitment:controll_panel",
    "",
    array(
        'ACTIVE' => YNSIR_JOB_ORDER,
    )
);
$APPLICATION->IncludeComponent(
    'ynsirecruitment:job_order',
    '.default',
    Array(
        'SEF_FOLDER' => '/recruitment/job-order/',
        'SEF_MODE' => 'Y',
        'SEF_URL_TEMPLATES' => array(
            'job_order_list' => 'list/',
            'job_order_edit' => 'edit/#id#/',
            'job_order_detail' => 'detail/#id#/',
        )
    )
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>