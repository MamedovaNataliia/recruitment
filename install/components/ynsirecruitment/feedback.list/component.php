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

if (!CModule::IncludeModule("blog")) {
    ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
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
$arResult['DATE_TIME_FORMAT'] = 'f j, Y';
$sFormatName = CSite::GetNameFormat(false);

$arResult['INTERNAL'] = $arParams['INTERNAL'] == 'Y';
if ($arResult['INTERNAL']) {

}
// =================== permission ================================================

if (false) {
    ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
    return;
} else {
    // ========== header ==========
    $arResult['HEADERS'] = array(
        array('id' => 'ID', 'name' => GetMessage("YNSIR_FEEDBACK_ID"), 'sort' => 'ID', 'default' => false),
        array('id' => 'TITLE', 'name' => GetMessage("YNSIR_FEEDBACK_TITLE"), 'sort' => 'TITLE', 'default' => true),
        array('id' => 'CREATED_BY', 'name' => GetMessage("YNSIR_FEEDBACK_CREATED_BY"),  'default' => true),
        array('id' => 'CANDIDATE', 'name' => GetMessage("YNSIR_FEEDBACK_CANDIDATE_ID"), 'default' => true),
        array('id' => 'JOB_ORDER', 'name' => GetMessage("YNSIR_FEEDBACK_JOB_ORDER_ID"), 'default' => true),
        array('id' => 'ROUND_ID', 'name' => GetMessage("YNSIR_FEEDBACK_ROUND_ID"), 'sort' => 'ROUND_ID', 'default' => true),
        array('id' => 'DESCRIPTION', 'name' => GetMessage("YNSIR_FEEDBACK_DESCRIPTION"),  'default' => false),
        array('id' => 'MODIFIED_DATE', 'name' => GetMessage("YNSIR_FEEDBACK_MODIFIED_DATE"), 'sort' => 'MODIFIED_DATE', 'default' => false),
        array('id' => 'CREATED_DATE', 'name' => GetMessage("YNSIR_FEEDBACK_CREATED_DATE"), 'sort' => 'CREATED_DATE', 'default' => false),
        array('id' => 'MODIFIED_BY', 'name' => GetMessage("YNSIR_FEEDBACK_MODIFIED_BY"), 'default' => false),

    );
//    if($arParams['CANDIDATE_OWNER_IS']) > 0{
//        $arResult['HEADERS'][] = array('id' => 'CANDIDATE', 'name' => GetMessage("YNSIR_FEEDBACK_CANDIDATE_ID"), 'sort' => 'ID', 'default' => true);
//    }
    // ========== list field filter ==========
    $arResult['FILTER'] = array(
        array('id' => 'ID', 'name' => GetMessage("YNSIR_FEEDBACK_ID"), 'type' => 'int', 'default' => true),
//        array('id' => 'ROUND_ID', 'name' => GetMessage("YNSIR_FEEDBACK_ROUND_ID"), 'type' => 'list', 'item' => array(), 'default' => true),
        array('id' => 'MODIFIED_DATE', 'name' => GetMessage("YNSIR_FEEDBACK_MODIFIED_DATE"), 'type' => 'date', 'item' => array(), 'default' => true),
        array('id' => 'CREATED_DATE', 'name' => GetMessage("YNSIR_FEEDBACK_CREATED_DATE"), 'type' => 'date', 'item' => array(), 'default' => true),
        array('id' => 'MODIFIED_BY',
            'name' => GetMessage("YNSIR_FEEDBACK_MODIFIED_BY"),
            'type' => 'custom_entity',
            'selector' => array(
                'TYPE' => 'user',
                'DATA' => array('ID' => 'MODIFIED_BY', 'FIELD_ID' => 'MODIFIED_BY'),
                'default' => true
            ),
        ),
        array('id' => 'CREATED_BY',
            'name' => GetMessage("YNSIR_FEEDBACK_CREATED_BY"),
            'type' => 'custom_entity',
            'selector' => array(
                'TYPE' => 'user',
                'DATA' => array('ID' => 'CREATED_BY', 'FIELD_ID' => 'CREATED_BY'),
                'default' => true
            ),
        )
    );
    // ========== filter ==========
    $arResult['GRID_ID'] = 'YNSI_FEEDBACK_LIST';
    $arFilter = $arSort = $arResult['FILTER_PRESETS'] = array();
    if (($arResult['INTERNAL'])) $arResult['GRID_ID'] .= '_INTERNAL';
    // ========== action for list job order ==========

    if (check_bitrix_sessid() && isset($_REQUEST['action_button_' . $arResult['GRID_ID']])) {
        switch ($_REQUEST['action_button_' . $arResult['GRID_ID']]) {
            case 'delete':
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

    $arParams['PATH_TO_FEEDBACK_EDIT'] = ' /recruitment/feedback/edit/#id#/';
    $arParams['PATH_TO_FEEDBACK_DETAIL'] = ' /recruitment/feedback/detail/#id#/';
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
        "ID", "!ID", "ROUND_ID", "SEARCH_CONTENT", "CREATED_BY", "MODIFIED_BY", "DEPARTMENT", "STATUS", "IS_REPLACE", "TEMPLATE_ID", "SUPERVISOR", "OWNER", "RECRUITER", "SUBORDINATE"
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
    if($arParams['CANDIDATE_OWNER_IS'] > 0){
        $arFilter['CANDIDATE_ID'] = $arParams['CANDIDATE_OWNER_IS'];
    }

    if($arParams['JOB_ORDER_OWNER_IS'] > 0){
        $arFilter['JOB_ORDER_ID'] = $arParams['JOB_ORDER_OWNER_IS'];
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
            $gridData = $_SESSION['YNSIR_FEEDBACK_PAGINATION_DATA'][$arResult['GRID_ID']];
            $filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
            $result = YNSIRFeedback::GetListJobOrder(array(), $filter, array(), false, array());
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
        if (!isset($_SESSION['YNSIR_FEEDBACK_PAGINATION_DATA'])) {
            $_SESSION['YNSIR_FEEDBACK_PAGINATION_DATA'] = array();
        }
        $_SESSION['YNSIR_FEEDBACK_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum);
    } else {
        if (!$bInternal
            && !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
            && isset($_SESSION['YNSIR_FEEDBACK_PAGINATION_DATA'])
            && isset($_SESSION['YNSIR_FEEDBACK_PAGINATION_DATA'][$arResult['GRID_ID']])
            && isset($_SESSION['YNSIR_FEEDBACK_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'])
        ) {
            $pageNum = (int)$_SESSION['YNSIR_FEEDBACK_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'];
        }

        if ($pageNum <= 0) {
            $pageNum = 1;
        }
    }
    if (isset($arFilter['SEARCH_CONTENT'])) {
        $v = $arFilter['SEARCH_CONTENT'];
        $arFilter['__INNER_FILTER_S_' . $v] = array(
            'LOGIC' => 'OR',
            '%TITLE' => $v,
            '%FIRST_NAME' => $v,
            '%LAST_NAME' => $v,
            '%JOB_ORDER' => $v,
            '%DESCRIPTION' => $v,
        );
    }
    //endregion
    $arResult['CONFIG']['ROUND'] = YNSIRInterview::getListDetail(array(), array(), false, false, array());

    // TODO ========== Share filter session for get total ==========
    $_SESSION['YNSIR_FEEDBACK_PAGINATION_DATA'][$arResult['GRID_ID']]['FILTER'] = $arFilter;

    $arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
    $navListOptions = array_merge($arOptions, array(
            'QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)))
    );

    $arNavParams['nTopCount'] = 1;
    $arSort = $arResult['SORT'];
    // ========== filter data job order ==========
    $obRes = YNSIRFeedback::GetList(
        $arSort,
        $arFilter,
        false,
        false,
        $navListOptions
    );
    $arResult['FEEDBACK_ID'] = array();
    $qty = 0;
    while ($arData = $obRes->Fetch()) {
        if ($pageSize > 0 && ++$qty > $pageSize) {
            $enableNextPage = true;
            break;
        }
        if (intval($arData['JOB_ORDER_ID']) > 0 && intval($arData['ROUND_ID']) > 0) {
            $index = $arResult['CONFIG']['ROUND'][$arData['JOB_ORDER_ID']][$arData['ROUND_ID']]['ROUND_INDEX'];
            $arData['ROUND_ID'] = GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $index));
        }
        $p = new blogTextParser();
        $arData['DESCRIPTION'] = $p->convert($arData['DESCRIPTION']);
        $arData['PATH_TO_FEEDBACK_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_FEEDBACK_EDIT'], array('id' => $arData['ID']));
        $arData['PATH_TO_FEEDBACK_DETAIL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_FEEDBACK_DETAIL'], array('id' => $arData['ID']));
        $cname = CUser::FormatName(
            $sFormatName,
            array(
                "NAME" => $arData['FIRST_NAME'],
                "LAST_NAME" => $arData['LAST_NAME'],
            )
        );
        $arData['JOB_ORDER'] = '<a href="/recruitment/job-order/detail/' . $arData['JOB_ORDER_ID'] . '/">' . $arData['JOB_ORDER'] . '</a>';
        $arData['CANDIDATE'] = '<a href="/recruitment/candidate/detail/' . $arData['CANDIDATE_ID'] . '/">' . $cname . '</a>';
        $arResult['FEEDBACK_DATA'][$arData['ID']] = $arData;
    }
    $arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
    // list field selected
    $arResult['SELECT'] = $arSelect;

//GROUP ADD BTN:ADD + IMPORT
    $arResult['PERMS']['ADD'] = 1;
    $arResult['PERMS']['WRITE'] = 1;
//DELETE FOR ALL
    $arResult['PERMS']['DELETE'] = 1;

    //Repare feedback data
    $data = array(
        'candidate_id' => $arParams['CANDIDATE_ID'],
        'job_order_id' => $arParams['JOB_ORDER_ID'],
        'round_id' => $arParams['ROUND_ID'],
    );
    $feedbackdata = YNSIRFeedback::getRepareFeedbackData($data);
    $arResult['REPARE_FEEDBACK_DATA'] = $feedbackdata['status'];
    if (!empty($arParams['CANDIDATE_ID']) &&
        !empty($arParams['JOB_ORDER_ID']) &&
        !empty($arParams['ROUND_ID']) &&
        $feedbackdata['status'] === true
    ) {
        $arFilter = array(
            'CANDIDATE_ID' => $arParams['CANDIDATE_ID'],
            'JOB_ORDER_ID' => $arParams['JOB_ORDER_ID'],
            'ROUND_ID' => $arParams['ROUND_ID'],
            'CREATED_BY' => $USER->GetID(),
        );
        $obRes = YNSIRFeedback::GetList(
            $arSort,
            $arFilter,
            false,
            false,
            $navListOptions
        );
        if ($obRes->Fetch()) {
            $arResult['FEEDBACK_ACTION'] = 'EDIT';
        } else {
            $arResult['FEEDBACK_ACTION'] = 'ADD';
        }
    }else{
        $arResult['FEEDBACK_ACTION'] = '';
    }
    $this->IncludeComponentTemplate($sFileTemplate);
}
?>