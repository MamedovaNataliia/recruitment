<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->RestartBuffer();
CModule::IncludeModule('ynsirecruitment');

if(isset($_REQUEST['get_template_content'])){
    $iId = intval($_REQUEST['id']);
    $arResult = $iId > 0 ? YNSIRJobOrderTemplate::getList(array("ID" => $iId)) : array();
    $APPLICATION->IncludeComponent(
        "bitrix:main.post.form",
        "",
        ($formParams = Array(
            "FORM_ID" => "DESCRIPTION",
            "SHOW_MORE" => "Y",
            'PARSER' => array(
                'Bold', 'Italic', 'Underline', 'Strike',
                'ForeColor', 'FontList', 'FontSizeList', 'RemoveFormat',
                'Quote', 'Code', 'InsertCut',
                'CreateLink', 'Image', 'Table', 'Justify',
                'InsertOrderedList', 'InsertUnorderedList',
                'SmileList', 'Source', 'UploadImage', 'InputVideo', 'MentionUser'
            ),
            "TEXT" => Array(
                "NAME" => "DESCRIPTION",
                "VALUE" => $iId > 0 ? $arResult[0]['CONTENT_TEMPLATE'] : '',
                "HEIGHT" => "500px"),
            "PROPERTIES" => array(),
        )),
        false,
        Array("HIDE_ICONS" => "Y")
    );
}
?>