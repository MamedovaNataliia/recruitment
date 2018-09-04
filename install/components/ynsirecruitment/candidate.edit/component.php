<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Disk\File;
global $APPLICATION;
CModule::IncludeModule('ynsirecruitment');
$APPLICATION->AddHeadScript('/bitrix/js/ynsirecruitment/select2.js');
$APPLICATION->SetAdditionalCSS('/bitrix/js/ynsirecruitment/select2.css');

$arResult['FORMAT_DB_TIME'] = 'YYYY-MM-DD';
$arResult['FORMAT_DB_BX_FULL'] = CSite::GetDateFormat("FULL");
$arResult['FORMAT_DB_BX_SHORT'] = CSite::GetDateFormat("SHORT");
$arResult['STATUS_LOCK'] = YNSIRConfig::getCandiateStatusLock();

$arResult['ID'] = intval($arParams['VARIABLES']['id']);
if ($arResult['ID'] > 0)
    $APPLICATION->SetTitle(GetMessage('YNSIR_CCE_EDIT') . ' ' . GetMessage('YNSIR_CCE_TITLE'));
else
    $APPLICATION->SetTitle(GetMessage('YNSIR_CCE_NEW') . ' ' . GetMessage('YNSIR_CCE_TITLE'));
$arResult['CANDIDATE_LOCK'] = YNSIRAssociateJob::checkCandiateLock($arResult['ID']);
$arResult["TITLE"] = GetMessage("YNSIR_CCE_TITLE");
global $USER;
$arResult['USER_ID'] = $USER->GetID();
$arPerms = unserialize(COption::GetOptionString("si_recruiment", "si_perm_all"));

//Check permission

$userPermissions = YNSIRPerms_::GetCurrentUserPermissions();
$bEdit = false;
$bVarsFromForm = false;

if ($arResult['ID'] > 0) {
    $bEdit = true;
}

if ($bEdit) {
    $isPermitted = YNSIRCandidate::CheckUpdatePermission($arResult['ID'], $userPermissions);
    $isPerm[YNSIRConfig::CS_BASIC_INFO] = YNSIRCandidate::CheckUpdatePermissionSec($arResult['ID'], YNSIRConfig::CS_BASIC_INFO, $userPermissions);
    $isPerm[YNSIRConfig::CS_ADDRESS_INFORMATION] = YNSIRCandidate::CheckUpdatePermissionSec($arResult['ID'], YNSIRConfig::CS_ADDRESS_INFORMATION, $userPermissions);
    $isPerm[YNSIRConfig::CS_PROFESSIONAL_DETAILS] = YNSIRCandidate::CheckUpdatePermissionSec($arResult['ID'], YNSIRConfig::CS_PROFESSIONAL_DETAILS, $userPermissions);
    $isPerm[YNSIRConfig::CS_OTHER_INFO] = YNSIRCandidate::CheckUpdatePermissionSec($arResult['ID'], YNSIRConfig::CS_OTHER_INFO, $userPermissions);
    $isPerm[YNSIRConfig::CS_ATTACHMENT_INFORMATION] = YNSIRCandidate::CheckUpdatePermissionSec($arResult['ID'], YNSIRConfig::CS_ATTACHMENT_INFORMATION, $userPermissions);
} else {
    $isPermitted = YNSIRCandidate::CheckCreatePermission($userPermissions);
    $isPerm[YNSIRConfig::CS_BASIC_INFO] = YNSIRCandidate::CheckCreatePermissionSec($userPermissions, YNSIRConfig::CS_BASIC_INFO);
    $isPerm[YNSIRConfig::CS_ADDRESS_INFORMATION] = YNSIRCandidate::CheckCreatePermissionSec($userPermissions, YNSIRConfig::CS_ADDRESS_INFORMATION);
    $isPerm[YNSIRConfig::CS_PROFESSIONAL_DETAILS] = YNSIRCandidate::CheckCreatePermissionSec($userPermissions, YNSIRConfig::CS_PROFESSIONAL_DETAILS);
    $isPerm[YNSIRConfig::CS_OTHER_INFO] = YNSIRCandidate::CheckCreatePermissionSec($userPermissions, YNSIRConfig::CS_OTHER_INFO);
    $isPerm[YNSIRConfig::CS_ATTACHMENT_INFORMATION] = YNSIRCandidate::CheckCreatePermissionSec($userPermissions, YNSIRConfig::CS_ATTACHMENT_INFORMATION);
}
if (!$isPermitted) {
    ShowError("Access denied");
    return;
}

//get KEY section for edit page
$arResult['CANDIDATE']['EDIT_FIELD_SECTION'] = YNSIRConfig::getFieldsIntabEditCandidate();
$arResult['CANDIDATE']['LIST_FIELD_KEYS_PERM'] = array();
$arResult['CANDIDATE']['LIST_FIELD_KEYS_PERM_VALUE'] = array();
foreach ($arResult['CANDIDATE']['EDIT_FIELD_SECTION'] as $SECTION_key => $SECTION_FIELD) {
    if (!$isPerm[$SECTION_key]) {
        unset($arResult['CANDIDATE']['EDIT_FIELD_SECTION'][$SECTION_key]);
    } else {
        /*
         * get Array FIELD KEYS by PERMISSION
         */
        foreach ($SECTION_FIELD['FIELDS'] as $_arFIELDS) {
            foreach ($_arFIELDS as $_FIELD) {
                $arResult['CANDIDATE']['LIST_FIELD_KEYS_PERM'][] = $_FIELD['KEY'];
                $arResult['CANDIDATE']['LIST_FIELD_KEYS_PERM_VALUE'][$_FIELD['KEY']] = $_FIELD;
            }
            unset($_FIELD);
        }
        unset($_FIELDS);
        /*
         * End get Array FIELD KEYS by PERMISSION
         */
    }
}
unset($SECTION_key);
unset($SECTION_FIELD);

$sFormatName = CSite::GetNameFormat(false);

$arResult['CONFIG'] = YNSIRConfig::GetListTypeList();
$arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS] = YNSIRGeneral::getListJobStatus('CANDIDATE_STATUS');
$arResult['CANDIDATE_DATA'] = array();
$arResult['FIELD_CANDIDATE'] = YNSIRConfig::getFieldsCandidate();
$arResult['FIELD_FILE_CANDIDATE'] = YNSIRConfig::getArrayFileFieldSection();
$arResult['SECTION_CANDIDATE'] = YNSIRConfig::getSectionCandidate();

$arResult['DOCUMENT_ID'] = isset($_GET['resume_id']) ? intval($_GET['resume_id']) : 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['ACTION'] == 'CONVERT') {
    if ($_POST['MULTIPLE'] == 'Y') {
        $APPLICATION->RestartBuffer();
        $file = File::loadById($_POST['ID'], array('STORAGE'));

        $arFileInfo = CFile::GetByID($file->getFileId())->Fetch();

        if (!empty($arFileInfo)) {
            $pathinfo = pathinfo($arFileInfo['ORIGINAL_NAME']);
            $file = $_SERVER["DOCUMENT_ROOT"] . '/upload/' . $arFileInfo['SUBDIR'] . '/' . $arFileInfo['FILE_NAME'];
            $newfile = $_SERVER["DOCUMENT_ROOT"] . '/recruitment/uploadcv/' . YNSIRHelper::getSlug($pathinfo['filename']) . '.' . $pathinfo['extension'];
            $filehtml = $_SERVER["DOCUMENT_ROOT"] . '/recruitment/uploadcv/' . YNSIRHelper::getSlug($pathinfo['filename']) . '.html';
            if (!copy($file, $newfile)) {
                json_encode(array('ERROR' => 'Y', 'MESSAGE' => 'File error', 'CANDIDATE_ID' => $iID, 'FILE_ID' => $_POST['ID']));
                die;
            }
            $sPathDocument = $newfile;
            //start parser

            $arDataParser = YNSIRParser::getContentDocument($sPathDocument);
            $ADDITIONAL_VALUE_file = YNSIRHelper::html2text($filehtml);
            $arDataParserTemp = YNSIRParser::parser($ADDITIONAL_VALUE_file);
            //end parser

            if (empty($arDataParser['EMAIL'])) {
                $arDataParser['EMAIL'] = $arDataParserTemp['GENERAL']['Email'];
            }
            if (empty($arDataParser['PHONE'])) {
                $arDataParser['PHONE'] = $arDataParserTemp['GENERAL']['Phone'];
            }
            $arDataParser['PHONE'] = isset($arDataParser['PHONE']) ? $arDataParser['PHONE'] : array();
            $arDataParser['EMAIL'] = isset($arDataParser['EMAIL']) ? $arDataParser['EMAIL'] : array();
            $pathinfo = explode('-', $pathinfo['filename']);
            $_name = trim($pathinfo[1]);
            $_name = explode(' ', $_name);
            $first_name = $_name[count($_name) - 1];
            $last_name = '';

            for ($i = 0; $i < count($_name) - 1; $i++) {
                $last_name .= $_name[$i] . ' ';
            }

            $last_name = strlen($last_name) > 0 ? $last_name : $arDataParser['LAST_NAME'];
            $first_name = strlen($first_name) > 0 ? $first_name : $arDataParser['FIRST_NAME'];

            if (strlen(trim($last_name)) <= 0 || strlen(trim($first_name)) <= 0) {
                echo json_encode(array('ERROR' => 'E', 'MESSAGE' => 'Error - Full Name is empty.', 'CANDIDATE_ID' => 0, 'FILE_ID' => $_POST['ID']));
                die;
            }

            unset($pathinfo[0]);
            unset($pathinfo[1]);

            $source = implode('-', $pathinfo);
            $source = str_replace(' ', '', $source);
            $source = str_replace('-', '', $source);
            $source = strtolower($source);
            $arSource = array();
            foreach ($arResult['CONFIG'][YNSIRConfig::TL_APPLY_POSITION] as $k => $v) {
                $v['NAME_VN'] = str_replace(' ', '', $v['NAME_VN']);
                $v['NAME_VN'] = str_replace('-', '', $v['NAME_VN']);
                $v['NAME_VN'] = strtolower($v['NAME_VN']);


                $v['NAME_EN'] = str_replace(' ', '', $v['NAME_EN']);
                $v['NAME_EN'] = str_replace('-', '', $v['NAME_EN']);
                $v['NAME_EN'] = strtolower($v['NAME_EN']);

                if (trim($v['NAME_VN']) == trim($source) || trim($v['NAME_EN']) == trim($source)) {
                    $arSource[] = $k;
                }
            }
            $arResult['CANDIDATE_DATA']['GENDER'] = $arDataParser['GENDER'];
            $arResult['CANDIDATE_DATA']['FIRST_NAME'] = $first_name;
            $arResult['CANDIDATE_DATA']['LAST_NAME'] = $last_name;



            $arResult['CANDIDATE_DATA']['CANDIDATE_STATUS'] = JOStatus::CDTATUS_NEW;
            $arResult['CANDIDATE_DATA']['LAST_NAME'] = $last_name;
            $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER'] = $USER->GetID();
            $arResult['CANDIDATE_DATA']['CREATED_BY'] = $USER->GetID();
            $arResult['CANDIDATE_DATA']['MODIFIED_BY'] = $USER->GetID();
            // source
            $arDataMulti[] = array('TYPE' => YNSIRConfig::TL_APPLY_POSITION, 'CONTENT' => $arSource);
            // email
            $arDataMulti[] = array('TYPE' => 'EMAIL', 'CONTENT' => $arDataParser['EMAIL']);
            // phone
//            $arDataMulti[] = array('TYPE' => 'PHONE', 'CONTENT' => $arDataParser['PHONE']);
            $arDataMulti[] = array('TYPE' => 'CMOBILE', 'CONTENT' => $arDataParser['PHONE']);

            //check duplicate
            if (!(empty($arDataParser['EMAIL']) && empty($arDataParser['PHONE']))) {
                $dbMultiField = YNSIRCandidate::GetListMultiField(array(), array('CONTENT' => array_merge($arDataParser['EMAIL'], $arDataParser['PHONE'])));
                while ($multiField = $dbMultiField->GetNext()) {
                    echo json_encode(array('ERROR' => 'Y', 'MESSAGE' => GetMessage('YNSIR_CCE_DUPLICATE'), 'CANDIDATE_ID' => $multiField['CANDIDATE_ID'], 'FILE_ID' => $_POST['ID']));
                    die;
                }
            }
            //address
            if (isset($arDataParser['GENERAL']['Address']) && strlen($arDataParser['GENERAL']['Address'][0]) > 0) {
                $arResult['CANDIDATE_DATA']['STREET'] = trim($arDataParser['GENERAL']['Address'][0]);
                // phase address
                $arPhaseAddress = array();
            }
            // skill
            $arResult['CANDIDATE_DATA']['SKILL_SET'] = $arDataParser['SKILL_SET'];
            // other
            $arResult['CANDIDATE_DATA']['ADDITIONAL_INFO'] = $arDataParser['ADDITIONAL_INFO'];
            // File upload
            $arResult['FILE_UPLOAD']['FILE_RESUME'] = array($_POST['ID'] => $_POST['ID']);
            $arResult['CANDIDATE_DATA']['SOURCE_BY'] = YNSIRConfig::IMPORT_FROM_RESUME;
            /*
                         * remove by permission
                         * add by nhatth2
                         *
                         */
            // TODO IMPLEMENT


            /*
             * end remove by permission
             */
            if ($iID == 0)
                $iID = YNSIRCandidate::Add($arResult['CANDIDATE_DATA'], $arDataMulti);
            if ($iID > 0) {
                // insert data b_ynsir_file
                YNSIRFile::compareAndAdd($iID, $arResult['FILE_UPLOAD']);
                if (strlen($first_name) <= 0 || strlen($last_name) <= 0) {
                    echo json_encode(array('ERROR' => 'Y', 'MESSAGE' => 'Failed', 'CANDIDATE_ID' => $iID, 'FILE_ID' => $_POST['ID']));
                    die;
                } else {
                    echo json_encode(array('ERROR' => 'N', 'CANDIDATE_ID' => $iID, 'FILE_ID' => $_POST['ID']));
                }
            } else {
                echo json_encode(array('ERROR' => 'E', 'MESSAGE' => 'Error', 'CANDIDATE_ID' => $iID, 'FILE_ID' => $_POST['ID']));
            }
            die;
        }
    }

}
//import from excel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['ACTION'] == 'IMPORT_EXCEL') {
    $APPLICATION->RestartBuffer();
//    $arFileInfo = CFile::GetByID($_POST['ID']);
//    $arDetailIFile = $arFileInfo->arResult;

    $file = File::loadById($_POST['ID'], array('STORAGE'));

    $arDetailIFile = CFile::GetByID($file->getFileId())->Fetch();


    if (!empty($arDetailIFile)) {
        $pathinfo = pathinfo($arDetailIFile['ORIGINAL_NAME']);

        $file = $_SERVER["DOCUMENT_ROOT"] . '/upload/' . $arDetailIFile['SUBDIR'] . '/' . $arDetailIFile['FILE_NAME'];
        $newfile = $_SERVER["DOCUMENT_ROOT"] . '/recruitment/uploadcv/' . YNSIRHelper::getSlug($pathinfo['filename']) . '.' . $pathinfo['extension'];
        if (!copy($file, $newfile)) {
            json_encode(array('ERROR' => 'Y', 'MESSAGE' => 'File error', 'CANDIDATE_ID' => $iID, 'FILE_ID' => $_POST['ID']));
            die;
        }
        //start parser
        $objPHPExcel = PHPExcel_IOFactory::load($newfile);

        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
        $arHeader = array(
            'FIRST_NAME' => 'First name', // 'B'
            'LAST_NAME' => 'Last name',
            'GENDER' => 'Gender',
            'CMOBILE' => 'Mobile',
            'EMAIL' => 'Email',
            'EXPERIENCE' => 'Experience in Years',
            'CURRENT_JOB_TITLE' => 'Current Job Title',
            'CURRENT_EMPLOYER' => 'Current Employer',
            YNSIRConfig::TL_SOURCES => 'Source',
            YNSIRConfig::TL_APPLY_POSITION => 'Apply Position'
        );
        $arr = array();
        foreach ($arHeader as $key => $value) {
            $arr[$key] = array_search($value, $sheetData[1]);
        }

        unset($sheetData[1]);
        $data = array();
        $result = array();
        foreach ($sheetData as $row) {
            $end = true;
            foreach ($arr as $k => $v) {
                if (isset($row[$v])) {
                    $end = false;
                }
                $rowData[$k] = $row[$v];
            }
            if ($end) break;
            // save candidate
            $arDataMulti = array();
            $arResult['CANDIDATE_DATA']['CANDIDATE_STATUS'] = JOStatus::CDTATUS_NEW;
            // email
            $arDataMulti[] = array('TYPE' => 'EMAIL', 'CONTENT' => array($rowData['EMAIL']));
            // phone
//            $arDataMulti[] = array('TYPE' => 'PHONE', 'CONTENT' => array($rowData['CMOBILE']));
            $arDataMulti[] = array('TYPE' => 'CMOBILE', 'CONTENT' => array($rowData['CMOBILE']));
            //source
            $arResult['CONFIG'];
            $arSource = array();
            foreach ($arResult['CONFIG'][YNSIRConfig::TL_SOURCES] as $k => $v) {
                if (trim($v['NAME_VN']) == trim($rowData[YNSIRConfig::TL_SOURCES]) || trim($v['NAME_EN']) == trim($rowData[YNSIRConfig::TL_SOURCES])) {
                    $arSource[] = $k;
                }
            }
            $arDataMulti[] = array('TYPE' => YNSIRConfig::TL_SOURCES, 'CONTENT' => $arSource);
            //position
            $arPosition = array();
            foreach ($arResult['CONFIG'][YNSIRConfig::TL_APPLY_POSITION] as $k => $v) {
                if (trim($v['NAME_VN']) == trim($rowData[YNSIRConfig::TL_APPLY_POSITION]) || trim($v['NAME_EN']) == trim($rowData[YNSIRConfig::TL_APPLY_POSITION])) {
                    $arPosition[] = $k;
                }
            }
            $arDataMulti[] = array('TYPE' => YNSIRConfig::TL_APPLY_POSITION, 'CONTENT' => $arPosition);
            //current job title
            $arDataMulti[] = array('TYPE' => YNSIRConfig::TL_CURRENT_JOB_TITLE, 'CONTENT' => array($rowData[YNSIRConfig::TL_CURRENT_JOB_TITLE]));

            // add status new

            $rowData['CANDIDATE_STATUS'] = JOStatus::CDTATUS_NEW;
            //check duplicate
            $rowData['EMAIL'] = !empty($rowData['EMAIL']) ? $rowData['EMAIL'] : '';
            $rowData['CMOBILE'] = !empty($rowData['CMOBILE']) ? $rowData['CMOBILE'] : 0;
            //mark import from excel
            $rowData['SOURCE_BY'] = YNSIRConfig::IMPORT_FROM_EXCEL;
            $rowData['CANDIDATE_OWNER'] = $USER->GetID();
            $rowData['CREATED_BY'] = $USER->GetID();
            $rowData['EXPERIENCE'] = intval($rowData['EXPERIENCE']);
            $rowData['MODIFIED_BY'] = $USER->GetID();
            $rowData['CMOBILE'] = !empty($rowData['CMOBILE']) ? $rowData['CMOBILE'] : 0;
            $rowData['GENDER'] = !empty($rowData['GENDER'])?strtoupper(substr($rowData['GENDER'],0,1)):'';
            $dbMultiField = YNSIRCandidate::GetListMultiField(array(), array('CONTENT' => array($rowData['EMAIL'], $rowData['CMOBILE'])));
            $_add = true;
            while ($multiField = $dbMultiField->GetNext()) {
                $_add = false;
                $result['ERROR'][] = array('ERROR' => 'Y', 'MESSAGE' => GetMessage('YNSIR_CCE_DUPLICATE'), 'CANDIDATE_ID' => $multiField['CANDIDATE_ID'], 'CANDIDATE_NAME' => $rowData);
                break;
            }
            unset($rowData['PHONE']);
            unset($rowData['CMOBILE']);
            unset($rowData['EMAIL']);
            unset($rowData['SOURCE']);
            unset($rowData[YNSIRConfig::TL_APPLY_POSITION]);
            unset($rowData[YNSIRConfig::TL_CURRENT_JOB_TITLE]);
            $iID = 0;
            if ($_add)
                $iID = YNSIRCandidate::Add($rowData, $arDataMulti);
            if ($iID > 0) {
                $result['SUCCESS'][] = (array('ERROR' => 'N', 'MESSAGE' => 'Success', 'CANDIDATE_ID' => $iID, 'CANDIDATE_NAME' => $rowData));
            } elseif ($_add) {
                $result['ERROR'][] = array('ERROR' => 'Y', 'MESSAGE' => 'error', 'CANDIDATE_NAME' => $rowData);
            }
        }
        echo json_encode($result);
        die;
    }

}


if ($_SERVER['REQUEST_METHOD'] == 'POST'  && $_POST['ACTION'] == 'SAVE_CANDIDATE') {
    // TODO
    //PERMISSION REMOVE
    //END PERMISSION REMOVE
    $APPLICATION->RestartBuffer();

    $arFieldMulti = YNSIRConfig::getFieldsCandidateMult();
    $arDataMulti = array();
    $arResult['ERROR'] = array();
    $arResult['CANDIDATE_DATA'] = array();
    $arResult['CONFIG']['EMAIL_OPT_OUT'] = 0;

    $isNew = JOStatus::CDTATUS_NEW == $_POST['CANDIDATE_STATUS'] ? true : false;

    foreach ($arResult['FIELD_CANDIDATE'] as $sKey => $sFieldName) {
        if (in_array($sKey, $arResult['CANDIDATE']['LIST_FIELD_KEYS_PERM']) || key_exists($sKey, $arResult['FIELD_FILE_CANDIDATE'])) {
            switch ($sKey) {
                case 'EXPERIENCE':
                    $arResult['CANDIDATE_DATA'][$sKey] = intval($_POST[$sKey]);
                    break;
                case YNSIRConfig::TL_SALT_NAME:
                    break;
                case 'FIRST_NAME':
                case 'LAST_NAME':
                    if (isset($_POST[$sKey]) && strlen(trim($_POST[$sKey])) > 0) {
                        $arResult['CANDIDATE_DATA'][$sKey] = trim($_POST[$sKey]);
                    } else {
                        $arResult['ERROR'][$sKey] = array(
                            'VALUE' => $arResult[$sKey],
                            'ERROR' => GetMessage('YNSIR_IG_TITLE_' . $sKey) . " is not specified.",
                        );
                    }
                    break;
                // multiple list
                case 'MARITAL_STATUS':
                case 'WORK_POSITION':
                case 'CURRENT_FORMER_ISSUE_PLACE':
                case 'ENGLISH_PROFICIENCY':
//                case 'SOURCE':
                case 'EDUCATION':
                case 'MAJOR':
                    $rsLists = YNSIRTypelist::GetList(array('ID' => "ASC"), array('ID' => $_POST[$sKey], 'ENTITY' => $sKey), false);
                    if ($element_list = $rsLists->GetNext()) {
                        $additional_info = YNSIRConfig::resolveListContentType($element_list['ADDITIONAL_INFO']);
                    }
                    if (($additional_info == YNSIRConfig::resolveListContentType(YNSIRConfig::YNSIR_TYPE_LIST_DATE))) {
                        $idate = $DB->FormatDate($_POST[$sKey . '_CONTENT_DATE'], $arResult['FORMAT_DB_BX_FULL'], $arResult['FORMAT_DB_TIME']);
                        $arDataMulti[] = array('TYPE' => $sKey, 'CONTENT' => $_POST[$sKey], 'ADDITIONAL_VALUE' => $idate, 'ADDITIONAL_TYPE' => YNSIRConfig::YNSIR_TYPE_LIST_DATE);

                    } else if (($additional_info == YNSIRConfig::resolveListContentType(YNSIRConfig::YNSIR_TYPE_LIST_NUMBER))) {
                        $arDataMulti[] = array('TYPE' => $sKey, 'CONTENT' => $_POST[$sKey], 'ADDITIONAL_VALUE' => $_POST[$sKey . '_CONTENT_NUMBER'], 'ADDITIONAL_TYPE' => YNSIRConfig::YNSIR_TYPE_LIST_NUMBER);

                    } else if (($additional_info == YNSIRConfig::resolveListContentType(YNSIRConfig::YNSIR_TYPE_LIST_STRING))) {
                        $arDataMulti[] = array('TYPE' => $sKey, 'CONTENT' => $_POST[$sKey], 'ADDITIONAL_VALUE' => $_POST[$sKey . '_CONTENT_STRING'], 'ADDITIONAL_TYPE' => YNSIRConfig::YNSIR_TYPE_LIST_STRING);
                    } else if (($additional_info == YNSIRConfig::resolveListContentType(YNSIRConfig::YNSIR_TYPE_LIST_USER))) {
                        $arDataMulti[] = array('TYPE' => $sKey, 'CONTENT' => $_POST[$sKey], 'ADDITIONAL_VALUE' => $_POST[$sKey . '_CONTENT_USER'], 'ADDITIONAL_TYPE' => YNSIRConfig::YNSIR_TYPE_LIST_USER);
                    }else {
                        $arDataMulti[] = array('TYPE' => $sKey, 'CONTENT' => $_POST[$sKey]);
                    }
                    break;
                //field required whenever change status
                case 'CURRENT_JOB_TITLE':
                case 'CURRENT_EMPLOYER':
                case 'HIGHEST_OBTAINED_DEGREE':
                case 'EMAIL':
                case 'PHONE':
                case 'CMOBILE':
                case 'SOURCE':
                    $rsLists = YNSIRTypelist::GetList(array('ID' => "ASC"), array('ID' => $_POST[$sKey], 'ENTITY' => $sKey), false);
                    if ($element_list = $rsLists->GetNext()) {
                        $additional_info = YNSIRConfig::resolveListContentType($element_list['ADDITIONAL_INFO']);
                    }
                    if (in_array($sKey, $arFieldMulti)) {
                        $isEmpty = true;
                        foreach ($_POST[$sKey] as $k => $v) {
                            if (strlen($v) > 0 && $v != -1) {
                                $isEmpty = false;
                            }
                        }
                        $isFormatPhone = false;
                        if (!$isNew && ($sKey == 'EMAIL')) {
                            foreach ($_POST[$sKey] as $k => $v) {
                                if (!preg_match('/@/', $v) && strlen($v) > 0) {
                                    $arResult['ERROR'][$sKey . '__' . $k] = array(
                                        'VALUE' => $arResult[$sKey],
                                        'ERROR' => GetMessage('YNSIR_ERROR_ADDITIONAL_FIELD', array("#FIELD#" => $arResult['CANDIDATE']['LIST_FIELD_KEYS_PERM_VALUE'][$sKey]['NAME']))
                                    );
                                }
                            }
                        }
                        if (!$isNew && ($sKey == 'CMOBILE')) {
                            foreach ($_POST[$sKey] as $k => $v) {
                                if (!preg_match('/^[0][0-9]{9,10}$/', $v) && strlen($v) > 0) {
                                    $arResult['ERROR'][$sKey . '__' . $k] = array(
                                        'VALUE' => $arResult[$sKey],
                                        'ERROR' => GetMessage('YNSIR_ERROR_ADDITIONAL_FIELD', array("#FIELD#" => $arResult['CANDIDATE']['LIST_FIELD_KEYS_PERM_VALUE'][$sKey]['NAME']))
                                    );
                                }
                            }
                        }
                        if ($isEmpty && !$isNew) {
                            $mark = $k > 1000 ? '__' . $k : '';
                            $arResult['ERROR'][$sKey . $mark] = array(
                                'VALUE' => $arResult[$sKey],
                                'ERROR' => GetMessage('YNSIR_IG_TITLE_' . $sKey) . " is not specified. Whenever changing status",
                            );
                        } else {
                            if (($additional_info == YNSIRConfig::resolveListContentType(YNSIRConfig::YNSIR_TYPE_LIST_DATE))) {
                                $idate = $DB->FormatDate($_POST[$sKey . '_CONTENT_DATE'], $arResult['FORMAT_DB_BX_FULL'], $arResult['FORMAT_DB_TIME']);
                                $arDataMulti[] = array('TYPE' => $sKey, 'CONTENT' => $_POST[$sKey], 'ADDITIONAL_VALUE' => $idate, 'ADDITIONAL_TYPE' => YNSIRConfig::YNSIR_TYPE_LIST_DATE);

                            } else if (($additional_info == YNSIRConfig::resolveListContentType(YNSIRConfig::YNSIR_TYPE_LIST_NUMBER))) {
                                $arDataMulti[] = array('TYPE' => $sKey, 'CONTENT' => $_POST[$sKey], 'ADDITIONAL_VALUE' => $_POST[$sKey . '_CONTENT_NUMBER'], 'ADDITIONAL_TYPE' => YNSIRConfig::YNSIR_TYPE_LIST_NUMBER);

                            } else if (($additional_info == YNSIRConfig::resolveListContentType(YNSIRConfig::YNSIR_TYPE_LIST_STRING))) {
                                $arDataMulti[] = array('TYPE' => $sKey, 'CONTENT' => $_POST[$sKey], 'ADDITIONAL_VALUE' => $_POST[$sKey . '_CONTENT_STRING'], 'ADDITIONAL_TYPE' => YNSIRConfig::YNSIR_TYPE_LIST_STRING);
                            } else if (($additional_info == YNSIRConfig::resolveListContentType(YNSIRConfig::YNSIR_TYPE_LIST_USER))) {
                                $arDataMulti[] = array('TYPE' => $sKey, 'CONTENT' => $_POST[$sKey], 'ADDITIONAL_VALUE' => $_POST[$sKey . '_CONTENT_USER'], 'ADDITIONAL_TYPE' => YNSIRConfig::YNSIR_TYPE_LIST_USER);
                            }else {
                                $arDataMulti_ = array_unique($_POST[$sKey]);
                                $arDataMulti[] = array('TYPE' => $sKey, 'CONTENT' => $_POST[$sKey]);
                            }
                        }
                    } else {
                        //CURRENT_EMPLOYER
                        if (strlen($_POST[$sKey]) <= 0 && !$isNew) {

                            $arResult['ERROR'][$sKey] = array(
                                'VALUE' => $arResult[$sKey],
                                'ERROR' => GetMessage('YNSIR_IG_TITLE_' . $sKey) . " is not specified. Whenever changing status",
                            );
                        } else {
                            $arResult['CANDIDATE_DATA'][$sKey] = trim($_POST[$sKey]);
                        }
                    }
                    break;
                case 'FILE_RESUME':
                case 'FILE_FORMATTED_RESUME':
                case 'FILE_COVER_LETTER':
                case 'FILE_OTHERS':
//                    if($_POST[$sKey][0] <= 0) break;
                    $arFileInfo = CFile::GetByID($_POST[$sKey][0]);
                    $arDetailIFile = $arFileInfo->arResult;
                    $pathinfo = pathinfo($arDetailIFile[0]['FILE_NAME']);

                    $file = $_SERVER["DOCUMENT_ROOT"] . '/upload/' . $arDetailIFile[0]['SUBDIR'] . '/' . $arDetailIFile[0]['FILE_NAME'];
                    $newfile = $_SERVER["DOCUMENT_ROOT"] . '/recruitment/uploadcv/' . YNSIRHelper::getSlug($pathinfo['filename']) . '.' . $pathinfo['extension'];
                    if (!copy($file, $newfile)) {
                    }
                    $arResult['FILE_UPLOAD'][$sKey] = array();
                    if (isset($_POST[$sKey]) && is_array($_POST[$sKey]) && !empty($_POST[$sKey])) {
                        $arResult['FILE_UPLOAD'][$sKey] = $_POST[$sKey];
                    }
                    break;
                case 'EMAIL_OPT_OUT':
                    if (isset($_POST[$sKey]) && $_POST[$sKey] == 'Y') {
                        $arResult['CANDIDATE_DATA'][$sKey] = 1;
                    } else {
                        $arResult['CANDIDATE_DATA'][$sKey] = 0;
                    }
                    break;
                case 'DOB':
                    if (isset($_POST[$sKey]) && strlen($_POST[$sKey]) > 0) {
                        if (!CheckDateTime($_POST[$sKey], $arResult['FORMAT_DB_BX_SHORT'])) {
                            $arResult['ERROR'][$sKey . $mark] = array(
                                'VALUE' => $arResult[$sKey],
                                'ERROR' => GetMessage('YNSIR_ERROR_ADDITIONAL_FIELD', array("#FIELD#" => $arResult['CANDIDATE']['LIST_FIELD_KEYS_PERM_VALUE'][$sKey]['NAME']))
                            );
                        } else {
                            $DOB = $DB->FormatDate($_POST['DOB'], $arResult['FORMAT_DB_BX_FULL'], $arResult['FORMAT_DB_TIME']);
                            $arResult['CANDIDATE_DATA'][$sKey] = trim($DOB);
                        }
                    } else {
                        $arResult['CANDIDATE_DATA'][$sKey] = '';
                    }
                    break;
                default:

                    if (isset($_POST[$sKey]) && strlen(trim($_POST[$sKey])) > 0) {
                        $arResult['CANDIDATE_DATA'][$sKey] = trim($_POST[$sKey]);
                    } else {
                        $arResult['CANDIDATE_DATA'][$sKey] = '';
                    }
                    break;
            }
        }
    }
    // extend data
    $iID = intval($_POST['CANDIDATE_ID']);
    $dbMultiField = YNSIRCandidate::GetListMultiField(array(), array('CONTENT' => array_merge($_POST['EMAIL'], $_POST['CMOBILE'])));
    while ($multiField = $dbMultiField->GetNext()) {
        if($multiField['CANDIDATE_ID'] == $iID) continue;
        while (array_search($multiField['CONTENT'], $_POST['EMAIL']) && $multiField['TYPE'] == 'EMAIL') {
            $key_mark = array_search($multiField['CONTENT'], $_POST['EMAIL']);
            $arResult['ERROR']['EMAIL__' . $key_mark] = array(
                'CANDIDATE_ID' => $multiField['CANDIDATE_ID'],
                'VALUE' => $multiField['CONTENT'],
                'ERROR' => 'Email is existing. <a href="/recruitment/candidate/detail/' . $multiField['CANDIDATE_ID'] . '/">this</a>',
            );
            unset($_POST['EMAIL'][$key_mark]);
        }
        while (array_search($multiField['CONTENT'], $_POST['CMOBILE']) && $multiField['TYPE'] == 'CMOBILE') {
            $key_mark = array_search($multiField['CONTENT'], $_POST['CMOBILE']);
            $arResult['ERROR']['CMOBILE__' . $key_mark] = array(
                'CANDIDATE_ID' => $multiField['CANDIDATE_ID'],
                'VALUE' => $multiField['CONTENT'],
                'ERROR' => 'Mobile is existing. <a href="/recruitment/candidate/detail/' . $multiField['CANDIDATE_ID'] . '/">this</a>',
            );
            unset($_POST['CMOBILE'][$key_mark]);
        }

    }


    if (empty($arResult['ERROR'])) {
        $arResult['CANDIDATE_DATA']['CREATED_DATE'] = "'" . date('Y-m-d H:i:s') . "'";
        if ($arResult['ID'] == 0) {
            $arResult['CANDIDATE_DATA']['CREATED_BY'] = $arResult['USER_ID'];
            $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER'] = $arResult['USER_ID'];
        }
        $arResult['CANDIDATE_DATA']['MODIFIED_BY'] = $arResult['USER_ID'];
        // insert data b_ynsir_candidate
        $arResult['CANDIDATE_DATA']['EXPECTED_SALARY'] = str_replace(' ','',$arResult['CANDIDATE_DATA']['EXPECTED_SALARY']);
        $arResult['CANDIDATE_DATA']['CURRENT_SALARY'] = str_replace(' ','',$arResult['CANDIDATE_DATA']['CURRENT_SALARY']);
        if ($iID > 0) {
            //Unset Status if it was lock
            if(in_array($arResult['CANDIDATE_DATA']['CANDIDATE_STATUS'],$arResult['STATUS_LOCK'])) {
                $checkLockAssociate = YNSIRAssociateJob::checkCandiateLock($iID);
                if ($checkLockAssociate['IS_LOCK'] == 'Y') {
                    unset($arResult['CANDIDATE_DATA']['CANDIDATE_STATUS']);
                }
                unset($checkLockAssociate);
            }
            //END
            $iUpdated = YNSIRCandidate::Update($iID, $arResult['CANDIDATE_DATA'], true, $arDataMulti);
            if ($iUpdated == 0)
                $iID = 0;
        }
        if ($iID == 0)
            $iID = YNSIRCandidate::Add($arResult['CANDIDATE_DATA'], $arDataMulti);
        // insert data b_ynsir_file
        if ($iID > 0) {
            //delete file
            $arFileUpload = array();
            foreach ($arResult['FILE_UPLOAD'] as $k=>$v){
                $arFileUpload = array_merge($arFileUpload,array_values($v));
            }
            $arFileofCandidate = YNSIRFile::getListById($arResult['ID']);
            $arFileUploaded = array();
            foreach ($arFileofCandidate as $k=>$v){
                $arFileUploaded = array_merge($arFileUploaded,array_keys($v));
            }

            $arFileDelete = array_diff($arFileUploaded,$arFileUpload);

            YNSIRFile::compareAndAdd($iID, $arResult['FILE_UPLOAD'], true);
            foreach ($arFileDelete as $k => $id){
                YNSIRDisk::actionDeleteFile($id);
            }
        }
        YNSIRCacheHelper::ClearCached(YNSIRCandidate::FREFIX_CACHE.$iID,YNSIRCandidate::ACTIVITY_CACHE_URL);
        echo json_encode(array('ERROR' => 'N', 'CANDIDATE_ID' => $iID));
    } else {
        echo json_encode(array('ERROR' => 'Y', 'MESS' => $arResult['ERROR']));
    }
    die;
}

if ($arResult['ID'] > 0) {
    $arSelect = array('ACTIVITY_ID');
    if (in_array('ACTIVITY_ID', $arSelect, true)) {
        $arSelect[] = 'ACTIVITY_TIME';
        $arSelect[] = 'ACTIVITY_SUBJECT';
        $arSelect[] = 'C_ACTIVITY_ID';
        $arSelect[] = 'C_ACTIVITY_TIME';
        $arSelect[] = 'C_ACTIVITY_SUBJECT';
        $arSelect[] = 'C_ACTIVITY_RESP_ID';
        $arSelect[] = 'C_ACTIVITY_RESP_LOGIN';
        $arSelect[] = 'C_ACTIVITY_RESP_NAME';
        $arSelect[] = 'C_ACTIVITY_RESP_LAST_NAME';
        $arSelect[] = 'C_ACTIVITY_RESP_SECOND_NAME';
    }

    if (in_array('ACTIVITY_ID', $arSelect, true)) {
        $arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
    }
    $resCache = YNSIRCacheHelper::GetCached(YNSIRCandidate::ACTIVITY_CACHE_TIME, YNSIRCandidate::FREFIX_CACHE . $arResult['ID'], YNSIRCandidate::ACTIVITY_CACHE_URL);
    if (is_array($resCache) && !empty($resCache['DATA']) && false) { // not use cache
        $arResult['CANDIDATE_DATA']  =  $resCache['DATA'];
    } else {
        $res = YNSIRCandidate::GetListCandidateNonePerms(
            array(),
            array('ID' => $arResult['ID']),
            array(),
            $arOptions,
            $arSelect
        );

        if ($arProfile = $res->GetNext()) {
            $arProfile['MODIFIED_DATE_SHORT'] = FormatDate('x', MakeTimeStamp($arProfile['MODIFIED_DATE']), (time() + CTimeZone::GetOffset()));
            $arResult['CANDIDATE_DATA'] = $arProfile;
        } else {
            ShowError(GetMessage("YNSIR_EDIT_ELEMENT_NOT_FOUND"));
            return;
        }
        $arResult['CANDIDATE_DATA']['NAME'] = CUser::FormatName(
            $sFormatName,
            array(
                "NAME" => $arResult['CANDIDATE_DATA']['FIRST_NAME'],
                "LAST_NAME" => $arResult['CANDIDATE_DATA']['LAST_NAME'],
            )
        );
        $arResult['CANDIDATE_DATA']['FULL_NAME'] = $arResult['CANDIDATE_DATA']['NAME'];
        $arResult['CANDIDATE_DATA']['NAME'] = $arResult['CANDIDATE_DATA']['SALT_NAME'] . $arResult['CANDIDATE_DATA']['NAME'];


//        $arOWNERTOOLTIP = YNSIRHelper::getTooltipandPhotoUser($arResult['CANDIDATE']['CANDIDATE_OWNER'], 'CANDIDATE_OWNER');;
//        $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER_ID'] = $arResult['CANDIDATE']['CANDIDATE_OWNER'];
//        $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER'] = $arOWNERTOOLTIP['TOOLTIP'];
//        $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER_PHOTO_URL'] = $arOWNERTOOLTIP['PHOTO_URL'];

        $arCREATBYTOOLTIP = YNSIRHelper::getTooltipandPhotoUser($arResult['CANDIDATE']['CREATED_BY'], 'CREATED_BY');
        $arResult['CANDIDATE_DATA']['CREATED_BY'] = $arCREATBYTOOLTIP['TOOLTIP'];
        $arResult['CANDIDATE_DATA']['CREATED_BY_PHOTO_URL'] = $arCREATBYTOOLTIP['PHOTO_URL'];



        $arMODIFIBYTOOLTIP = YNSIRHelper::getTooltipandPhotoUser($arResult['CANDIDATE']['MODIFIED_BY'], 'MODIFIED_BY');
        $arResult['CANDIDATE_DATA']['MODIFIED_BY'] = $arMODIFIBYTOOLTIP['TOOLTIP'];
        $arResult['CANDIDATE_DATA']['MODIFIED_BY_PHOTO_URL'] = $arMODIFIBYTOOLTIP['PHOTO_URL'];

        //FILE
        $arResult['CANDIDATE_DATA']['FILE_UPLOAD'] = YNSIRFile::getListById($arResult['CANDIDATE_DATA']['ID']);
        //TODO: SAVE CACHE
        //YNSIRCacheHelper::SetCached($arResult['CANDIDATE'], YNSIRCandidate::ACTIVITY_CACHE_TIME, YNSIRCandidate::FREFIX_CACHE . $ID, YNSIRCandidate::ACTIVITY_CACHE_URL);

    }
}
//TODO: GET FIELD MULTIPLE
$dbMultiField = YNSIRCandidate::GetListMultiField(array(), array('CANDIDATE_ID' => $arResult['ID']));
while ($multiField = $dbMultiField->GetNext()) {
    $arResult['CANDIDATE_DATA'][$multiField['TYPE']][] = $multiField;
}
//end TODO: Get Field Multiple

$arResult['CANDIDATE_DATA']['CANDIDATE_OWNER'] = intval($arResult['CANDIDATE_DATA']['CANDIDATE_OWNER']);

$arResult['CANDIDATE_DATA']['NAME_OWNER'] = '';
if ($arResult['CANDIDATE_DATA']['CANDIDATE_OWNER'] > 0) {
    $rsUser = CUser::GetByID($arResult['CANDIDATE_DATA']['CANDIDATE_OWNER']);
    $arUser = $rsUser->Fetch();
    $arResult['CANDIDATE_DATA']['NAME_OWNER'] = CUser::FormatName(
        $sFormatName,
        array(
            "NAME" => $arUser['NAME'],
            "LAST_NAME" => $arUser['LAST_NAME'],
            "SECOND_NAME" => $arUser['SECOND_NAME'],
        )
    );
}

$arResult["URL_LIST_CANDIDATE"] = $arParams['FOLDER'] . $arParams['URL_TEMPLATES']['candidate_list'];
$arResult["URL_EDIT_CANDIDATE"] = $arParams['FOLDER'] . str_replace('#id#', $arParams['VARIABLES']['id'], $arParams['URL_TEMPLATES']['candidate_edit']);


$this->IncludeComponentTemplate();

?>