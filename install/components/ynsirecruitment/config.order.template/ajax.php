<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->RestartBuffer();
CModule::IncludeModule('ynsirecruitment');

$YNSIRPerms = YNSIRPerms::havePermsConfig();
$bAddFromJobOrder = isset($_POST['SAVE_FROM_JOB_ORDER']) ? true : false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['SAVE_TEMPLATE'])) {
    $arResult = array();
    if ($YNSIRPerms == true || $bAddFromJobOrder == true) {
        $iId = intval($_POST['ID_TEMPLATE']);
        $arData['NAME_TEMPLATE'] = $_POST['NAME_TEMPLATE'];
        $arData['ACTIVE'] = $bAddFromJobOrder == true ? 0 : intval($_POST['ACTIVE']);
        $arData['CONTENT_TEMPLATE'] = $_POST['CONTENT_TEMPLATE'];
        $arData['CATEGORY'] = intval($_POST['CATEGORY']);
        // category
        $arCategory = YNSIRGeneral::getListType(array('ENTITY' => YNSIRConfig::TL_TEMPLATE_CATEGORY), true);
        // check name existed
        $arTemplates = YNSIRJobOrderTemplate::getList(array('NAME_TEMPLATE' => $arData['NAME_TEMPLATE']));
        if (!empty($arTemplates) && (count($arTemplates) > 2 || $arTemplates[0]['ID'] != $iId)) {
            $arResult['ERROR'] = GetMessage("YNSIR_COT_C_NAME_EXISTED", array('#XXX#' => $arData['NAME_TEMPLATE']));
        } else if (!isset($arCategory[$arData['CATEGORY']])) {
            $arResult['ERROR'] = GetMessage("YNSIR_COT_C_CATEGORY_NOT_FOUND");
        } else {
            $iResult = YNSIRJobOrderTemplate::addOrUpdate($arData, $iId);
            if ($iResult > 0) {
                $p = new blogTextParser();
                $arData['CONTENT_HTML'] = $p->convert($arData['CONTENT_TEMPLATE']);
                $arResult = array(
                    'DATA' => array(
                        'ID' => $iResult,
                        'NAME_TEMPLATE' => $arData['NAME_TEMPLATE'],
                        'CONTENT_HTML' => $arData['CONTENT_HTML'],
                        'ACTIVE' => $arData['ACTIVE']
                    )
                );
                // notify
                if ($bAddFromJobOrder == true) {
                    YNSIRGeneral::sendNotifyJOTemplate(array(
                        'ID_TEMPLATE' => $iResult,
                        'NAME_TEMPLATE' => $arData['NAME_TEMPLATE'],
                        'CATEGORY' => $arData['CATEGORY'],
                    ));
                }
            }
        }
    } else {
        $arResult['ERROR'] = GetMessage("YNSIR_COT_C_PERMISSION_DENIED");
    }
    echo json_encode($arResult);
} else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['DELETE_TEMPLATE'])) {
    $arResult = array('ERROR' => 'Not found template.', 'SUCCESS' => 0);
    if ($YNSIRPerms == false) {
        $arResult['ERROR'] = GetMessage("YNSIR_COT_C_PERMISSION_DENIED");
    } else {
        $iId = intval($_POST['ID_TEMPLATE']);
        if ($iId > 0) {
            $iResult = YNSIRJobOrderTemplate::delete($iId);
            if ($iResult == 1)
                $arResult = array('SUCCESS' => 1, 'ERROR' => '');
        }
    }
    echo json_encode($arResult);
} else {
    $iId = intval($_REQUEST['id']);
    $arResult = $iId > 0 ? YNSIRJobOrderTemplate::getList(array('ID' => $iId)) : array();
    $template = $iId > 0 ? $arResult[0]['CONTENT_TEMPLATE'] : '';
    if (strlen($template) <= 0) {
        $rsLists = YNSIRTypelist::GetList(array('ID' => "ASC"), array('ENTITY' => YNSIRConfig::TL_SECTION_JO_DESCRIPTION), true);
        while ($element_list = $rsLists->GetNext()) {
            $template .= '[COLOR=#555555][B]'.$element_list['NAME_'.strtoupper(LANGUAGE_ID)].'[/B][/COLOR][LIST]'.'[*]item[/LIST]';
        }
    }


    $APPLICATION->IncludeComponent(
        "bitrix:main.post.form",
        "",
        ($formParams = Array(
            "FORM_ID" => "JOB_ORDER_TEMPLATE",
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
                "NAME" => "JOB_ORDER_TEMPLATE",
                "VALUE" => $template,
                "HEIGHT" => "500px"),
            "PROPERTIES" => array(),
        )),
        false,
        Array("HIDE_ICONS" => "Y")
    );
}
?>