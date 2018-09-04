<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
CJSCore::Init(array("jquery"));
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
$arResult['DATE_TIME_FORMAT'] = 'f j, Y';
$sFormatName = CSite::GetNameFormat(false);
$available_value = YNSIRMailTemplateScope::GetAllDescriptions();
// =================== permission ================================================


if (false) {
    ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
    return;
} else {

    $arResult['HEADERS'] = array(
        array('id' => 'ID', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_ID'), 'sort' => 'ID', 'default' => false, 'editable' => false),
        array('id' => 'TITLE', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_TITLE'), 'sort' => 'TITLE', 'default' => true, 'editable' => true, 'params' => array('size' => 60)),
        array('id' => 'SORT', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_SORT'), 'sort' => 'SORT', 'default' => true, 'editable' => true),
//        array('id' => 'ENTITY_TYPE', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_ENTITY_TYPE'), 'default' => true, 'editable' => false),
        array('id' => 'SCOPE', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_SCOPE'), 'default' => true, 'editable' => false),
        array('id' => 'IS_ACTIVE', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_IS_ACTIVE'), 'sort' => 'IS_ACTIVE', 'default' => true, 'editable' => true, 'type' => 'checkbox'),
//        array('id' => 'OWNER_FORMATTED', 'name' =>'OWNER_FORMATTED'. GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_OWNER'), 'default' => false, 'editable' => false),
        array('id' => 'OWNER_ID', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_OWNER_ID'), 'default' => false, 'editable' => false),
        array('id' => 'CREATED', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_CREATED'), 'sort' => 'CREATED', 'default' => false, 'editable' => false),

        array('id' => 'EMAIL_FROM', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_EMAIL_FROM'), 'sort' => 'LAST_UPDATED', 'default' => false, 'editable' => false),
        array('id' => 'SUBJECT', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_SUBJECT'), 'sort' => 'LAST_UPDATED', 'default' => false, 'editable' => false),
        array('id' => 'BODY', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_BODY'), 'sort' => 'LAST_UPDATED', 'default' => false, 'editable' => false),
        array('id' => 'CREATED', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_CREATED'), 'sort' => 'LAST_UPDATED', 'default' => false, 'editable' => false),
        array('id' => 'LAST_UPDATED', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_LAST_UPDATED'), 'sort' => 'LAST_UPDATED', 'default' => false, 'editable' => false),
        array('id' => 'EDITOR_ID', 'name' => GetMessage('YNSIR_COLUMN_MAIL_TEMPLATE_EDITOR_ID'), 'sort' => 'LAST_UPDATED', 'default' => false, 'editable' => false),
    );

    // ========== list field filter ==========
    $arResult['FILTER'] = array(
        array('id' => 'ID', 'name' => GetMessage("YNSIR_COLUMN_MAIL_TEMPLATE_ID"), 'type' => 'string', 'default' => true),
        array('id' => 'BODY', 'name' => GetMessage("YNSIR_COLUMN_MAIL_TEMPLATE_BODY"), 'type' => 'string', 'default' => true),
        array('id' => 'CREATED', 'name' => GetMessage("YNSIR_COLUMN_MAIL_TEMPLATE_CREATED"), 'type' => 'date', 'default' => true),
        array('id' => 'LAST_UPDATED', 'name' => GetMessage("YNSIR_COLUMN_MAIL_TEMPLATE_LAST_UPDATED"), 'type' => 'date', 'default' => true),
        array('id' => 'SCOPE', 'name' => GetMessage("YNSIR_COLUMN_MAIL_TEMPLATE_SCOPE"), 'type' => 'list', 'default' => true,'items'=>$available_value),
    );


    // ========== filter ==========
    $arResult['GRID_ID'] = 'YNSI_EMAIL_TEMPLATE';
    $arFilter = $arSort = $arResult['FILTER_PRESETS'] = array();
    if (($arResult['INTERNAL'])) $arResult['GRID_ID'] .= '_INTERNAL';

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

    $arParams['PATH_TO_JO_EDIT'] = '/recruitment/config/mailtemplate/edit/#id#/';
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
        "ID", "!ID", "SEARCH_CONTENT", "OWNER_ID", "MODIFIED_BY", "DEPARTMENT", "STATUS", "!STATUS", "IS_REPLACE", "TEMPLATE_ID", "SUPERVISOR", "OWNER", "RECRUITER", "SUBORDINATE"
    );
    // ========== action ==========

    if (check_bitrix_sessid() && isset($_REQUEST['action_button_' . $arResult['GRID_ID']])) {

        switch ($_REQUEST['action_button_' . $arResult['GRID_ID']]) {
            case 'delete':
                //$APPLICATION->RestartBuffer();
                $arFilterDel = array('ID' => $_REQUEST['ID']);
                $arIDdel = array();
                $obRes = YNSIRMailTemplate::GetList(array(), $arFilterDel, array('GROUP_BY' => 'ID'), false, array('ID'));
                while ($arEmail = $obRes->Fetch()) {
                    $ID = $arEmail['ID'];
                    $arIDdel[] = $arEmail['ID'];
                }
                YNSIRMailTemplate::DeActivebyArray($arIDdel);
                break;
            default:
                break;
        }
    }

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
            $gridData = $_SESSION['YNSIR_MT_PAGINATION_DATA'][$arResult['GRID_ID']];
            $filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
//            $filter['IS_ACTIVE'] = 'Y';
            $result = YNSIRMailTemplate::GetList(array(), $filter, array(), false, array());
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
        if (!isset($_SESSION['YNSIR_MT_PAGINATION_DATA'])) {
            $_SESSION['YNSIR_MT_PAGINATION_DATA'] = array();
        }
        $_SESSION['YNSIR_MT_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum);
    } else {
        if (!$bInternal
            && !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
            && isset($_SESSION['YNSIR_MT_PAGINATION_DATA'])
            && isset($_SESSION['YNSIR_MT_PAGINATION_DATA'][$arResult['GRID_ID']])
            && isset($_SESSION['YNSIR_MT_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'])
        ) {
            $pageNum = (int)$_SESSION['YNSIR_MT_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'];
        }

        if ($pageNum <= 0) {
            $pageNum = 1;
        }
    }
    //endregion
    // TODO ========== Share filter session for get total ==========
    if (!empty($arFilter['SEARCH_CONTENT'])) {
        $v = $arFilter['SEARCH_CONTENT'];
        $v = trim($v);
        $arFilter['__INNER_FILTER' . $v] = array(
            'LOGIC' => 'OR',
            '%TITLE' => $v,
            '%EMAIL_FROM' => $v,
            '%SUBJECT' => $v,
            '%BODY' => $v
        );
    }
    $_SESSION['YNSIR_MT_PAGINATION_DATA'][$arResult['GRID_ID']]['FILTER'] = $arFilter;

    $arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
    $navListOptions = array_merge($arOptions, array(
            'QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)))
    );

    $arNavParams['nTopCount'] = 1;
    $arSort = $arResult['SORT'];
    if(!$USER->isAdmin()){
        $userID = $USER->GetID();
        $arFilter['__INNER_FILTER_P'] = array(
            'LOGIC'=>'OR',
            'SCOPE'=>YNSIRMailTemplateScope::Common,
            'OWNER_ID' => $userID
        );
    }
    // ========== filter data  ==========
    $obRes = YNSIRMailTemplate::GetList(
        $arSort,
        $arFilter,
        false,
        false,
        $navListOptions
    );
    $arResult['EMAIL_TEMMPLATE'] = array();
    $qty = 0;

    while ($arData = $obRes->Fetch()) {
        if ($pageSize > 0 && ++$qty > $pageSize) {
            $enableNextPage = true;
            break;
        }
        $arOwnerTOOLTIP = YNSIRHelper::getTooltipandPhotoUser( $arData['OWNER_ID'], 'OWNER_ID');
        $arData['OWNER_ID'] = $arOwnerTOOLTIP['TOOLTIP'];

        $arEDITOR_IDTOOLTIP = YNSIRHelper::getTooltipandPhotoUser( $arData['EDITOR_ID'], 'EDITOR_ID');
        $arData['EDITOR_ID'] = $arEDITOR_IDTOOLTIP['TOOLTIP'];

        $arData['CREATED'] = FormatDateEx($arData['CREATED'], $arResult['FORMAT_DB_BX_FULL'], $arResult['DATE_TIME_FORMAT']);
        $arData['LAST_UPDATED'] = FormatDateEx($arData['LAST_UPDATED'], $arResult['FORMAT_DB_BX_FULL'], $arResult['DATE_TIME_FORMAT']);
        $arData['SCOPE']  = $available_value[$arData['SCOPE']];
        $arData['PATH_TO_JO_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_JO_EDIT'], array('id' => $arData['ID']));
        $arData['PATH_TO_JO_DETAIL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_JO_DETAIL'], array('id' => $arData['ID']));
        $arResult['EMAIL_TEMMPLATE'][$arData['ID']] = $arData;
    }
    $arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);

    //GROUP ADD BTN:ADD + IMPORT
    $arResult['PERMS']['ADD'] = 1;
    $arResult['PERMS']['WRITE'] = 0;
    //DELETE FOR ALL
    $arResult['PERMS']['DELETE'] = 1;

    $this->IncludeComponentTemplate();
}
?>