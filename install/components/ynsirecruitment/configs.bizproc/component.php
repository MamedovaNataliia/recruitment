<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
if (!CModule::IncludeModule('bizproc'))
    return false;
CJSCore::Init(array("jquery"));

$arResult["CONFIGS"] = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_bizproc_config'));

$arResult["ITEMS"] = array();
$arOrder = array(
    'SORT' => 'ASC',
    'NAME' => 'ASC',
);
$arFilter = array(
    'ACTIVE' => 'Y',
    'TYPE' => 'bitrix_processes',
    'CHECK_PERMISSIONS' => 'N',
    'SITE_ID' => 's1',
);
$documentType = array(
    0 => 'ynsirecruitment',
    1 => 'YNSIRDocumentJobOrder',
    2 => YNSIR_JOB_ORDER,
);
$db_res = CBPWorkflowTemplateLoader::GetList(
    array('ID'=> 'ASC'),
    array(
        "DOCUMENT_TYPE" => $documentType,
        '!AUTO_EXECUTE' => CBPDocumentEventType::Automation
    ),
    false,
    false,
    array("ID", "NAME", "DESCRIPTION", "MODIFIED", "USER_ID", "AUTO_EXECUTE", "USER_NAME", "USER_LAST_NAME", "USER_LOGIN", "ACTIVE", "USER_SECOND_NAME"));
if ($db_res) {
    while ($res = $db_res->GetNext()) {
        $arResult["ITEMS"][$res['ID']] = $res['NAME'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['ACTION'] == 'SAVE_CONFIGS') {
    global $APPLICATION;
    $APPLICATION->RestartBuffer();
    $arData = array();
    $result = array();
    $result['SUCCESS'] = 0;
    $err = false;

    if (intval($_POST['YNSIR_BIZ_APPROVE_ORDER_ID']) > 0) {
        $arData['YNSIR_BIZ_APPROVE_ORDER_ID'] = intval($_POST['YNSIR_BIZ_APPROVE_ORDER_ID']);
    } elseif (!isset($arResult["ITEMS"][$arData['YNSIR_BIZ_APPROVE_ORDER_ID']])) {
        $err = true;
        $result['MESSAGE_ERROR']['YNSIR_BIZ_APPROVE_ORDER_ID'] = GetMessage('YNSIR_CONFIG_ERROR_WORFLOW_NOT_EXIST');
    } else {
        $err = true;
        $result['MESSAGE_ERROR']['YNSIR_BIZ_APPROVE_ORDER_ID'] = GetMessage('YNSIR_CONFIG_ERROR_WORFLOW_EMPTY');
    }

    if (intval($_POST['BIZPROC_BIZ_SCAN_CV_ID']) > 0) {
        $arData['BIZPROC_BIZ_SCAN_CV_ID'] = intval($_POST['BIZPROC_BIZ_SCAN_CV_ID']);
    } elseif (!isset($arResult["ITEMS"][$arData['BIZPROC_BIZ_SCAN_CV_ID']])) {
        $err = true;
        $result['MESSAGE_ERROR']['BIZPROC_BIZ_SCAN_CV_ID'] = GetMessage('YNSIR_CONFIG_ERROR_APPROVE_NOT_EXIST');
    } else {
        $err = true;
        $result['MESSAGE_ERROR']['BIZPROC_BIZ_SCAN_CV_ID'] = GetMessage('YNSIR_CONFIG_ERROR_APPROVE_ORDER_ID_EMPTY');
    }
    if (intval($_POST['BIZPROC_BIZ_ONBOARDING_CV_ID']) > 0) {
        $arData['BIZPROC_BIZ_SCAN_CV_ID'] = intval($_POST['BIZPROC_BIZ_ONBOARDING_CV_ID']);
    } elseif (!isset($arResult["ITEMS"][$arData['BIZPROC_BIZ_ONBOARDING_CV_ID']])) {
        $err = true;
        $result['MESSAGE_ERROR']['BIZPROC_BIZ_ONBOARDING_CV_ID'] = GetMessage('YNSIR_CONFIG_ERROR_APPROVE_NOT_EXIST');
    } else {
        $err = true;
        $result['MESSAGE_ERROR']['BIZPROC_BIZ_ONBOARDING_CV_ID'] = GetMessage('YNSIR_CONFIG_ERROR_APPROVE_ORDER_ID_EMPTY');
    }
    if (!$err) {
        COption::SetOptionString("ynsirecruitment", "ynsir_bizproc_config", serialize($arData));
        $result['SUCCESS'] = 1;
        $_SESSION['BIZ_CONFIG_SUCCESS'] = 1;
    }
    echo json_encode($result);
    die;
}

$this->IncludeComponentTemplate();
