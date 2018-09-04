<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
$APPLICATION->SetTitle(GetMessage("YNSIR_COT_C_TITLE"));
$YNSIRPerms = YNSIRPerms::havePermsConfig();
if($YNSIRPerms == false){
    ShowError(GetMessage('YNSIR_COT_C_PERMISSION_DENIED'));
    return;
}

$arResult['ACCESS_PERMS'] = 1;
//======CONFIG
$arResult['CONFIG']['LIST_SORT']['SORT_BY'] = array(
    'ID' => GetMessage('YNSIR_CONFIG_ID'),
    'NAME_EN' => GetMessage('YNSIR_CONFIG_NAME'),
    'CREATED_DATE' => GetMessage('YNSIR_CONFIG_DATE_CREATED'),
    'MODIFIED_DATE' => GetMessage('YNSIR_CONFIG_DATE_MODIFIED'),
);
$arResult['CONFIG']['LIST_SORT']['ORDER_BY'] = array(
    'DESC' => GetMessage('YNSIR_CONFIG_ORDER_BY_DESC'),
    'ASC' => GetMessage('YNSIR_CONFIG_ORDER_BY_ASC'),
);
$arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS] = YNSIRGeneral::getListJobStatus('CANDIDATE_STATUS');
//======ENd CONFIG

$arResult['DATA']['LIST_SORT'] = Unserialize(COption::GetOptionString("ynsirecruitment", "ynsir_list_sort"));
$arResult['DATA']['SHOW_NOTE_SALARY'] = COption::GetOptionString("ynsirecruitment", "ynsir_order_salary_note");
$arResult['DATA']['CANDIDATE_STATUS'] = Unserialize(COption::GetOptionString("ynsirecruitment", "ynsir_candidate_status"));
$arResult['DATA']['ON_BOARDING_STATUS'] = Unserialize(COption::GetOptionString("ynsirecruitment", "ynsir_onboarding_status"));
CUtil::InitJSCore();



if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['ACTION'] == 'SAVE_CONFIGS') {
    global $APPLICATION;
    $APPLICATION->RestartBuffer();
    $arDataBiz = array();
    $result = array();
    $result['SUCCESS'] = 0;
    $err = false;

    if (intval($_POST['YNSIR_BIZ_APPROVE_ORDER_ID']) > 0) {
        $arDataBiz['YNSIR_BIZ_APPROVE_ORDER_ID'] = intval($_POST['YNSIR_BIZ_APPROVE_ORDER_ID']);
    } else {
        $arDataBiz['YNSIR_BIZ_APPROVE_ORDER_ID'] = 0;
    }
//    elseif (!isset($arResult["ITEMS"][$arDataBiz['YNSIR_BIZ_APPROVE_ORDER_ID']])) {
//        $err = true;
//        $result['MESSAGE_ERROR']['YNSIR_BIZ_APPROVE_ORDER_ID'] = GetMessage('YNSIR_CONFIG_ERROR_WORFLOW_NOT_EXIST');
//    } else {
//        $err = true;
//        $result['MESSAGE_ERROR']['YNSIR_BIZ_APPROVE_ORDER_ID'] = GetMessage('YNSIR_CONFIG_ERROR_WORFLOW_EMPTY');
//    }

    if (intval($_POST['BIZPROC_BIZ_SCAN_CV_ID']) > 0) {
        $arDataBiz['BIZPROC_BIZ_SCAN_CV_ID'] = intval($_POST['BIZPROC_BIZ_SCAN_CV_ID']);
    } else {
        $arDataBiz['BIZPROC_BIZ_SCAN_CV_ID'] = 0;
    }
    if (intval($_POST['BIZPROC_BIZ_ONBOARDING_ID']) > 0) {
        $arDataBiz['BIZPROC_BIZ_ONBOARDING_ID'] = intval($_POST['BIZPROC_BIZ_ONBOARDING_ID']);
    } else {
        $arDataBiz['BIZPROC_BIZ_ONBOARDING_ID'] = 0;
    }
//    elseif (!isset($arResult["ITEMS"][$arDataBiz['BIZPROC_BIZ_SCAN_CV_ID']])) {
//        $err = true;
//        $result['MESSAGE_ERROR']['BIZPROC_BIZ_SCAN_CV_ID'] = GetMessage('YNSIR_CONFIG_ERROR_SCAN_WF_NOT_EXIST');
//    } else {
//        $err = true;
//        $result['MESSAGE_ERROR']['BIZPROC_BIZ_SCAN_CV_ID'] = GetMessage('YNSIR_CONFIG_ERROR_SCAN_WF_ID_EMPTY');
//    }
    //CANDIDATE STATUS ===============>
//    approve
    if (strlen($_POST['ACCEPT_OFFER_STATUS']) > 0) {
        if (!isset($arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS][$_POST['ACCEPT_OFFER_STATUS']])) {
            $err = true;
            $result['MESSAGE_ERROR']['ACCEPT_OFFER_STATUS'] = GetMessage('YNSIR_CONFIG_ERROR_ACCEPT_OFFER_STATUS_NOT_EXIST');
        } else {
            $arDataStatus['ACCEPT_OFFER_STATUS'] = $_POST['ACCEPT_OFFER_STATUS'];
        }
    } else {
        $err = true;
        $result['MESSAGE_ERROR']['ACCEPT_OFFER_STATUS'] = GetMessage('YNSIR_CONFIG_ERROR_ACCEPT_OFFER_STATUS_EMPTY');
    }

    //reject
    if (strlen($_POST['REJECT_OFFER_STATUS']) > 0) {
        if (!isset($arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS][$_POST['REJECT_OFFER_STATUS']])) {
            $err = true;
            $result['MESSAGE_ERROR']['REJECT_OFFER_STATUS'] = GetMessage('YNSIR_CONFIG_ERROR_REJECT_OFFER_STATUS_NOT_EXIST');
        } elseif($_POST['REJECT_OFFER_STATUS'] == $_POST['ACCEPT_OFFER_STATUS']) {
            $err = true;
            $result['MESSAGE_ERROR']['REJECT_OFFER_STATUS'] = GetMessage('YNSIR_CONFIG_ERROR_REJECT_OFFER_STATUS_DUPPLICATE');
        } else {
            $arDataStatus['REJECT_OFFER_STATUS'] = $_POST['REJECT_OFFER_STATUS'];
        }
    } else {
        $err = true;
        $result['MESSAGE_ERROR']['REJECT_OFFER_STATUS'] = GetMessage('YNSIR_CONFIG_ERROR_REJECT_OFFER_STATUS_EMPTY');
    }

    //END CANDIDATE STATUS <=============
    //HR MANAGEMENT
    $arDataHrManager = array();
    foreach ($_POST['DATA']['HR_MANAGER'] as $k => $v) {
        $id = intval(str_replace('U', '', $k));
        if ($id > 0) {
            $arDataHrManager[] = $id;
        }
    }
    //Recruitment MANAGEMENT
    $arDataRecruitmentManager = array();

    foreach ($_POST['DATA']['RECRUITMENT_MANAGER'] as $k => $v) {
        $id = intval(str_replace('U', '', $k));
        if ($id > 0) {
            $arDataRecruitmentManager[] = $id;
        }
    }
    //LIST SHORT
    if (isset($_POST['LIST_SORT_BY']) && isset($_POST['LIST_SORT_ORDER'])) {
        if (key_exists($_POST['LIST_SORT_BY'], $arResult['CONFIG']['LIST_SORT']['SORT_BY'])
            && key_exists($_POST['LIST_SORT_ORDER'], $arResult['CONFIG']['LIST_SORT']['ORDER_BY'])) {
            $arDataListSort[$_POST['LIST_SORT_BY']] = $_POST['LIST_SORT_ORDER'];
        } else {
            $arDataListSort = array();
        }
    } else {
        $arDataListSort = array();
    }
    if (isset($_POST['SHOW_NOTE_SALARY'])) {
        if ($_POST['SHOW_NOTE_SALARY'] == 'Y' && $_POST['SHOW_NOTE_SALARY'] == 'N') {
            $arDataShowNote = $_POST['SHOW_NOTE_SALARY'];
        }
    }


    //    onboarding status
    if (is_array($_POST['ON_BOARDING_STATUS']) && !empty($_POST['ON_BOARDING_STATUS'])) {
        foreach ($_POST['ON_BOARDING_STATUS'] as $__value) {
            if (!isset($arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS][$__value])) {
                $err = true;
                $result['MESSAGE_ERROR']['ON_BOARDING_STATUS'] = GetMessage('YNSIR_CONFIG_ERROR_ON_BOARDING_STATUS_NOT_EXIST');
            } else {
                $arDataStatus['ON_BOARDING_STATUS'][] = $_POST['ON_BOARDING_STATUS'];
            }
        }
    } else {
        $err = true;
        $result['MESSAGE_ERROR']['ON_BOARDING_STATUS'] = GetMessage('YNSIR_CONFIG_(ERROR_ON_BOARDING_STATUS_EMPTY');
    }

    if (!$err) {
        COption::SetOptionString("ynsirecruitment", "ynsir_bizproc_config", serialize($arDataBiz));
        COption::SetOptionString("ynsirecruitment", "ynsir_hr_manager_config", serialize($arDataHrManager));
        COption::SetOptionString("ynsirecruitment", "ynsir_recruitment_manager_config", serialize($arDataRecruitmentManager));
        COption::SetOptionString("ynsirecruitment", "ynsir_list_sort", serialize($arDataListSort));
        COption::SetOptionString("ynsirecruitment", "ynsir_candidate_status", serialize($arDataStatus));
        COption::SetOptionString("ynsirecruitment", "ynsir_order_salary_note", $_POST['SHOW_NOTE_SALARY']);
        COption::SetOptionString("ynsirecruitment", "ynsir_onboarding_status", serialize($_POST['ON_BOARDING_STATUS']));
        $result['SUCCESS'] = 1;
        $_SESSION['BIZ_CONFIG_SUCCESS'] = 1;
    }
    echo json_encode($result);
    die;
}

$this->IncludeComponentTemplate();
?>