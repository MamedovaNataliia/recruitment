<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
// =================== CRM Library ==========================================
use Bitrix\Main\Grid\Editor;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\Format\ContactAddressFormatter;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Crm\WebForm\Manager as WebFormManager;
// =================== include module =======================================
if (!CModule::IncludeModule('crm')) {
    ShowError(GetMessage('MODULE_NOT_INSTALL'));
    return;
}
if (!CModule::IncludeModule("ynsirecruitment")) {
    ShowError(GetMessage("MODULE_NOT_INSTALL"));
    return;
}
// =================== general information =======================================
$sFileTemplate = ''; // file show content
$arResult['FORMAT_DB_TIME'] = 'YYYY-MM-DD';
$arResult['FORMAT_DB_BX_FULL'] = CSite::GetDateFormat("FULL");
$arResult['FORMAT_DB_BX_SHORT'] = CSite::GetDateFormat("SHORT");
$arResult['FIELDS_BY_SECTION'] = YNSIRConfig::getSectionFieldsJobOrder();
$arResult['INTERNAL'] =$arParams['IS_INTERNAL_JOB_LIST'] == 'Y';
if($arResult['INTERNAL']) {
    $arCheckCandidateLock = YNSIRAssociateJob::checkCandiateLock($arParams['ENTITY_ID']);
    if(!empty($arCheckCandidateLock) && $arCheckCandidateLock['IS_LOCK'] == 'Y') {
        $this->IncludeComponentTemplate('lock-candidate');
        die();
    }
}
// =================== permission ================================================

$arResult['USER_PERMISSIONS'] = YNSIRPerms_::GetCurrentUserPermissions();
$arSectionPerm = YNSIRConfig::getSectionCandidatePerms();
$arResult['SECTIONS_PERMS'] = $arSectionPerm[YNSIR_PERM_ENTITY_ORDER];

if (!YNSIRJobOrder::CheckReadPermission(0, $arResult['USER_PERMISSIONS'])) {
    ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
    return;
}
else {
    // ========== list status ==========
    $arResult['JO_STATUS'] = YNSIRGeneral::getListJobStatus();
    // ========== list department ==========
    $arResult['DEPARTMENT'] = YNSIRGeneral::getDepartment(array(), false);
    // ========== list template discription job order ==========
    $arResult['JO_TEMPLATE'] = YNSIRJobOrderTemplate::getListAll();
    $arResult['LEVEL'] = YNSIRGeneral::getListType(array('ENTITY' => YNSIRConfig::TL_WORK_POSITION), true);
    // ========== header ==========
    $arResult['HEADERS'] = array(
        array('id' => 'ID', 'name' => GetMessage("YNSIR_CJOL_FIELD_ID"), 'sort' => 'ID', 'default' => false),
        array('id' => 'CREATED_BY', 'name' => GetMessage("YNSIR_CJOL_FIELD_CREATED_BY"), 'sort' => 'CREATED_BY', 'default' => false),
        array('id' => 'MODIFIED_BY', 'name' => GetMessage("YNSIR_CJOL_FIELD_MODIFIED_BY"), 'sort' => 'MODIFIED_BY', 'default' => false),
        array('id' => 'DATE_CREATE', 'name' => GetMessage("YNSIR_CJOL_FIELD_DATE_CREATE"), 'sort' => 'DATE_CREATE', 'default' => false),
        array('id' => 'DATE_MODIFY', 'name' => GetMessage("YNSIR_CJOL_FIELD_DATE_MODIFY"), 'sort' => 'DATE_MODIFY', 'default' => false),
        array('id' => 'HEADCOUNT', 'name' => GetMessage("YNSIR_CJOL_FIELD_HEADCOUNT"), 'sort' => 'HEADCOUNT', 'default' => false),
        array('id' => 'TITLE', 'name' => GetMessage("YNSIR_CJOL_FIELD_TITLE"), 'sort' => 'TITLE', 'default' => true),
        array('id' => 'LEVEL', 'name' => GetMessage("YNSIR_CJOL_FIELD_LEVEL"), 'sort' => 'LEVEL', 'default' => true),
        array('id' => 'DEPARTMENT', 'name' => GetMessage("YNSIR_CJOL_FIELD_DEPARTMENT"), 'sort' => 'DEPARTMENT', 'default' => true),
        array('id' => 'EXPECTED_END_DATE', 'name' => GetMessage("YNSIR_CJOL_FIELD_EXPECTED_END_DATE"), 'sort' => 'EXPECTED_END_DATE', 'default' => true),
        array('id' => 'STATUS', 'name' => GetMessage("YNSIR_CJOL_FIELD_STATUS"), 'sort' => 'STATUS', 'default' => true),
        array('id' => 'VACANCY_REASON', 'name' => GetMessage("YNSIR_CJOL_FIELD_VACANCY_REASON"), 'sort' => 'VACANCY_REASON', 'default' => false),
        array('id' => 'IS_REPLACE', 'name' => GetMessage("YNSIR_CJOL_FIELD_TYPE"), 'sort' => 'IS_REPLACE', 'default' => false),
        array('id' => 'SALARY_FROM', 'name' => GetMessage("YNSIR_CJOL_FIELD_SALARY_FROM"), 'sort' => 'SALARY_FROM', 'default' => false),
        array('id' => 'SALARY_TO', 'name' => GetMessage("YNSIR_CJOL_FIELD_SALARY_TO"), 'sort' => 'SALARY_TO', 'default' => false),
        array('id' => 'TEMPLATE_ID', 'name' => GetMessage("YNSIR_CJOL_FIELD_TEMPLATE"), 'sort' => 'TEMPLATE_ID', 'default' => false),
        array('id' => 'DESCRIPTION', 'name' => GetMessage("YNSIR_CJOL_FIELD_DESCRIPTION"), 'sort' => 'DESCRIPTION', 'default' => false),
        array('id' => 'SUPERVISOR', 'name' => GetMessage("YNSIR_CJOL_FIELD_SUPERVISOR"), 'sort' => 'SUPERVISOR', 'default' => false),
        array('id' => 'OWNER', 'name' => GetMessage("YNSIR_CJOL_FIELD_OWNER"), 'sort' => 'OWNER', 'default' => false),
        array('id' => 'RECRUITER', 'name' => GetMessage("YNSIR_CJOL_FIELD_RECRUITER"), 'sort' => 'RECRUITER', 'default' => false),
        //array('id' => 'INTERVIEW', 'name' => GetMessage("YNSIR_CJOL_FIELD_INTERVIEW"), 'sort' => 'INTERVIEW','default' => true),
        array('id' => 'SUBORDINATE', 'name' => GetMessage("YNSIR_CJOL_FIELD_SUBORDINATE"), 'sort' => 'SUBORDINATE', 'default' => false),
    );
    // ========== list field filter ==========
    $arResult['FILTER'] = array(
        array('id' => 'ID', 'name' => GetMessage("YNSIR_CJOL_FIELD_ID"), 'type' => 'string', 'default' => true),
        array('id' => 'CREATED_BY', 'name' => GetMessage("YNSIR_CJOL_FIELD_CREATED_BY"), 'type' => 'custom_entity', 'default' => true,
            'selector' => array('TYPE' => 'user', 'DATA' => array('ID' => 'CREATED_BY', 'FIELD_ID' => 'CREATED_BY'))
        ),
        array('id' => 'MODIFIED_BY', 'name' => GetMessage("YNSIR_CJOL_FIELD_MODIFIED_BY"), 'type' => 'custom_entity', 'default' => true,
            'selector' => array('TYPE' => 'user', 'DATA' => array('ID' => 'MODIFIED_BY', 'FIELD_ID' => 'MODIFIED_BY'))
        ),
        array('id' => 'DATE_CREATE', 'name' => GetMessage("YNSIR_CJOL_FIELD_DATE_CREATE"), 'type' => 'date', 'default' => true),
        array('id' => 'DATE_MODIFY', 'name' => GetMessage("YNSIR_CJOL_FIELD_DATE_MODIFY"), 'type' => 'date', 'default' => true),
        array('id' => 'HEADCOUNT', 'name' => GetMessage("YNSIR_CJOL_FIELD_HEADCOUNT"), 'type' => 'string', 'default' => true),
        array('id' => 'TITLE', 'name' => GetMessage("YNSIR_CJOL_FIELD_TITLE"), 'type' => 'string', 'default' => true),
        array('id' => 'LEVEL', 'name' => GetMessage("YNSIR_CJOL_FIELD_LEVEL"), 'type' => 'list', 'default' => true,
            'items' => $arResult['LEVEL']
        ),
        array('id' => 'DEPARTMENT', 'name' => GetMessage("YNSIR_CJOL_FIELD_DEPARTMENT"), 'type' => 'list', 'default' => true,
            'items' => $arResult['DEPARTMENT']
        ),
        array('id' => 'EXPECTED_END_DATE', 'name' => GetMessage("YNSIR_CJOL_FIELD_EXPECTED_END_DATE"), 'type' => 'string', 'default' => true),
        array('id' => 'STATUS', 'name' => GetMessage("YNSIR_CJOL_FIELD_STATUS"), 'type' => 'list', 'default' => true,
            'items' => $arResult['JO_STATUS']
        ),
        array('id' => 'VACANCY_REASON', 'name' => GetMessage("YNSIR_CJOL_FIELD_VACANCY_REASON"), 'type' => 'string', 'default' => true),
        array('id' => 'IS_REPLACE', 'name' => GetMessage("YNSIR_CJOL_FIELD_TYPE"), 'type' => 'list',
            'items' => array('2' => GetMessage("YNSIR_CJOL_FIELD_TYPE_NEW"), '1' => GetMessage("YNSIR_CJOL_FIELD_TYPE_REPLACE"))
        ),
        array('id' => 'SALARY', 'name' => GetMessage('YNSIR_CJOL_FIELD_SALARY'), 'type' => 'number', 'default' => true),
        array('id' => 'TEMPLATE_ID', 'name' => GetMessage("YNSIR_CJOL_FIELD_TEMPLATE"), 'type' => 'list',
            'items' => $arResult['JO_TEMPLATE']
        ),
        array('id' => 'DESCRIPTION', 'name' => GetMessage("YNSIR_CJOL_FIELD_DESCRIPTION"), 'type' => 'string', 'default' => true),
        array('id' => 'SUPERVISOR', 'name' => GetMessage("YNSIR_CJOL_FIELD_SUPERVISOR"), 'type' => 'custom_entity', 'default' => true,
            'selector' => array('TYPE' => 'user', 'DATA' => array('ID' => 'SUPERVISOR', 'FIELD_ID' => 'SUPERVISOR'))
        ),
        array('id' => 'OWNER', 'name' => GetMessage("YNSIR_CJOL_FIELD_OWNER"), 'type' => 'custom_entity', 'default' => true,
            'selector' => array('TYPE' => 'user', 'DATA' => array('ID' => 'OWNER', 'FIELD_ID' => 'OWNER'))
        ),
        array('id' => 'RECRUITER', 'name' => GetMessage("YNSIR_CJOL_FIELD_RECRUITER"), 'type' => 'custom_entity', 'default' => true,
            'selector' => array('TYPE' => 'user', 'DATA' => array('ID' => 'RECRUITER', 'FIELD_ID' => 'RECRUITER'))
        ),
        //array('id' => 'INTERVIEW', 'name' => 'INTERVIEW', 'type' => 'string', 'default' => true),
        array('id' => 'SUBORDINATE', 'name' => GetMessage("YNSIR_CJOL_FIELD_SUBORDINATE"), 'type' => 'custom_entity', 'default' => true,
            'selector' => array('TYPE' => 'user', 'DATA' => array('ID' => 'SUBORDINATE', 'FIELD_ID' => 'SUBORDINATE'))
        ),
    );
    // ========== filter ==========
    $arResult['GRID_ID'] = 'YNSI_JOB_ORDER_LIST';
    $arFilter = $arSort = $arResult['FILTER_PRESETS'] = array();
    if (($arResult['INTERNAL'])) $arResult['GRID_ID'] .= '_INTERNAL';
    // ========== action for list job order ==========
    /*
     *TODO: REQUEST ACTION
     * Update by nhatth2
     */
    if (check_bitrix_sessid() && isset($_REQUEST['action_button_' . $arResult['GRID_ID']])) {

        switch ($_REQUEST['action_button_' . $arResult['GRID_ID']]) {
            case 'delete':
                //DELETE (--DEACTIVE--) ACTION FOR GRID JOB ORDER
                $APPLICATION->RestartBuffer();
                $arFilterDel = array('ID' => $_REQUEST['ID']);
                $arIDdel = array();
                $obRes = YNSIRJobOrder::GetList(array(), $arFilterDel, array('GROUP_BY' => 'ID'), false, array('ID'));
                while ($arOrder = $obRes->Fetch()) {
                    $ID = $arOrder['ID'];
                    $arEntityAttr = $arResult['USER_PERMISSIONS']->GetEntityAttr(YNSIR_PERM_ENTITY_ORDER, array($ID));
                    if (!$arResult['USER_PERMISSIONS']->CheckEnityAccess(YNSIR_PERM_ENTITY_ORDER, 'DELETE', $arEntityAttr[$ID])) {
                        continue;
                    }
                    $arIDdel[] = $arOrder['ID'];
                }
                YNSIRJobOrder::DeActivebyArray($arIDdel);
                break;

            case 'associate':
                //INTERNAL ASSOCIATE
                $APPLICATION->RestartBuffer();
                $strTypeAssociate = $_REQUEST['PARAMS']['params']['ENTITY_TYPE'];

                if ($strTypeAssociate == YNSIR_CANDIDATE) {
                    $arFilterAssociate = array('ID' => $_REQUEST['ID']);
                    $arIDOrderInsert = array();

                    //temp GET CANDIDATE
                    $arRes = YNSIRCandidate::GetByID($_REQUEST['CURRENT_TYPE_ID']);
                    if (intval($arRes['ID']) > 0 && !empty($arFilterAssociate['ID'])) {
                        $obRes = YNSIRJobOrder::GetListJobOrder(array(), $arFilterAssociate, false, false,array());
                        while ($arOrder = $obRes->Fetch()) {
                            if($arOrder['STATUS'] != JOStatus::JOSTATUS_CLOSED)
                                $arIDOrderInsert[] = $arOrder['ID'];
                        }
                        unset($arOrder);
                        //Remove Candidates have already Add to Job before
                        $dbResultAssociate = YNSIRAssociateJob::GetList(
                            array(),
                            array('CANDIDATE_ID' => $_REQUEST['CURRENT_TYPE_ID'], 'ORDER_JOB_ID' => $arIDOrderInsert),
                            false,
                            array(),
                            array('ORDER_JOB_ID'));
                        $arOrderAlready = array();
                        while ($arAssociate = $dbResultAssociate->Fetch()) {
                            $arOrderAlready[] = $arAssociate['ORDER_JOB_ID'];
                        }
                        unset($dbResultAssociate);
                        unset($arAssociate);
                        //End

                        foreach ($arIDOrderInsert as $ID_CA) {
                            if (in_array($ID_CA, $arOrderAlready)) continue;
                            $arAssociateInsert = array(
                                'CANDIDATE_ID' => $arRes['ID'],
                                'ORDER_JOB_ID' => $ID_CA,
                                'STATUS_ID' => $arRes['CANDIDATE_STATUS'],
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
    /*
     * End TODO: REQUEST ACTION
     */
    // ========== internal filter : not require ==========
    if ($arResult['INTERNAL']) {
        //TODO: Internal Associate
        $dbResultAssociate = YNSIRAssociateJob::GetList(
            array(),
            array('CANDIDATE_ID' => $arParams['ENTITY_ID']),
            false,
            false,
            array('ORDER_JOB_ID'));
        while ($arCandidateRes = $dbResultAssociate->GetNext()) {
            $arFilter['!ID'][] = $arCandidateRes['ORDER_JOB_ID'];
        }
            //No closed Status
        $arFilter['!STATUS'] = JOStatus::JOSTATUS_CLOSED;

        unset($arCandidateRes);
        unset($dbResultAssociate);
        //ENd
    }

    // ========== internal filter : not require ==========
    if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER'])) {
        $arFilter = $arParams['INTERNAL_FILTER'];
    }

    $arParams['PAGE_COUNT'] = intval($arParams['PAGE_COUNT']) > 0 ? intval($arParams['PAGE_COUNT']) : 20;
    $arNavParams = array(
        'nPageSize' => $arParams['PAGE_COUNT']
    );
    $arNavigation = CDBResult::GetNavParams($arNavParams);
    $gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
    $filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
    $arNavParams = $gridOptions->GetNavParams($arNavParams);

    $arNavigation = CDBResult::GetNavParams($arNavParams);
    $gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
    $filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
    $arNavParams = $gridOptions->GetNavParams($arNavParams);
    $arNavParams['bShowAll'] = false;
//    --ACTIVE ELEMENT--
    $arFilter['ACTIVE'] = 1;
    $arFilter += $filterOptions->getFilter($arResult['FILTER']);

    $arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
    $arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
    $arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
    $arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';
    $arResult['EXTERNAL_SALES'] = CCrmExternalSaleHelper::PrepareListItems();
    $arResult['CALL_LIST_UPDATE_MODE'] = isset($_REQUEST['call_list_context']) && isset($_REQUEST['call_list_id']) && IsModuleInstalled('voximplant');
    $arResult['CALL_LIST_CONTEXT'] = (string)$_REQUEST['call_list_context'];
    $arResult['CALL_LIST_ID'] = (int)$_REQUEST['call_list_id'];

    $arParams['PATH_TO_JO_EDIT'] = ' /recruitment/job-order/edit/#id#/';
    $arParams['PATH_TO_JO_DETAIL'] = ' /recruitment/job-order/detail/#id#/';
    // ========== find in filter ==========
    if (isset($arFilter['FIND'])) {
        if (is_string($arFilter['FIND'])) {
            $find = trim($arFilter['FIND']);
            if ($find !== '') {
                $arFilter['SEARCH_CONTENT'] = $find;
            }
        }
        unset($arFilter['FIND']);
    }
    // ========== prepare filter ==========
    $requisite = new \Bitrix\Crm\EntityRequisite();
    $requisite->prepareEntityListFilter($arFilter);

    // Immutable : filter exact match
    $arImmutableFilters = array(
        "ID", "!ID", "SEARCH_CONTENT", "CREATED_BY", "MODIFIED_BY", "DEPARTMENT", "STATUS", "!STATUS", "IS_REPLACE", "TEMPLATE_ID", "SUPERVISOR", "OWNER", "RECRUITER", "SUBORDINATE"
    );

    foreach ($arFilter as $k => $v) {
        // filter exact match
        if (in_array($k, $arImmutableFilters, true)) {
            if ($k == 'ID') {
                $arIDIO = explode(',', $arFilter[$k]);
                unset($arFilter[$k]);
                foreach ($arIDIO as $itemIdSearch) {
                    $arFilter[$k][] = intval($itemIdSearch);
                }
            }
            continue;
        }

        $arMatch = array();
        // filter field from -> to
        if (preg_match('/(.*)_from$/i' . BX_UTF_PCRE_MODIFIER, $k, $arMatch)) {
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
        } // filter login : and or xor...
        elseif (in_array($k, $arResult['FILTER2LOGIC'])) {
            // Bugfix #26956 - skip empty values in logical filter
            $v = trim($v);
            if ($v !== '') {
                $arFilter['?' . $k] = $v;
            }
            unset($arFilter[$k]);
        } elseif ($k == 'SEARCH_CONTENT') {
            $arFilter['%' . $k] = str_rot13($v);
            unset($arFilter[$k]);
        } // filter like
        elseif ($k != 'ID' && $k != 'LOGIC' && $k != '__INNER_FILTER' && $k != '__JOINS' && $k != '__CONDITIONS' && strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1) {
            $arFilter['%' . $k] = $v;
            unset($arFilter[$k]);
        }
    }

    \Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

    // get sort
    $_arSort = $gridOptions->GetSorting(array(
        'sort' => array('ID' => 'asc'),
        'vars' => array('by' => 'by', 'order' => 'order')
    ));

    $arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
    $arResult['SORT_VARS'] = $_arSort['vars'];

    $arSelect = $gridOptions->GetVisibleColumns();

    //region Navigation data initialization
    $pageNum = 0;
    $pageSize = (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['PAGE_COUNT']);
    $enableNextPage = false;

    if (isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y') {
        $pageNum = 1;
    } elseif ($pageSize > 0 && isset($_REQUEST['page']) || true) {
        $pageNum = (int)$_REQUEST['page'];
        // NOTE : reset page number
        if ($pageNum < 0) {
            // Backward mode : Last page
            $offset = -($pageNum + 1);
            $gridData = $_SESSION['YNSIR_JO_PAGINATION_DATA'][$arResult['GRID_ID']];
            $filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
            $result = YNSIRJobOrder::GetListJobOrder(array(), $filter, array(), false, array());
            $pageNum = (int)(ceil($result / $pageSize));
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
        if (!isset($_SESSION['YNSIR_JO_PAGINATION_DATA'])) {
            $_SESSION['YNSIR_JO_PAGINATION_DATA'] = array();
        }
        $_SESSION['YNSIR_JO_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum);
    } else {
        if (!$bInternal
            && !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
            && isset($_SESSION['YNSIR_JO_PAGINATION_DATA'])
            && isset($_SESSION['YNSIR_JO_PAGINATION_DATA'][$arResult['GRID_ID']])
            && isset($_SESSION['YNSIR_JO_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'])
        ) {
            $pageNum = (int)$_SESSION['YNSIR_JO_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'];
        }

        if ($pageNum <= 0) {
            $pageNum = 1;
        }
    }
    //endregion
    // TODO ========== Share filter session for get total ==========
    $_SESSION['YNSIR_JO_PAGINATION_DATA'][$arResult['GRID_ID']]['FILTER'] = $arFilter;

    $arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
    $navListOptions = array_merge($arOptions, array(
            'QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)))
    );

    $arNavParams['nTopCount'] = 1;
    $arSort = $arResult['SORT'];
    // ========== filter data job order ==========
    $obRes = YNSIRJobOrder::GetList(
        $arSort,
        $arFilter,
        array('GROUP_BY' => 'ID'),
        false,
        $navListOptions
    );
    $arResult['ID_JOB_ORDER'] = array();
    $qty = 0;
    while ($arData = $obRes->Fetch()) {
        if ($pageSize > 0 && ++$qty > $pageSize) {
            $enableNextPage = true;
            break;
        }
        $arResult['ID_JOB_ORDER'][] = intval($arData['ID']);
    }
    $arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
    // list field selected
    $arResult['SELECT'] = $arSelect;
    // list field department id
    $arResult['DEPARTMENT_ID'] = array();
    // list subordinates
    $arResult['SUBORDINATE'] = array();
    // list job order
    $arResult['LIST_JOB_ORDER'] = array();
    if (!empty($arResult['ID_JOB_ORDER'])) {
        // level
        $arResult['LEVEL'] = YNSIRGeneral::getListType(array('ENTITY' => YNSIRConfig::TL_WORK_POSITION), true);
        $obJORes = YNSIRJobOrder::GetList($arSort, array('ID' => $arResult['ID_JOB_ORDER']), false, false, $arSelect);
        $arListUser = array();
        $p = new blogTextParser();
        while ($arData = $obJORes->Fetch()) {
            // get user id
            //TODO: GET PERMISSION EACH JOB ORDER FIELD
            $isReadPermsSec = array();
            foreach($arResult['SECTIONS_PERMS']['FIELDS'] as $KEY_SECTION => $NAME_SECTION) {
                $isReadPermsSec[$KEY_SECTION] = YNSIRJobOrder::CheckReadPermission($arData['ID'], $arResult['USER_PERMISSIONS'], $KEY_SECTION);
            }
            unset($KEY_SECTION);
            unset($NAME_SECTION);
            foreach ($arResult['FIELDS_BY_SECTION'] as $KEY_SECTION => $arFieldSection) {
                if($isReadPermsSec[$KEY_SECTION]) continue;
                else {
                    foreach($arFieldSection['FIELD'] as $KEY_FIELDS => $NAME_FIELDS) {
                        $arData[$KEY_FIELDS] = '';
                    }
                    unset($KEY_FIELDS);
                    unset($NAME_FIELDS);
                }
            }
            // level
            $arData['LEVEL'] = $arResult['LEVEL'][$arData['LEVEL']];
            // expect date
            $arData['EXPECTED_END_DATE'] = $DB->FormatDate($arData['EXPECTED_END_DATE'], $arResult['FORMAT_DB_BX_FULL'], $arResult['FORMAT_DB_BX_SHORT']);

            $arResult['FORMAT_DB_BX_FULL'] = CSite::GetDateFormat("FULL");
            $arResult['FORMAT_DB_BX_SHORT'] = CSite::GetDateFormat("SHORT");

            unset($KEY_SECTION);
            unset($arFieldSection);

            //End Remove permission
            $arListUser[$arData['CREATED_BY']] = $arData['CREATED_BY'];
            $arListUser[$arData['MODIFIED_BY']] = $arData['MODIFIED_BY'];
            $arListUser[$arData['SUPERVISOR']] = $arData['SUPERVISOR'];
            $arListUser[$arData['OWNER']] = $arData['OWNER'];
            $arListUser[$arData['RECRUITER']] = $arData['RECRUITER'];
            // path for action : edit, detail
            $arData['PATH_TO_JO_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_JO_EDIT'], array('id' => $arData['ID']));
            $arData['PATH_TO_JO_DETAIL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_JO_DETAIL'], array('id' => $arData['ID']));
            // get subordinates
            if (!isset($arDataTemp[$iId])) {
                // convert description to html
                if (in_array('DESCRIPTION', $arSelect)) {

                    $arData['DESCRIPTION'] = '<div style="max-height: 80px">'.$p->convert($arData['DESCRIPTION']).'</div>';
                }
                // get list subordinates
                if (in_array('SUBORDINATE', $arSelect)) {
                    $arResult['SUBORDINATE'][$arData['ID']][$arData['SUBORDINATE']] = $arData['SUBORDINATE'];
                    $arListUser[$arData['SUBORDINATE']] = $arData['SUBORDINATE'];
                }
                if (in_array('DEPARTMENT', $arSelect)) {
                    $arResult['DEPARTMENT_ID'][$arData['DEPARTMENT']] = $arData['DEPARTMENT'];
                }
                $arResult['LIST_JOB_ORDER'][$arData['ID']] = $arData;
            }
        }
        $arResult['PERMS']['ADD'] = true;
    }
    // get data basic information user (name, ... photo url)
    $arResult['DATA_USER'] = array();
    if (!empty($arListUser)) {
        $arResult['DATA_USER'] = YNSIRGeneral::getUserInfo($arListUser);
    }
//GROUP ADD BTN:ADD + IMPORT
    $arResult['PERMS']['ADD'] = !$arResult['USER_PERMISSIONS']->HavePerm(YNSIR_PERM_ENTITY_ORDER, YNSIR_PERM_NONE, 'ADD');
    $arResult['PERMS']['WRITE'] = 0;
//DELETE FOR ALL
    $arResult['PERMS']['DELETE'] = !$arResult['USER_PERMISSIONS']->HavePerm(YNSIR_PERM_ENTITY_ORDER, YNSIR_PERM_NONE, 'DELETE');

    $this->IncludeComponentTemplate($sFileTemplate);
}
?>