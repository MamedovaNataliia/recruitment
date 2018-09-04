<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Disk\File;

if (!CModule::IncludeModule('crm')) {
    ShowError(GetMessage('YNSIR_MODULE_NOT_INSTALLED'));
    return;
}
if (!CModule::IncludeModule("ynsirecruitment")) {
    ShowError(GetMessage("MODULE_NOT_INSTALL"));
    return;
}
if (!CModule::IncludeModule("blog")) {
//    ShowError(GetMessage("MODULE_NOT_INSTALL"));
    return;
}


$arResult['FORMAT_DB_TIME'] = 'YYYY-MM-DD';
$arResult['FORMAT_DB_BX_FULL'] = CSite::GetDateFormat("FULL");
$arResult['FORMAT_DB_BX_SHORT'] = CSite::GetDateFormat("SHORT");
$arResult['FIELD_CANDIDATE_VIEW'] = YNSIRConfig::getFieldsIntabViewCandidate();

$arResult['IS_INTERNAL_ASSOCIATE'] = ($arParams['IS_INTERNAL_ASSOCIATE'] == 'Y');
$arResult['STATUS_CANDIDATE_LOCK'] = YNSIRConfig::getCandiateStatusLock();

$isBizProcInstalled = IsModuleInstalled('bizproc');
if ($isBizProcInstalled) {
    if (!CModule::IncludeModule('bizproc')) {
        ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
        return;
    } elseif (!CBPRuntime::isFeatureEnabled())
        $isBizProcInstalled = false;
}

/** @global CMain $APPLICATION */
global $USER_FIELD_MANAGER, $USER, $APPLICATION, $DB;


use Bitrix\Main\Grid\Editor;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\Format\ContactAddressFormatter;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Crm\WebForm\Manager as WebFormManager;

$userPermissions = YNSIRPerms_::GetCurrentUserPermissions();
if (!YNSIRCandidate::CheckReadPermission(0, $userPermissions)) {
    ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
    return;
} else {
    $arResult['GRID_ID'] = 'YNSI_CANDIDATE_LIST';
    if ($arResult['IS_INTERNAL_ASSOCIATE']) $arResult['GRID_ID'] .= '_INTERNAL';

    if (isset($_REQUEST['action_button_' . $arResult['GRID_ID']])) {
        switch ($_REQUEST['action_button_' . $arResult['GRID_ID']]) {
            case 'delete':

                $APPLICATION->RestartBuffer();
                $arFilterDel = array('ID' => $_REQUEST['ID']);
                $arIDdel = array();
                $obRes = YNSIRCandidate::GetList(array(), $arFilterDel, false, false, array('ID'));
                while ($arOrder = $obRes->Fetch()) {
                    $ID = $arOrder['ID'];
                    $arEntityAttr = $userPermissions->GetEntityAttr(YNSIR_PERM_ENTITY_CANDIDATE, array($ID));
                    if (!$userPermissions->CheckEnityAccess(YNSIR_PERM_ENTITY_CANDIDATE, 'DELETE', $arEntityAttr[$ID])) {
                        continue;
                    }
                    foreach ($arOrder['ID'] as $iID) {
                        YNSIRCacheHelper::ClearCached(YNSIRCandidate::FREFIX_CACHE . $iID, YNSIRCandidate::ACTIVITY_CACHE_URL);
                    }
                    unset($iID);
                    $arIDdel[] = $arOrder['ID'];
                }
                $st = YNSIRCandidate::Delete($arIDdel);
                break;
            case 'associate':
                $APPLICATION->RestartBuffer();
                $strTypeAssociate = $_REQUEST['PARAMS']['params']['ENTITY_TYPE'];
                if ($strTypeAssociate == YNSIR_JOB_ORDER) {
                    $arFilterAssociate = array('ID' => $_REQUEST['ID'], '!CANDIDATE_STATUS' => $arResult['STATUS_CANDIDATE_LOCK']);
                    $arIDOrderInsert = array();

                    //temp GET CANDIDATE
                    $arRes = YNSIRJobOrder::GetByID($_REQUEST['CURRENT_TYPE_ID']);
                    if (intval($arRes['ID']) > 0 && $arRes['STATUS'] != JOStatus::JOSTATUS_CLOSED && !empty($arFilterAssociate['ID'])) {
                        $obRes = YNSIRCandidate::GetList(array(), $arFilterAssociate, false, false, array('ID', 'CANDIDATE_STATUS'));
                        while ($arCandidate = $obRes->Fetch()) {
                            $arID[] = $arCandidate['ID'];
                            $arIDCandidateAssociate[] = $arCandidate;
                        }
                        unset($obRes);
                        unset($arCandidate);
                        //Remove Candidates have already Add to Job before or Locked by any JobOther
                        $arCandidateAlready = array();
                        $dbResultAssociate = YNSIRAssociateJob::GetList(
                            array(),
                            array(
                                "__INNER_FILTER_ID_ASSOCIATE" =>
                                    array(
                                        'LOGIC' => 'OR',
                                        '__INNER_FILTER_ID_ORDER_AND' => array(
                                            'LOGIC' => 'AND',
                                            'ORDER_JOB_ID' => $_REQUEST['CURRENT_TYPE_ID'],
                                            'CANDIDATE_ID' => $arID
                                        ),
                                        'STATUS_ID' => $arResult['STATUS_CANDIDATE_LOCK']
                                    )

                            ),
                            false,
                            array(),
                            array('CANDIDATE_ID'));
                        while ($arAssociate = $dbResultAssociate->Fetch()) {
                            $arCandidateAlready[] = $arAssociate['CANDIDATE_ID'];
                        }
                        unset($dbResultAssociate);
                        unset($arAssociate);
                        //End

                        foreach ($arIDCandidateAssociate as $ID_CA) {
                            if (in_array($ID_CA['ID'], $arCandidateAlready)) continue;
                            $arAssociateInsert = array(
                                'CANDIDATE_ID' => $ID_CA['ID'],
                                'ORDER_JOB_ID' => $arRes['ID'],
                                'STATUS_ID' => $ID_CA['CANDIDATE_STATUS'],
                                'STATUS_ROUND_ID' => 0,
                            );
                            YNSIRAssociateJob::Add($arAssociateInsert);
                        }
                        unset($ID_CA);
                    }

                }
                break;
            default:
                break;
        }
    }
    if ($_REQUEST['action'] == 'show_total') {
        $APPLICATION->RestartBuffer();
        echo json_encode(array('TOTAL' => $_SESSION['YNSI_GRID_DATA_COUNT_TOTAL']));
        die;
    }

    if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'download') {
        $isreport = true;
    } else {
        $isreport = false;
    }
    //get value in config
    $rsTypeList = YNSIRTypelist::GetList(array('ID' => "ASC"), array(), false);
    $gender = YNSIRConfig::getListConfig('GENDER');
    $arl = array(
        YNSIRConfig::TL_HIGHEST_OBTAINED_DEGREE,
        YNSIRConfig::TL_CANDIDATE_STATUS,
        YNSIRConfig::TL_CITY,
        YNSIRConfig::TL_SOURCES,
        YNSIRConfig::TL_UNIVERSITY,
        YNSIRConfig::TL_MAJOR,
        YNSIRConfig::TL_ENGLISH_PROFICIENCY,
        YNSIRConfig::TL_APPLY_POSITION,
        YNSIRConfig::TL_MARITAL_STATUS
    );
    while ($element_list = $rsTypeList->GetNext()) {
        $arResult['CONFIG'][$element_list['ENTITY']][$element_list['ID']] = $element_list;
        $arResult['CONFIG_FILTER'][$element_list['ENTITY']][$element_list['ID']] = $element_list['NAME_' . strtoupper(LANGUAGE_ID)];
    }
    $arResult['CONFIG_FILTER'][YNSIRConfig::TL_CANDIDATE_STATUS] = YNSIRGeneral::getListJobStatus('CANDIDATE_STATUS');
    $arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS] = YNSIRGeneral::getListJobStatus('CANDIDATE_STATUS');

//print_r($arResult['CONFIG_FILTER']);die;
    $arResult['FORMAT_DB_BX_SHORT'] = CSite::GetDateFormat("SHORT");
    $arResult['FORMAT_DB_BX_FULL'] = CSite::GetDateFormat("FULL");
    $arResult['FORMAT_DB_TIME'] = 'YYYY-MM-DD';
    $arResult['DATE_TIME_FORMAT'] = 'f j, Y';

    $sTimenewformat = CSite::GetDateFormat("SHORT");
    $sTimeFormat = CSite::GetDateFormat("LONG");
    //ListConfig


    $enableOutmodedFields = $arResult['ENABLE_OUTMODED_FIELDS'] = ContactSettings::getCurrent()->areOutmodedRequisitesEnabled();

    $arResult['CURRENT_USER_ID'] = $USER->GetID();
    $arParams['PATH_TO_ORDER_DETAIL'] = ' /recruitment/job-order/detail/#job_order_id#/';
    $arParams['PATH_TO_CONTACT_EDIT'] = ' /recruitment/candidate/edit/#contact_id#/';
    $arParams['PATH_TO_CONTACT_DETAIL'] = ' /recruitment/candidate/detail/#contact_id#/';

    $arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
    $arResult['SESSION_ID'] = bitrix_sessid();
    $arResult['NAVIGATION_CONTEXT_ID'] = isset($arParams['NAVIGATION_CONTEXT_ID']) ? $arParams['NAVIGATION_CONTEXT_ID'] : '';
    $arResult['PRESERVE_HISTORY'] = isset($arParams['PRESERVE_HISTORY']) ? $arParams['PRESERVE_HISTORY'] : false;

    CUtil::InitJSCore(array('ajax', 'tooltip'));

    $arResult['GADGET'] = 'N';
    if (isset($arParams['GADGET_ID']) && strlen($arParams['GADGET_ID']) > 0) {
        $arResult['GADGET'] = 'Y';
        $arResult['GADGET_ID'] = $arParams['GADGET_ID'];
    }
    $isInGadgetMode = $arResult['GADGET'] === 'Y';

    $arFilter = $arSort = array();
    $bInternal = false;
    $arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
    $arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

    if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER'])) {
        if (empty($arParams['GRID_ID_SUFFIX'])) {
            $arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null ? strtoupper($this->GetParent()->GetName()) : '';
        }
        $arFilter = $arParams['INTERNAL_FILTER'];
    }

    $arResult['WEBFORM_LIST'] = WebFormManager::getListNames();
    $arResult['EXPORT_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));
    $arResult['FILTER'] = array();
    $arResult['FILTER2LOGIC'] = array();
    $arResult['FILTER_PRESETS'] = array();

    $arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
    $arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
    $arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
    $arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';
    $arResult['CALL_LIST_UPDATE_MODE'] = isset($_REQUEST['call_list_context']) && isset($_REQUEST['call_list_id']) && IsModuleInstalled('voximplant');
    $arResult['CALL_LIST_CONTEXT'] = (string)$_REQUEST['call_list_context'];
    $arResult['CALL_LIST_ID'] = (int)$_REQUEST['call_list_id'];

    $requisite = new \Bitrix\Crm\EntityRequisite();

    $arResult['FILTER2LOGIC'] = array('TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'COMMENTS');
    $arResult['FILTER'] = array();
    $arFieldCandidate = YNSIRConfig::getFieldsCandidate();
    $arFieldCandidate['FIRST_NAME'] = GetMessage('YNSIR_IG_TITLE_FULL_NAME');
    $arFieldCandidate['MODIFIED_DATE'] = GetMessage('YNSIR_IG_TITLE_MODIFIED_DATE');
    $arFieldCandidate['CREATED_DATE'] = GetMessage('YNSIR_IG_TITLE_CREATED_DATE');
    unset($arFieldCandidate['LAST_NAME']);
    unset($arFieldCandidate['SALT_NAME']);
//    deb($arFieldCandidate);die;
    foreach ($arFieldCandidate as $k => $v) {

        switch ($k) {

            case 'FIRST_NAME':
            case 'EMAIL':
            case 'PHONE':
            case 'CMOBILE':
            case 'FILE_RESUME':
            case 'FILE_FORMATTED_RESUME':
            case 'FILE_COVER_LETTER':
            case 'FILE_OTHERS':
            case 'EMAIL_OPT_OUT':
                break;
            case 'CANDIDATE_OWNER' :
                $arResult['FILTER'][] = array(
                    'id' => $k,
                    'name' => strtoupper($v),
                    'type' => 'custom_entity',
                    'selector' => array(
                        'TYPE' => 'user',
                        'DATA' => array('ID' => 'CANDIDATE_OWNER', 'FIELD_ID' => $k)
                    )
                );
                break;
            case YNSIRConfig::TL_HIGHEST_OBTAINED_DEGREE:
            case YNSIRConfig::TL_CANDIDATE_STATUS:
            case YNSIRConfig::TL_CITY:
            case YNSIRConfig::TL_SOURCES:
            case YNSIRConfig::TL_UNIVERSITY:
            case YNSIRConfig::TL_MAJOR:
            case YNSIRConfig::TL_ENGLISH_PROFICIENCY:
            case YNSIRConfig::TL_APPLY_POSITION:
            case YNSIRConfig::TL_MARITAL_STATUS:
//                break;
                $arResult['CONFIG_FILTER'][$k] = isset($arResult['CONFIG_FILTER'][$k]) ? $arResult['CONFIG_FILTER'][$k] : array('');
//                echo '<pre>'; print_r($arResult['CONFIG_FILTER'][$k]);echo '</pre>';
                $arResult['FILTER'][] = array(
                    'id' => $k,
                    'name' => strtoupper($v),
                    'type' => 'list',
                    'default' => false,
                    'items' => $arResult['CONFIG_FILTER'][$k]
                );
                break;
            case 'GENDER' :
                $arResult['FILTER'][] = array(
                    'id' => $k,
                    'name' => strtoupper($v),
                    'type' => 'list',
                    'default' => true,
                    'items' => $gender// array_merge(array(''=>''),$gender)
                );
                break;
            case 'DOB' :
            case 'MODIFIED_DATE' :
            case 'CREATED_DATE' :
                $arResult['FILTER'][] = array('id' => $k, 'name' => strtoupper($v), 'type' => 'date');
                break;
            case 'EXPERIENCE':
            case 'EXPECTED_SALARY':
            case 'CURRENT_SALARY':
                $arResult['FILTER'][] = array('id' => $k, 'default' => true, 'name' => strtoupper($v), 'type' => 'number');
                break;
            default:
//                break;
                $arResult['FILTER'][] = array('id' => $k, 'name' => strtoupper($v), 'type' => 'string');
                break;
        }
    }

    $currentUserID = $arResult['CURRENT_USER_ID'];
    $currentUserName = CYNSIRViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
    $arResult['FILTER_PRESETS'] = array();

    // Headers initialization -->
    $arResult["HEADERS"] = array();

    //meger show data:
    $arHeaderMeger = array(
//        'CONTACT_INFO' => array(
//            'ITEM' => array(
//                'PERSONAL_MOBILE' => array('lable' => $arFieldCandidate['PERSONAL_MOBILE'], 'type' => 'phone'),
//                'EMAIL' => array('LABLE' => $arFieldCandidate['EMAIL'], 'type' => 'email'),
//                'PERSONAL_EMAIL' => array('LABLE' => $arFieldCandidate['PERSONAL_EMAIL'], 'type' => 'email'),
//                'UF_PHONE_INNER' => array('lable' => $arFieldCandidate['UF_PHONE_INNER'], 'type' => 'phone'),
//                'UF_SKYPE' => array('lable' => $arFieldCandidate['UF_SKYPE'], 'type' => 'phone'),
//                'UF_FACEBOOK' => array('lable' => $arFieldCandidate['UF_FACEBOOK'], 'type' => 'link'),
//                'PERSONAL_TAX_CODE' => array('lable' => $arFieldCandidate['PERSONAL_TAX_CODE'], 'type' => 'phone')
//            ),
//            'LABLE' => 'Contact'
//        )
    );
    foreach ($arHeaderMeger as $k => $v) {
        $arFieldCandidate[$k] = $v['LABLE'];
    }
    $arFieldCandidate['MODIFIED_DATE'] = GetMessage('YNSIR_IG_TITLE_MODIFIED_DATE');
    $arFieldCandidate['CREATED_DATE'] = GetMessage('YNSIR_IG_TITLE_CREATED_DATE');
    $arResult["HEADERS"][] = array('id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'first_order' => 'desc', 'width' => 60, 'editable' => false, 'type' => 'int', 'class' => 'minimal');

    foreach ($arFieldCandidate as $k => $v) {
        switch ($k) {
            case 'ACTIVITY_ID':
                break;
            case 'ID':
                $arResult["HEADERS"][] = array('id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'first_order' => 'desc', 'width' => 60, 'editable' => false, 'type' => 'int', 'class' => 'minimal');
                break;
            case 'STREET':
                $arResult["HEADERS"][] = array("id" => $k, "name" => $v, "sort" => $k, "default" => true);
                $arResult['HEADERS'][] = array(
                    'id' => 'ACTIVITY_ID',
                    'name' => GetMessage('YNSIR_TITLE_ACTIVITY'),
                    'sort' => 'C_ACTIVITY_TIME',
                    'width' => 150,
                    'default' => true,
                    'prevent_default' => false
                );
                break;
            case 'FIRST_NAME':
            case 'LAST_NAME':
            case 'STREET':
            case 'EXPERIENCE':
                $arResult["HEADERS"][] = array("id" => $k, "name" => $v, "sort" => $k, "default" => true);
                break;
            case 'EMAIL':
            case 'CMOBILE':
            case 'USER_ID':
            case 'CREATED_BY':
            case 'MODIFIED_BY':
                $arResult["HEADERS"][] = array("id" => $k, "name" => $v, "default" => true);
                break;
            default:
                $arResult["HEADERS"][] = array("id" => $k, "name" => $v, "default" => false);
        }
    }
    // <-- Headers initialization
    if (intval($arParams['CONTACT_COUNT']) <= 0)
        $arParams['CONTACT_COUNT'] = 20;

    $arNavParams = array(
        'nPageSize' => $arParams['CONTACT_COUNT'],
    );
    //TODO Remove header and filter Activity when interal list
    if ($arResult['IS_INTERNAL_ASSOCIATE']) {
        $arRemoveHeader_Filter = array('ACTIVITY_ID', 'FILE_RESUME', 'FILE_FORMATTED_RESUME', 'FILE_COVER_LETTER', 'FILE_OTHERS');
        foreach ($arResult["HEADERS"] as $HEADER_KEY => $objHEADER) {
            if (in_array($objHEADER['id'], $arRemoveHeader_Filter)) {
                unset($arResult["HEADERS"][$HEADER_KEY]);
            }
        }
        unset($HEADER_KEY);
        unset($objHEADER);
        foreach ($arResult["FILTER"] as $FILTER_KEY => $objFILTER) {
            if (in_array($objFILTER['id'], $arRemoveHeader_Filter)) {
                unset($arResult["FILTER"][$FILTER_KEY]);
            }
        }
        unset($FILTER_KEY);
        unset($objFILTER);
    }
    //end
    $arNavigation = CDBResult::GetNavParams($arNavParams);
    $gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
    $filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
    $arNavParams = $gridOptions->GetNavParams($arNavParams);
    $arNavParams['bShowAll'] = false;
    if (!$arResult['IS_EXTERNAL_FILTER']) {
        $arFilter += $filterOptions->getFilter($arResult['FILTER']);
    }

//    $CCrmUserType->PrepareListFilterValues($arResult['FILTER'], $arFilter, $arResult['GRID_ID']);
    $USER_FIELD_MANAGER->AdminListAddFilter(CCrmContact::$sUFEntityID, $arFilter);
    // converts data from filter
    if (isset($arFilter['FIND'])) {
        if (is_string($arFilter['FIND'])) {
            $find = trim($arFilter['FIND']);
            if ($find !== '') {
                $arFilter['SEARCH_CONTENT'] = $find;
            }
        }
        unset($arFilter['FIND']);
    }

    $requisite->prepareEntityListFilter($arFilter);

    $arImmutableFilters = array(
        'FM', 'ID', 'COMPANY_ID', 'COMPANY_ID_value', 'ASSOCIATED_COMPANY_ID', 'ASSOCIATED_DEAL_ID',
        'ASSIGNED_BY_ID', 'ASSIGNED_BY_ID_value',
        'CREATED_BY_ID', 'CREATED_BY_ID_value',
        'MODIFY_BY_ID', 'MODIFY_BY_ID_value',
        'TYPE_ID', 'SOURCE_ID', 'WEBFORM_ID',
        'HAS_PHONE', 'HAS_EMAIL', 'RQ',
        'SEARCH_CONTENT',
        'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID'
    );

    foreach ($arFilter as $k => $v) {
        //Check if first key character is aplpha and key is not immutable

        if (preg_match('/^[a-zA-Z]/', $k) !== 1 || in_array($k, $arImmutableFilters, true)) {
            continue;
        }

        $arMatch = array();

        if ($k === 'ORIGINATOR_ID') {
            // HACK: build filter by internal entities
            $arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
            unset($arFilter[$k]);
        } elseif ($k === 'ADDRESS'
            || $k === 'ADDRESS_2'
            || $k === 'ADDRESS_CITY'
            || $k === 'ADDRESS_REGION'
            || $k === 'ADDRESS_PROVINCE'
            || $k === 'ADDRESS_POSTAL_CODE'
            || $k === 'ADDRESS_COUNTRY'
        ) {
            $v = trim($v);
            if ($v === '') {
                continue;
            }

            if (!isset($arFilter['ADDRESSES'])) {
                $arFilter['ADDRESSES'] = array();
            }

            $addressTypeID = ContactAddress::resolveEntityFieldTypeID($k);
            if (!isset($arFilter['ADDRESSES'][$addressTypeID])) {
                $arFilter['ADDRESSES'][$addressTypeID] = array();
            }

            $n = ContactAddress::mapEntityField($k, $addressTypeID);
            $arFilter['ADDRESSES'][$addressTypeID][$n] = "{$v}%";
            unset($arFilter[$k]);
        } elseif (preg_match('/(.*)_from$/i' . BX_UTF_PCRE_MODIFIER, $k, $arMatch)) {
            if (strlen($v) > 0) {
                $arFilter['>=' . $arMatch[1]] = $v;
            }
            unset($arFilter[$k]);
        } elseif (preg_match('/(.*)_to$/i' . BX_UTF_PCRE_MODIFIER, $k, $arMatch)) {
            if (strlen($v) > 0) {
                if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/' . BX_UTF_PCRE_MODIFIER, $v)) {
                    $v = CCrmDateTimeHelper::SetMaxDayTime($v);
                }
                $arFilter['<=' . $arMatch[1]] = $v;
            }
            unset($arFilter[$k]);
        } elseif (in_array($k, $arResult['FILTER2LOGIC'])) {
            // Bugfix #26956 - skip empty values in logical filter
            $v = trim($v);
            if ($v !== '') {
                $arFilter['?' . $k] = $v;
            }
            unset($arFilter[$k]);
        } elseif ($k === 'COMMUNICATION_TYPE') {
            if (!is_array($v)) {
                $v = array($v);
            }
            foreach ($v as $commTypeID) {
                if ($commTypeID === CCrmFieldMulti::PHONE) {
                    $arFilter['=HAS_PHONE'] = 'Y';
                } elseif ($commTypeID === CCrmFieldMulti::EMAIL) {
                    $arFilter['=HAS_EMAIL'] = 'Y';
                }
            }

            unset($arFilter['COMMUNICATION_TYPE']);
        } elseif ($k != 'ID'
            && $k != 'CANDIDATE_OWNER'
            && $k != 'HIGHEST_OBTAINED_DEGREE'
            && $k != 'CURRENT_FORMER_ISSUE_PLACE'

            && $k != YNSIRConfig::TL_HIGHEST_OBTAINED_DEGREE
            && $k != YNSIRConfig::TL_CANDIDATE_STATUS
            && $k != YNSIRConfig::TL_CITY
            && $k != YNSIRConfig::TL_SOURCES
            && $k != YNSIRConfig::TL_UNIVERSITY
            && $k != YNSIRConfig::TL_MAJOR
            && $k != YNSIRConfig::TL_ENGLISH_PROFICIENCY
            && $k != YNSIRConfig::TL_APPLY_POSITION
            && $k != YNSIRConfig::TL_MARITAL_STATUS
            && $k != '__INNER_FILTER' && $k != '__JOINS' && $k != '__CONDITIONS' && strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1
        ) {
            $arFilter['%' . $k] = $v;
            unset($arFilter[$k]);
        }
    }

    \Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

    $_arSort = $gridOptions->GetSorting(array(
        'sort' => array('full_name' => 'asc'),
        'vars' => array('by' => 'by', 'order' => 'order')
    ));

    $arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
    $arResult['SORT_VARS'] = $_arSort['vars'];

    if ($isInExportMode) {
        $arFilter['EXPORT'] = 'Y';
    }

    $arSelect = $gridOptions->GetVisibleColumns();
    // HACK: Make custom sort for ASSIGNED_BY and FULL_NAME field
    $arSort = $arResult['SORT'];

    $arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
    if (isset($arSelectMap['ACTIVITY_ID'])) {
        $arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
    }

    if (isset($arParams['IS_EXTERNAL_CONTEXT'])) {
        $arOptions['IS_EXTERNAL_CONTEXT'] = $arParams['IS_EXTERNAL_CONTEXT'];
    }

    $arSelect = array_unique(array_keys($arSelectMap), SORT_STRING);

    $arResult['CANDIDATE'] = array();
    $arResult['CONTACT_ID'] = array();
    $arResult['CONTACT_UF'] = array();

    //region Navigation data initialization
    $pageNum = 0;
    $pageSize = !$isInExportMode
        ? (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['CONTACT_COUNT']) : 0;
    $enableNextPage = false;

    if (isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y') {
        $pageNum = 1;
    } elseif ($pageSize > 0 && isset($_REQUEST['page'])) {
        $pageNum = (int)$_REQUEST['page'];

        if ($pageNum < 0) {
            //Backward mode
            $offset = -($pageNum + 1);
            $gridData = $_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']];
            $filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
            $arNavParams = array(
                'nPageSize' => 10,
                'bShowAll' => false,
                'nTopCount' => 1,
            );
            $navListOptions = array(
                'FIELD_OPTIONS' =>
                    array(
                        'ADDITIONAL_FIELDS' =>
                            array(
                                0 => 'ACTIVITY',
                            ),
                    ),
                'QUERY_OPTIONS' =>
                    array(
                        'LIMIT' => 10000,
                        'OFFSET' => 0,
                    ),
            );
            $result = YNSIRCandidate::GetListCandidateForMultiField(array(), $filter, $arNavParams, $navListOptions, array());

            $count = $result->result->num_rows;
            $pageNum = (int)(ceil($count / $pageSize));
            if ($pageNum <= 0) {
                $pageNum = 1;
            }
        }
        $arNavParams = array(
            "nPageSize" => $pageSize,
            "iNumPage" => $pageNum,
        );
    }

    if ($pageNum > 0) {
        if (!isset($_SESSION['YNSIR_PAGINATION_DATA'])) {
            $_SESSION['YNSIR_PAGINATION_DATA'] = array();
        }
        $_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum);
    } else {
        if (!$bInternal
            && !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
            && isset($_SESSION['YNSIR_PAGINATION_DATA'])
            && isset($_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']])
            && isset($_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'])
        ) {
            $pageNum = (int)$_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'];
        }

        if ($pageNum <= 0) {
            $pageNum = 1;
        }
    }
    //endregion

    if ($isInGadgetMode && isset($arNavParams['nTopCount'])) {
        $navListOptions = array_merge($arOptions, array('QUERY_OPTIONS' => array('LIMIT' => $arNavParams['nTopCount'])));
    } else {
        $navListOptions = $isInExportMode
            ? array()
            : array_merge(
                $arOptions,
                array('QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)))
            );
    }

    if (!empty($arFilter['SEARCH_CONTENT'])) {
        $t = $arFilter;
        unset($t['SEARCH_CONTENT']);
        $isLogicOr = stripos($arFilter['SEARCH_CONTENT'], '|');
        $isLogicAnd = stripos($arFilter['SEARCH_CONTENT'], '&');
        if ($isLogicOr > 0 || $isLogicAnd > 0) {
            $arFilterLogicOr = explode("|", $arFilter['SEARCH_CONTENT']);
        } else {
            $arF = explode(" ", $arFilter['SEARCH_CONTENT']);
        }
        foreach ($arF as $k => $v) {
            $v = trim($v);
            $f['__INNER_FILTER_ID_' . $v] = array(
                'LOGIC' => 'OR',
                '%SALT_NAME' => $v,
                '%FIRST_NAME' => $v,
                '%LAST_NAME' => $v,
                '%EMAIL' => $v,
                '%PHONE' => $v,
                '%CMOBILE' => $v,
                '%FILE_CONTENT' => $v
            );
        }
        $arFilter = array('LOGIC' => 'AND',
            '__INNER_FILTER_ID1' => $t,
            '__INNER_FILTER_ID2' => $f);


        foreach ($arFilterLogicOr as $k => $value) {
            if (!isset($arFilter['__INNER_FILTER_ID_OR_FILE']['LOGIC'])) {
                $arFilter['__INNER_FILTER_ID_OR_FILE']['LOGIC'] = 'OR';
            }
            if ($value == '' || $value == ' ' || $value == '||' | $value == '|' | $value == '&&' || $value == '&') continue;

            $arFilterLogicAnd = explode("&", $value);

            foreach ($arFilterLogicAnd as $key => $vsearch) {
                $vsearch = trim($vsearch);
                $arFilter['__INNER_FILTER_ID_OR_FILE']['__INNER_FILTER_ID_AND_' . $k]['LOGIC'] = 'AND';
                $arFilter['__INNER_FILTER_ID_OR_FILE']['__INNER_FILTER_ID_AND_' . $k]['__INNER_FILTER_ID_AND_' . $key]['%FILE_CONTENT'] = $vsearch;
            }
        }

    }
    $arNavParams['nTopCount'] = 1;
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
        $navListOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
    }

    //TODO: Internal Associate
    if ($arResult['IS_INTERNAL_ASSOCIATE']) {
        $arStatus_lock = YNSIRConfig::getCandiateStatusLock();
        //get List Candidate have aready added to current JobOrder

        $dbResultAssociate = YNSIRAssociateJob::GetList(
            array(),
            array(
                "__INNER_FILTER_ID_CANDIDATE" => array(
                    'LOGIC' => 'OR',
                    'ORDER_JOB_ID' => $arParams['ENTITY_ID'],
                    'STATUS_ID' => $arResult['STATUS_CANDIDATE_LOCK']
                )
            ),
            false,
            false,
            array('CANDIDATE_ID'));


        while ($arCandidateRes = $dbResultAssociate->GetNext()) {
            if (!in_array($arCandidateRes['CANDIDATE_ID'], $arFilter['!ID'])) $arFilter['!ID'][] = $arCandidateRes['CANDIDATE_ID'];
        }
        unset($arCandidateRes);
        unset($dbResultAssociate);
        $arFilter['!CANDIDATE_STATUS'] = $arStatus_lock;
    }
    //ENd

    $dbResult = YNSIRCandidate::GetListCandidateForMultiField(
        $arSort,
        $arFilter,
        $arNavParams,
        $navListOptions, $arSelect);
    while ($arProfile = $dbResult->GetNext()) {
        $arCandId[] = $arProfile['ID'];
    }

    $_SESSION['YNSI_GRID_DATA_COUNT_TOTAL'] = $dbResult->NavRecordCount;
    $_SESSION['YNSIR_PAGINATION_DATA'][$arResult['GRID_ID']]['FILTER'] = $arFilter;
    $qty = 0;

    $dbMultiField = YNSIRCandidate::GetListMultiField($arSort, array('CANDIDATE_ID' => $arCandId));
    while ($multiField = $dbMultiField->GetNext()) {
        if ($multiField['CONTENT'] == -1) continue;
        $content_list = $multiField['CONTENT'];


        $lable = $arResult['CONFIG'][$multiField['TYPE']][$multiField['CONTENT']]['ADDITIONAL_INFO_LABEL_EN'];
        $content = $arResult['CONFIG'][$multiField['TYPE']][$multiField['CONTENT']]['NAME_' . strtoupper(LANGUAGE_ID)];
        if ($multiField['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
            $multiField['ADDITIONAL_VALUE'] = $DB->FormatDate($multiField['ADDITIONAL_VALUE'], $arResult['FORMAT_DB_TIME'], $arResult['FORMAT_DB_BX_FULL']);
            $multiField['ADDITIONAL_VALUE'] = FormatDateEx($multiField['ADDITIONAL_VALUE'], $arResult['FORMAT_DB_BX_FULL'], $arResult['DATE_TIME_FORMAT']);
        }
        if ($multiField['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_USER) {

            $arOWNERTOOLTIP = YNSIRHelper::getTooltipandPhotoUser($multiField['ADDITIONAL_VALUE'], 'M' . $multiField['ID']);;
            $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER_ID'] = $arResult['CANDIDATE']['CANDIDATE_OWNER'];
            $photo = '<div class = "crm-client-photo-wrapper">
                                            <div class="crm-client-user-def-pic">
                                                <img alt="Author Photo" src="' . $arOWNERTOOLTIP['PHOTO_URL'] . '"/>
                                            </div>
                                        </div>';
            $multiField['ADDITIONAL_VALUE'] = $arOWNERTOOLTIP['TOOLTIP'];

        }
        $lable = strlen($lable) > 0 ? $lable . ': ' : '';
        $additional_value = $multiField['ADDITIONAL_VALUE'] != '' ? ' (' . $lable . $multiField['ADDITIONAL_VALUE'] . ')' : '';

        $multiField['CONTENT'] = $content . $additional_value . '<br>';

        $arMultiField[$multiField['CANDIDATE_ID']][$multiField['TYPE']] .= !in_array($multiField['TYPE'], $arl) ? $content_list . '<br>' : $multiField['CONTENT'] . '<br>';
    }
    $sFormatName = CSite::GetNameFormat(false);
    $now = time() + CTimeZone::GetOffset();
    $dbResult = YNSIRCandidate::GetListCandidate(
        $arSort,
        array('ID' => $arCandId),
        '',
        array(), $arSelect);
    while ($arProfile = $dbResult->GetNext()) {
        if ($pageSize > 0 && ++$qty > $pageSize) {
            $enableNextPage = true;
            break;
        }
        $item = $arProfile;
        $item = isset($arMultiField[$item['ID']]) ? array_merge($item, $arMultiField[$item['ID']]) : $item;
        if (isset($item['~ACTIVITY_TIME'])) {
            $time = MakeTimeStamp($item['~ACTIVITY_TIME']);
            $item['~ACTIVITY_EXPIRED'] = $time <= $now;
            $item['~ACTIVITY_IS_CURRENT_DAY'] = $item['~ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
        }
        if (true) {
            $item['PATH_TO_CONTACT_EDIT'] = CComponentEngine::MakePathFromTemplate(
                $arParams['PATH_TO_CONTACT_EDIT'],
                array('contact_id' => $arProfile['ID'])
            );
        }
        if (true) {
            $item['PATH_TO_CONTACT_DETAIL'] = CComponentEngine::MakePathFromTemplate(
                $arParams['PATH_TO_CONTACT_DETAIL'],
                array('contact_id' => $arProfile['ID'])
            );
        }

        $item['GENDER'] = $gender[$item['GENDER']];
        $item['CANDIDATE_OWNER'] = YNSIRHelper::getTooltipUser($item['CANDIDATE_OWNER'], 'CANDIDATE_OWNER' . $item['ID']);
        $item['EMAIL_OPT_OUT'] = $item['EMAIL_OPT_OUT'] == 1 ? 'Yes' : 'No';
        $item['SALT_NAME'] = $SALT_NAME[$item['SALT_NAME']];

        $item['NAME'] = CUser::FormatName(
            $sFormatName,
            array(
                "NAME" => $item['FIRST_NAME'],
                "LAST_NAME" => $item['LAST_NAME'],
            )
        );
        $item['FIRST_NAME'] = $item['SALT_NAME'] . ' ' . $item['NAME'];
        $item['DOB'] = ($item['DOB'] != '0000-00-00') ? FormatDateEx($item['DOB'], $arResult['FORMAT_DB_TIME'], $arResult['DATE_TIME_FORMAT']) : "";

        $item['CANDIDATE_STATUS'] = $arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS][$item['CANDIDATE_STATUS']];

        //Update by nhatth2
        //TODO: View status if current candiate associate with Job order
        //get List Candidate have aready added to current JobOrder
        $arCandidateRes = YNSIRAssociateJob::checkCandiateLock($item['ID']);
        if (!empty($arCandidateRes)) {
            $arParams['PATH_TO_ORDER_DETAIL'] = ' /recruitment/job-order/detail/#job_order_id#/';
            $HtmlOrder = '';
            if (intval($arCandidateRes['ORDER_JOB_ID']) > 0) {
                $orderLink = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ORDER_DETAIL'], array('job_order_id' => $arCandidateRes['ORDER_JOB_ID']));
                $HtmlOrder = '<span>&rarr;</span><a href="' . $orderLink . '" title="' . $arCandidateRes['ORDER_JOB_TITLE'] . '">' . $arCandidateRes['ORDER_JOB_TITLE'] . '</a>';
            }
            $classLock = '';
            if ($arCandidateRes['IS_LOCK'] == 'Y') {
                $classLock = 'ynsir-associate-candidate-lock-detail';
            }
            $item['CANDIDATE_STATUS'] = '<span class="' . $classLock . '">' . $arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS][$arCandidateRes['STATUS_ID']] . $HtmlOrder . '</span>';
        }
        unset($arCandidateRes);
        //end
        //get attact file
        $arFile = YNSIRFile::getListById($item['ID']);

        foreach ($arFile as $key => $file) {
            foreach ($arFile[$key] as $idFile => $fitem) {
                $k = $idFile;
                $file = File::loadById($idFile, array('STORAGE'));
                if (!$file) continue;
                // id storage
                $iIdStorageTmp = $file->getParentId();
                if($iIdStorageTmp > 0){
                    $object = \Bitrix\Disk\BaseObject::loadById((int)$iIdStorageTmp, array('STORAGE'));
                    if (!$object) {
                        $idStorage = 0;
                        continue;
                    }
                }
                // end
                $arFileInfo = CFile::GetByID($file->getFileId());
                $arDetailIFile = $arFileInfo->arResult;
                $arPhaseName = '';
                if (!empty($arDetailIFile)) {
                    $file_name = $arDetailIFile[0]['ORIGINAL_NAME'];
                    $file_size = CFile::FormatSize(
                        $arDetailIFile[0]['FILE_SIZE']
                    );
                    $file_name = TruncateText($file_name, 30);
                    $item[$key] .= getFileInfo(array('ID' => $idFile, 'NAME' => $file_name, 'TYPE' => $key));
//                    $item[$key] .= '<a href="#" data-bx-viewer="iframe" data-bx-download="/disk/downloadFile/' . $k . '/?&amp;ncc=1&amp;filename=' . $arDetailIFile[0]['ORIGINAL_NAME'] . '" data-bx-title="' . $arDetailIFile[0]['ORIGINAL_NAME'] . '"data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=' . $k . '&amp;service=gvdrive&amp; bx-attach-file-id="' . $k . '" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId=' . $k . '&amp;service=gdrive&amp;action=' . $k . '">
//                    ' . $arDetailIFile[0]['ORIGINAL_NAME'] . '
//                    </a>'.'<script type="text/javascript">BX.viewElementBind(\'bx-disk-filepage-' . $k . '\',{showTitle: true},{attr: "data-bx-viewer"});</script>';
                }
            }

        }

        $arResult['CANDIDATE'][$arProfile['ID']] = $item;
        $arResult['CONTACT_ID'][$arProfile['ID']] = $arProfile['ID'];
    }
    $arResult['FIELD_HAS_PERMISSION'] = $arFieldHasPermistion;
    $arIDProfile = array_keys($arResult['CANDIDATE']);
    if (empty($arIDProfile)) {
        $arIDProfile = array(-1);
    }
    $arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);


    if (!isset($_SESSION['YNSI_GRID_DATA'])) {
        $_SESSION['YNSI_GRID_DATA'] = array();
    }
    $_SESSION['YNSI_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);

    if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'filter-reset') {
        LocalRedirect('/recruitment/candidate/list/');
    }


    if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'download') {
        $aOptions = CUserOptions::GetOption("main.interface.grid", $arResult['GRID_ID'], array());
        $strHeader = $aOptions['views']['default']['columns'];
        $strHeader = str_replace('DEPT_ID', 'UF_DEPARTMENT', $strHeader);
        $arHeader = array_flip(explode(',', $strHeader));

        if (isset($arHeader['FULL_NAME'])) {
            $arHeader['PERSONAL_GENDER'] = 'PERSONAL_GENDER';
            $arHeader['PERSONAL_PROFESSION'] = 'PERSONAL_PROFESSION';
        }
        foreach ($arHeader as $k => $v) {
            if (!empty($arHeaderMeger[$k])) {
                $arkeyofitem = array_flip(array_keys($arHeaderMeger[$k]['ITEM']));
                $arHeader = array_merge($arHeader, $arkeyofitem);
                unset($arHeader[$k]);
            }
        }
        foreach ($arResult['CANDIDATE'] as $sKey => $arProfile)//$arFieldHasPermistion
        {
            $arResult['CANDIDATE'][$sKey] = array_intersect_key($arResult['CANDIDATE'][$sKey], $arResult['FIELD_HAS_PERMISSION'][$sKey]);
        }
        $arHeader = array_intersect_key($arFieldCandidate, $arHeader);
        $APPLICATION->RestartBuffer();
        // hack. any '.default' customized template should contain 'excel' page
        $this->__templateName = '.default';

        if ($_REQUEST['type'] === 'carddav') {
            Header('Content-Type: text/vcard');
        }
        if ($_REQUEST['type'] == 'csv') {
            Header('Content-Type: text/csv');
            Header('Content-Disposition: attachment;filename=staff-list.csv');
        }
        if ($_REQUEST['type'] === 'excel') {
            Header('Content-Type: application/vnd.ms-excel');
            Header('Content-Disposition: attachment;filename=staff-list.xls');
        }
        Header('Content-Type: application/octet-stream');
        Header('Content-Transfer-Encoding: binary');

        // add UTF-8 BOM marker
        if (defined('BX_UTF') && BX_UTF)
            echo chr(239) . chr(187) . chr(191);
        $arResult['SELECTED_HEADERS'] = $arHeader;
        $this->IncludeComponentTemplate($_REQUEST['type']);

        die();
    }

    //GROUP ADD BTN:ADD + IMPORT
    $arResult['PERMS']['ADD'] = !$userPermissions->HavePerm(YNSIR_PERM_ENTITY_CANDIDATE, YNSIR_PERM_NONE, 'ADD');
    $arResult['PERMS']['WRITE'] = 0;

    //DELETE FOR ALL
    $arResult['PERMS']['DELETE'] = !$userPermissions->HavePerm(YNSIR_PERM_ENTITY_CANDIDATE, YNSIR_PERM_NONE, 'DELETE');

    $this->IncludeComponentTemplate();
    include_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix/crm.contact/include/nav.php');
    return $arResult['ROWS_COUNT'];
}
function getFileInfo($arData = array())
{
    $sResult = '<div id="bx-disk-filepage-' . $arData['ID'] . '" class="bx-disk-filepage-' . $arData['TYPE'] . '">
            <a href="#" data-bx-viewer="iframe" data-bx-download="/disk/downloadFile/' . $arData['ID'] . '/?&amp;ncc=1&amp;filename=' . $arData['NAME'] . '" data-bx-title="' . $arData['NAME'] . '" data-bx-src="/bitrix/tools/disk/document.php?document_action=show&amp;primaryAction=show&amp;objectId=' . $arData['ID'] . '&amp;service=gvdrive&amp; bx-attach-file-id="' . $arData['ID'] . '"="" data-bx-edit="/bitrix/tools/disk/document.php?document_action=start&amp;primaryAction=publish&amp;objectId=' . $arData['ID'] . '&amp;service=gdrive&amp;action=' . $arData['ID'] . '">
            ' . $arData['NAME'] . '                                             </a>
        </div>';
    $sResult .= "<script type=\"text/javascript\">BX.viewElementBind('bx-disk-filepage-" . $arData['ID'] . "',{showTitle: true},{attr: 'data-bx-viewer'});</script>";
    return $sResult;
}

?>
