<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

CModule::IncludeModule("disk");
CModule::IncludeModule("ynsirecruitment");
CModule::IncludeModule("socialnetwork");
CModule::IncludeModule("socialnetwork");
CUtil::InitJSCore(array("window", "ajax"));

$arResult['ONUPLOADDONE'] = isset($arParams["ONUPLOADDONE"]) ? $arParams["ONUPLOADDONE"] : '';

$arResult['ALLOW_UPLOAD_EXT'] = isset($arParams["ALLOW_UPLOAD_EXT"]) && !empty($arParams["ALLOW_UPLOAD_EXT"]) ? $arParams["ALLOW_UPLOAD_EXT"] : array('docx', 'pdf');

$iIdWorkGroup = YNSIRDisk::rootFolder();
if($iIdWorkGroup <= 0){
    ShowError(GetMessage('YNSIR_CDFU_ERROR_CONFIG_DISK'));
    return;
}
$arResult['TARGET_FOLDER'] = YNSIRDisk::getTempFolder();
YNSIRDisk::shareTempFolder(YNSIRDisk::DEFAULT_SHARE_TYPE_USER, $arResult['TARGET_FOLDER']);
$arResult['BUTTON_MESSAGE'] = isset($arParams['BUTTON_MESSAGE']) ? $arParams['BUTTON_MESSAGE'] : 'Upload';
$arResult['HTML_ELEMENT_ID'] = isset($arParams["HTML_ELEMENT_ID"]) ? $arParams["HTML_ELEMENT_ID"] : $iIdWorkGroup;

$arResult['FILE_MULTIPLE'] = $arParams['FILE_MULTIPLE'] === false ? 0 : 1;
$this->IncludeComponentTemplate();
?>