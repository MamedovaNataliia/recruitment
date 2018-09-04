<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->AddHeadScript('/bitrix/js/ynsirecruitment/select2.js');
$APPLICATION->SetAdditionalCSS('/bitrix/js/ynsirecruitment/select2.css');
global $APPLICATION, $USER;

//region - General config
CModule::IncludeModule('ynsirecruitment');
$arResult['FORMAT_DB'] = 'YYYY-MM-DD';
$arResult['FORMAT_DB_FULL'] = 'YYYY-MM-DD HH:MI:SS';
$arResult['FORMAT_BX_FULL'] = CSite::GetDateFormat("FULL");
$arResult['FORMAT_BX_SHORT'] = CSite::GetDateFormat("SHORT");
$arResult['ID'] = intval($arParams['VARIABLES']['id']);
$arResult['USER_ID'] = $USER->GetID();
//endregion

//region - Set title and check general permission
$arResult['USER_PERMISSIONS'] = YNSIRPerms_::GetCurrentUserPermissions();
if ($arResult['ID'] > 0) {
    $APPLICATION->SetTitle(GetMessage('YNSIR_CJOD_EDIT_TITLE'));
    // check general permission update
    $isPermitted = YNSIRJobOrder::CheckUpdatePermission($arResult['ID'], $arResult['USER_PERMISSIONS']);
}
else {
    $APPLICATION->SetTitle(GetMessage('YNSIR_CJOD_NEW_TITLE'));
    // check general permission add
    $isPermitted = YNSIRJobOrder::CheckCreatePermission($arResult['ID'], $arResult['USER_PERMISSIONS']);
}
//endregion

if(!$isPermitted){
    ShowError(GetMessage("YNSIR_CJOD_ACCESS_DENIED"));
    return;
}
else {
    //region - Check permission
    $sAction = '';
    if ($arResult['ID'] > 0) {
        $arResult['PERM'][YNSIRConfig::OS_BASIC_INFO] = YNSIRJobOrder::CheckUpdatePermissionSec($arResult['ID'], YNSIRConfig::OS_BASIC_INFO, $arResult['USER_PERMISSIONS']);
        $arResult['PERM'][YNSIRConfig::OS_SENSITIVE] = YNSIRJobOrder::CheckUpdatePermissionSec($arResult['ID'], YNSIRConfig::OS_SENSITIVE, $arResult['USER_PERMISSIONS']);
        $arResult['PERM'][YNSIRConfig::OS_INTERVIEWS] = YNSIRJobOrder::CheckUpdatePermissionSec($arResult['ID'], YNSIRConfig::OS_INTERVIEWS, $arResult['USER_PERMISSIONS']);
        $arResult['PERM'][YNSIRConfig::OS_DESCRIPTION] = YNSIRJobOrder::CheckUpdatePermissionSec($arResult['ID'], YNSIRConfig::OS_DESCRIPTION, $arResult['USER_PERMISSIONS']);
        $arResult['PERM'][YNSIRConfig::OS_APPROVE] = YNSIRJobOrder::CheckUpdatePermissionSec($arResult['ID'], YNSIRConfig::OS_APPROVE, $arResult['USER_PERMISSIONS']);
        //region - Get info job order
        $arResult['DATA'] = YNSIRJobOrder::getById($arResult['ID'], false);
        $arInterview = YNSIRInterview::getListDetail(array(), array('ID' => $arResult['DATA']['INTERVIEW']), false, false, array());
        ksort($arInterview[$arResult['ID']]);
        $arResult['DATA']['INTERVIEW'] = $arInterview[$arResult['ID']];
        $arResult['DATA']['EXPECTED_END_DATE'] = $DB->FormatDate($arResult['DATA']['EXPECTED_END_DATE'], $arResult['FORMAT_BX_FULL'], $arResult['FORMAT_BX_SHORT']);
        // get list id user
        $arUser = array(
            $arResult['USER_ID'],
            $arResult['DATA']['CREATED_BY'],
            $arResult['DATA']['MODIFIED_BY'],
            $arResult['DATA']['SUPERVISOR'],
            $arResult['DATA']['OWNER'],
            $arResult['DATA']['RECRUITER']
        );
        $arUser = array_merge($arUser, $arResult['DATA']['SUBORDINATE']);
        foreach ($arResult['DATA']['INTERVIEW'] as $arRound) {
            $arUser = array_merge($arUser, $arRound['PARTICIPANT']);
        }
        $sAction = 'WRITE';
        //endregion - Get info job order
    } else {
        $arResult['PERM'][YNSIRConfig::OS_BASIC_INFO] = YNSIRJobOrder::CheckCreatePermissionSec($arResult['ID'], YNSIRConfig::OS_BASIC_INFO, $arResult['USER_PERMISSIONS']);
        $arResult['PERM'][YNSIRConfig::OS_SENSITIVE] = YNSIRJobOrder::CheckCreatePermissionSec($arResult['ID'], YNSIRConfig::OS_SENSITIVE, $arResult['USER_PERMISSIONS']);
        $arResult['PERM'][YNSIRConfig::OS_INTERVIEWS] = YNSIRJobOrder::CheckCreatePermissionSec($arResult['ID'], YNSIRConfig::OS_INTERVIEWS, $arResult['USER_PERMISSIONS']);
        $arResult['PERM'][YNSIRConfig::OS_DESCRIPTION] = YNSIRJobOrder::CheckCreatePermissionSec($arResult['ID'], YNSIRConfig::OS_DESCRIPTION, $arResult['USER_PERMISSIONS']);
        $arResult['PERM'][YNSIRConfig::OS_APPROVE] = YNSIRJobOrder::CheckCreatePermissionSec($arResult['ID'], YNSIRConfig::OS_APPROVE, $arResult['USER_PERMISSIONS']);
        $sAction = 'ADD';
        $arResult['DATA']['HEADCOUNT'] = 1;
        
        if(!($arResult['PERM'][YNSIRConfig::OS_BASIC_INFO] == true && $arResult['PERM'][YNSIRConfig::OS_SENSITIVE] == true)){
            ShowError(GetMessage("YNSIR_CJOD_ACCESS_DENIED"));
            return;
        }
    }
    //endregion - Check permission

    //region - Get general info
    $arResult['URL_FORM_SUBMIT'] = '/recruitment/job-order/edit/'.$arResult['ID'].'/';
    $arResult['URL_JO_EDIT'] = '/recruitment/job-order/edit/#id#/';
    $arResult['URL_JO_DETAIL'] = '/recruitment/job-order/detail/#id#/';
    $arResult['DEPARTMENT'] = YNSIRGeneral::getDepartmentPerms(array(), true, $sAction);
    $arResult['TEMPLATE'] = YNSIRJobOrderTemplate::getList(array('ACTIVE' => 1), true);
    $arResult['JO_STATUS'] = YNSIRGeneral::getListJobStatus();
    $arResult['JO_STATUS'] = YNSIRJobOrder::listStatusCanUpdate($arResult['PERM'][YNSIRConfig::OS_APPROVE], $arResult['DATA']['STATUS'], $arResult['JO_STATUS']);
    $arResult['DATA']['OWNER'] = intval($arResult['DATA']['OWNER']) <= 0 ? $arResult['USER_ID'] : $arResult['DATA']['OWNER'];
    $arResult['DATA_USER'] = YNSIRGeneral::getUserInfo($arUser);
    $arResult['TEMPLATE_CATEGORY'] = YNSIRGeneral::getListType(array('ENTITY' => YNSIRConfig::TL_TEMPLATE_CATEGORY), true);
    $arResult['TEMPLATE_CATEGORY_ID'] = intval($arResult['TEMPLATE'][$arResult['DATA']['TEMPLATE_ID']]['CATEGORY']);
    if(!isset($arResult['TEMPLATE_CATEGORY'][$arResult['TEMPLATE_CATEGORY_ID']])){
        $arKeyTCate = array_keys($arResult['TEMPLATE_CATEGORY']);
        $arResult['TEMPLATE_CATEGORY_ID'] = intval($arKeyTCate[0]);
    }
    // =================================================================================================================
    $arResult['LEVEL'] = YNSIRGeneral::getListType(array('ENTITY' => YNSIRConfig::TL_WORK_POSITION), false);
    // =================================================================================================================
    //endregion - Get general info

    //region - Add or update job order
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $APPLICATION->RestartBuffer();
        $arSFRequire = YNSIRConfig::getSectionFieldsJobOrder();
        $arData = $_POST;
        if($arResult['PERM'][YNSIRConfig::OS_BASIC_INFO] == true){
            $arData['STATUS'] = $arResult['ID'] == 0 ? JOStatus::JOSTATUS_NEW : (isset($arData['STATUS']) ? $arData['STATUS'] : $arResult['DATA']['STATUS']);
            $arData['RECRUITER'] = isset($arData['RECRUITER']) ? intval($arData['RECRUITER']) : YNSIRGeneral::getHRManager();
            $arData['OWNER'] = $arResult['ID'] == 0 ? $arResult['USER_ID'] : $arResult['DATA']['OWNER'];
            $arData['EXPECTED_END_DATE'] = $DB->FormatDate($_POST['EXPECTED_END_DATE'], $arResult['FORMAT_BX_SHORT'], $arResult['FORMAT_DB_FULL']);
            $arResult['DATA']['EXPECTED_END_DATE'] = $DB->FormatDate($arResult['DATA']['EXPECTED_END_DATE'], $arResult['FORMAT_BX_SHORT'], $arResult['FORMAT_DB_FULL']);
        }
        //region - Validate
        $arRespond = array();
        foreach ($arSFRequire as $sKeySection => $arSFConfig) {
            foreach ($arSFConfig['FIELD'] as $sKeyField => $sFieldName) {
                // check require
                if((!isset($arData[$sKeyField]) || strlen(trim($arData[$sKeyField])) <= 0) && in_array($sKeyField, $arSFConfig['REQUIRE'])
                    && ($arResult['ID'] == 0 || ($arResult['ID'] > 0 && $arResult['PERM'][$sKeySection] == true))){
                    $arRespond['ERROR'][$sKeyField] = str_replace('#XXX#', $sFieldName, GetMessage('YNSIR_CJOE_REQUIRE_FIELD'));
                    continue;
                }
                // validate data
                switch ($sKeyField) {
                    case 'TITLE':
                        if(isset($arData[$sKeyField])){
                            $arData[$sKeyField] = trim($arData[$sKeyField]);
                            if(strlen($arData[$sKeyField]) <= 10){
                                $arRespond['ERROR'][$sKeyField] = str_replace('#XXX#', $sFieldName, GetMessage('YNSIR_CJOE_REQUIRE_FIELD_TITLE'));
                            }
                        }
                        break;
                    case 'HEADCOUNT':
                        if(isset($arData[$sKeyField])){
                            $arData[$sKeyField] = intval($arData[$sKeyField]);
                            if($arData[$sKeyField] <= 0){
                                $arRespond['ERROR'][$sKeyField] = str_replace('#XXX#', $sFieldName, GetMessage('YNSIR_CJOE_REQUIRE_FIELD_HEADCOUNT'));
                            }
                        }
                        break;
                    case 'SALARY_FROM':
                    case 'SALARY_TO':
                        if(isset($arData[$sKeyField])){
                            $arData[$sKeyField] = str_replace(' ', '', $arData[$sKeyField]);
                            $arData[$sKeyField] = intval($arData[$sKeyField]);
                        }
                        break;
                    default:
                        if(isset($arData[$sKeyField]) && !is_array($arData[$sKeyField])){
                            $arData[$sKeyField] = trim($arData[$sKeyField]);
                        }
                        break;
                }
            }
        }
        if(!isset($arData['OWNER'])){
            $arData['OWNER'] = $arResult['USER_ID'];
        }

        // swap data salary
        if(isset($arData['SALARY_FROM']) && isset($arData['SALARY_TO'])){
            if($arData['SALARY_FROM'] > $arData['SALARY_TO']){
                $iSTemp = $arData['SALARY_TO'];
                $arData['SALARY_TO'] = $arData['SALARY_FROM'];
                $arData['SALARY_FROM'] = $iSTemp;
            }
        }
        //endregion - Validate

        if(empty($arRespond['ERROR'])){
            if($arResult['ID'] == 0){
                $arResult['ID'] = YNSIRJobOrder::Add($arData);
            }
            else {
                $bUpdate = YNSIRJobOrder::Update($arResult['ID'], $arData, $arResult['DATA'], true, $arResult['PERM']);
            }
            if(isset($_POST['JO_ACTION']) && $_POST['JO_ACTION'] == 'SUBMIT' && isset($_POST['AJAX']) && $_POST['AJAX'] = 'Y'){
                $arWFResult = YNSIRBizProc::autoStart($arResult['ID']);
                if(!empty($arWFResult['ERROR'])){
                    $_SESSION[$arResult['ID'].'_SUBMIT'] = $arWFResult['ERROR'];
                }
                else {
                    $_SESSION[$arResult['ID'].'_SUCCESS'] = 1;
                }
            }
            $arRespond['SUCCESS'] = 1;
            $arRespond['ID'] = $arResult['ID'];
        }
        if(isset($_POST['AJAX']) && $_POST['AJAX'] = 'Y'){
            echo json_encode($arRespond);
        }
        else {
            LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['FOLDER'].$arParams['URL_TEMPLATES']['job_order_edit'],
                array(
                    'id' => $arParams['VARIABLES']['id']
                )
            ));
        }
        die;
    }
    //endregion - Add or update job order
    unset($arResult['JO_STATUS'][JOStatus::JOSTATUS_NEW]);
    unset($arResult['JO_STATUS'][JOStatus::JOSTATUS_WAITING]);
    if($arResult['DATA']['SALARY_FROM'] > 0){
        $arResult['DATA']['SALARY_FROM'] = number_format($arResult['DATA']['SALARY_FROM'], 0, '.', ' ');
    }
    if($arResult['DATA']['SALARY_TO'] > 0){
        $arResult['DATA']['SALARY_TO'] = number_format($arResult['DATA']['SALARY_TO'], 0, '.', ' ');
    }
    if(empty($arResult['DATA']['INTERVIEW'] ) && $arResult['ID'] <=0){
        $arResult['DATA']['INTERVIEW'] =array (
            0 =>array ('ROUND_INDEX' => '1','PARTICIPANT' =>array ($USER->GetID() => $USER->GetID())),
            1 =>array ('ROUND_INDEX' => '2','PARTICIPANT' =>array ($USER->GetID() => $USER->GetID()))
        );
    }
    $this->IncludeComponentTemplate();
}
?>