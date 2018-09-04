<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('ynsirecruitment')) {
    ShowError(GetMessage('YNSIR_MODULE_NOT_INSTALLED'));
    return;
}

if (!CModule::IncludeModule('ynsirecruitment')) {
    ShowError(GetMessage('YNSIR_MODULE_NOT_INSTALLED_SALE'));
    return;
}
if (!CModule::IncludeModule('crm')) {
    ShowError(GetMessage('YNSIR_MODULE_NOT_INSTALLED_SALE'));
    return;
}
if (!CModule::IncludeModule('bizproc'))
    return false;

/** @global CMain $APPLICATION */
global $USER_FIELD_MANAGER, $USER, $APPLICATION, $DB;

use Bitrix\Crm\Category\DealCategory;

//$userPermissions = YNSIRPerms_::GetCurrentUserPermissions();
//if (!CCrmDeal::CheckReadPermission(0, $userPermissions))
//{
//	ShowError(GetMessage('YNSIR_PERMISSION_DENIED'));
//	return;
//}


$userID = YNSIRSecurityHelper::GetCurrentUserID();
$isAdmin = YNSIRPerms_::IsAdmin();

$arResult['CURRENT_USER_ID'] = YNSIRSecurityHelper::GetCurrentUserID();
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]);

$arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
$arResult['SESSION_ID'] = bitrix_sessid();
$arResult['PRESERVE_HISTORY'] = isset($arParams['PRESERVE_HISTORY']) ? $arParams['PRESERVE_HISTORY'] : false;
$arResult['PATH_TO_ASSOCIATE_DELETE'] =  CHTTP::urlAddParams($arParams['PATH_TO_ASSOCIATE_LIST'], array('sessid' => bitrix_sessid()));
$arParams['PATH_TO_JOB_ORDER_DETAIL'] = (strlen($arParams['PATH_TO_JOB_ORDER_DETAIL']) > 0)? $arParams['PATH_TO_JOB_ORDER_DETAIL']:'/recruitment/job-order/detail/#order_id#/';
$arResult['STATUS_LOCK'] = YNSIRConfig::getCandiateStatusLock();

CUtil::InitJSCore(array('ajax', 'tooltip'));

$arFilter = $arSort = array();
$bInternal = false;
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
$arResult['CONFIG']['COLUMN_TITLE'] = YNSIRConfig::GetFieldAssociate();
$arResult['CONFIG']['CANDIDATE_STATUS_LIST'] = YNSIRGeneral::getListJobStatus(YNSIRConfig::CANDIDATE_STATUS);

unset($status_id);
unset($status_obj);

if (!empty($arParams['INTERNAL_FILTER']))
    $bInternal = true;
$arResult['INTERNAL'] = $bInternal;
if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER'])) {
    if (empty($arParams['GRID_ID_SUFFIX'])) {
        $arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null ? strtoupper($this->GetParent()->GetName()) : '';
    }

    $arFilter = $arParams['INTERNAL_FILTER'];
}

if (!empty($arParams['INTERNAL_SORT']) && is_array($arParams['INTERNAL_SORT']))
    $arSort = $arParams['INTERNAL_SORT'];

$enableWidgetFilter = !$bInternal && isset($_REQUEST['WG']) && strtoupper($_REQUEST['WG']) === 'Y';

$arResult['IS_EXTERNAL_FILTER'] = ($enableWidgetFilter || $enableCounterFilter);


$arResult['GRID_ID'] = 'YNSIR_ASSOCIATE_LIST_V12' . (!empty($arParams['GRID_ID_SUFFIX']) ? '_' . $arParams['GRID_ID_SUFFIX'] : '');
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();

$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';


$arResult['HEADERS'] = array(
    array('id' => 'ID', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['ID'], 'sort' => 'id', 'first_order' => 'desc',  'type' => 'int', 'class' => 'minimal'),
);
//Invisible Candidate/Order in Current Link
if($arParams['ENTITY_TYPE'] == YNSIR_JOB_ORDER) {
    $arResult['HEADERS'][] = array('id' => 'CANDIDATE_FULL_NAME', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['CANDIDATE_ID'], 'sort' => 'candidate_id', 'width' => 200, 'default' => true,);
    $arResult['FILTER']  = array(array('id' => 'CANDIDATE_FULL_NAME', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['CANDIDATE_ID']));
} elseif($arParams['ENTITY_TYPE'] == YNSIR_CANDIDATE) {
    $arResult['HEADERS'][] =  array('id' => 'ORDER_JOB_TITLE', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['ORDER_JOB_ID'], 'sort' => 'order_job_id', 'width' => 200, 'default' => true);
    $arResult['FILTER']  = array(array('id' => 'ORDER_JOB_TITLE', 'name' =>  $arResult['CONFIG']['COLUMN_TITLE']['ORDER_JOB_ID']));
}

$arResult['HEADERS'] = array_merge(
    $arResult['HEADERS'],
    array(
        array('id' => 'STATUS', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['STATUS_ID'], 'sort' => 'status_id', 'default' => true),
        array('id' => 'STATUS_ROUND', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['STATUS_ROUND_ID'], 'sort' => 'status_round_id', 'default' => true, 'prevent_default' => false),

        array('id' => 'CREATED_DATE', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['CREATED_DATE'], 'sort' => 'created_date', 'first_order' => 'desc', 'class' => 'date'),
        array('id' => 'CREATED_BY_FULL_NAME', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['CREATED_BY'], 'sort' => 'created_by', 'class' => 'username'),
        array('id' => 'MODIFIED_DATE', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['MODIFIED_DATE'], 'sort' => 'modified_date', 'first_order' => 'desc', 'default' => true, 'class' => 'date'),
        array('id' => 'MODIFIED_BY_FULL_NAME', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['MODIFIED_BY'], 'sort' => 'modified_by', 'class' => 'username'),
    )
);

$arResult['FILTER'] = array_merge(
    $arResult['FILTER'],
    array(
        array('id' => 'ID', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['ID']),
        array('id' => 'STATUS_ID', 'params' => array('multiple' => 'Y'), 'name' => $arResult['CONFIG']['COLUMN_TITLE']['STATUS_ID'],  'type' => 'list', 'items' => $arResult['CONFIG']['CANDIDATE_STATUS_LIST']),
        array('id' => 'CREATED_DATE', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['CREATED_DATE'], 'type' => 'date'),
        array(
            'id' => 'CREATED_BY',
            'name' => $arResult['CONFIG']['COLUMN_TITLE']['CREATED_BY'],
            'type' => 'custom_entity',
            'selector' => array(
                'TYPE' => 'user',
                'DATA' => array('ID' => 'created_by', 'FIELD_ID' => 'CREATED_BY')
            )
        ),
        array('id' => 'MODIFIED_DATE', 'name' => $arResult['CONFIG']['COLUMN_TITLE']['MODIFIED_DATE'], 'type' => 'date'),
        array(
            'id' => 'MODIFIED_BY',
            'name' => GetMessage('YNSIR_COLUMN_MODIFIED_BY'),
            'type' => 'custom_entity',
            'selector' => array(
                'TYPE' => 'user',
                'DATA' => array('ID' => 'modify_by', 'FIELD_ID' => 'MODIFIED_BY')
            )
        ),
    ));
if($arParams['ENTITY_TYPE'] == YNSIR_JOB_ORDER) {
    //===============================>
    //TODO:Get Round for filter if Associate in Order page
    $arResultInterview = YNSIRInterview::getListDetail(array(), array('JOB_ORDER' => $arParams['ENTITY_ID']), false, false, array());
    foreach ($arResultInterview as $id_order => $each_order) {
        foreach ($each_order as $interview_id => $interview) {
            $arResultInterviewNormalize[$interview['ID']] = GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $interview['ROUND_INDEX']));
        }
        unset($interview_id);
        unset($interview);
    }
    unset($id_order);
    unset($each_order);
    //<===============================
    $arResult['FILTER'][] = array('id' => 'STATUS_ROUND_ID', 'params' => array('multiple' => 'Y'), 'name' => $arResult['CONFIG']['COLUMN_TITLE']['STATUS_ROUND_ID'], 'type' => 'list', 'items' => $arResultInterviewNormalize);
}

if (!$bInternal) {

}

//region Try to extract user action data
// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
$actionData = array(
    'METHOD' => $_SERVER['REQUEST_METHOD'],
    'ACTIVE' => false
);

if (check_bitrix_sessid()) {
    $postAction = 'action_button_' . $arResult['GRID_ID'];
    $getAction = 'action_' . $arResult['GRID_ID'];
    //We need to check grid 'controls'
    $controls = isset($_POST['controls']) && is_array($_POST['controls']) ? $_POST['controls'] : array();
    if($actionData['METHOD'] == 'POST' && ($_REQUEST[$getAction] == 'delete' ||$_REQUEST[$getAction] == 'changeStatus')) {
        $actionData['ACTIVE'] = check_bitrix_sessid();
        $actionData['NAME'] = $_REQUEST[$getAction];
        unset($_REQUEST[$getAction], $_REQUEST[$getAction]);

        if (isset($_REQUEST['ID'])) {
            $actionData['ID'] = array($_REQUEST['ID']);
            unset($_REQUEST['ID'], $_REQUEST['ID']);
        }

    } else if ($actionData['METHOD'] == 'POST' && (isset($controls[$postAction]) || isset($_POST[$postAction]))) {
        CUtil::JSPostUnescape();

        $actionData['ACTIVE'] = true;

        if (isset($controls[$postAction])) {
            $actionData['NAME'] = $controls[$postAction];
        } else {
            $actionData['NAME'] = $_POST[$postAction];
            unset($_POST[$postAction], $_REQUEST[$postAction]);
        }

        $allRows = 'action_all_rows_' . $arResult['GRID_ID'];
        $actionData['ALL_ROWS'] = false;
        if (isset($controls[$allRows])) {
            $actionData['ALL_ROWS'] = $controls[$allRows] == 'Y';
        } elseif (isset($_POST[$allRows])) {
            $actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
            unset($_POST[$allRows], $_REQUEST[$allRows]);
        }

        if (isset($_POST['rows']) && is_array($_POST['rows'])) {
            $actionData['ID'] = $_POST['rows'];
        } elseif (isset($_POST['ID'])) {
            $actionData['ID'] = $_POST['ID'];
            unset($_POST['ID'], $_REQUEST['ID']);
        }

        if (isset($_POST['FIELDS'])) {
            $actionData['FIELDS'] = $_POST['FIELDS'];
            unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
        }

        $actionData['AJAX_CALL'] = $arResult['IS_AJAX_CALL'];
    } elseif ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction])) {
        $actionData['ACTIVE'] = check_bitrix_sessid();

        $actionData['NAME'] = $_GET[$getAction];
        unset($_GET[$getAction], $_REQUEST[$getAction]);

        if (isset($_GET['ID'])) {
            $actionData['ID'] = $_GET['ID'];
            unset($_GET['ID'], $_REQUEST['ID']);
        }

        $actionData['AJAX_CALL'] = $arResult['IS_AJAX_CALL'];
    }
}
//endregion

// HACK: for clear filter by CREATED_BY, MODIFIED_BY and ASSIGNED_BY_ID
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_REQUEST['CREATED_BY_name']) && $_REQUEST['CREATED_BY_name'] === '') {
        $_REQUEST['CREATED_BY'] = $_GET['CREATED_BY'] = array();
    }

    if (isset($_REQUEST['MODIFY_BY_name']) && $_REQUEST['MODIFY_BY_name'] === '') {
        $_REQUEST['MODIFY_BY'] = $_GET['MODIFY_BY'] = array();
    }
}

if (intval($arParams['ASSOCIATE_COUNT']) <= 0)
    $arParams['ASSOCIATE_COUNT'] = 20;

$arNavParams = array(
    'nPageSize' => $arParams['ASSOCIATE_COUNT']
);

$gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$arNavParams = $gridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

if (!$arResult['IS_EXTERNAL_FILTER']) {
    $arFilter += $filterOptions->getFilter($arResult['FILTER']);
}



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

foreach ($arFilter as $k => $v) {
    if (in_array($k, $arImmutableFilters, true)) {
        continue;
    }
    $arMatch = array();

    if (in_array($k, array('CANDIDATE_FULL_NAME'))) {
        $arFilter['__INNER_FILTER_ID_' . $k] = array(
            'LOGIC' => 'OR',
            '%CANDIDATE_FIRST_NAME' => $v,
            '%CANDIDATE_LAST_NAME' => $v,
        );
        unset($arFilter[$k]);
    } else if (in_array($k, array('PRODUCT_ID', 'TYPE_ID', 'STAGE_ID', 'COMPANY_ID', 'CONTACT_ID'))) {
        // Bugfix #23121 - to suppress comparison by LIKE
        $arFilter['=' . $k] = $v;
        unset($arFilter[$k]);
    } elseif ($k === 'ORIGINATOR_ID') {
        // HACK: build filter by internal entities
        $arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
        unset($arFilter[$k]);
    } elseif (preg_match('/(.*)_from$/i' . BX_UTF_PCRE_MODIFIER, $k, $arMatch)) {
        \Bitrix\Crm\UI\Filter\Range::prepareFrom($arFilter, $arMatch[1], $v);
    } elseif (preg_match('/(.*)_to$/i' . BX_UTF_PCRE_MODIFIER, $k, $arMatch)) {
        if ($v != '' && ($arMatch[1] == 'CREATED_DATE' || $arMatch[1] == 'MODIFIED_DATE') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/' . BX_UTF_PCRE_MODIFIER, $v)) {
            $v = CCrmDateTimeHelper::SetMaxDayTime($v);
        }
        \Bitrix\Crm\UI\Filter\Range::prepareTo($arFilter, $arMatch[1], $v);
    } elseif (in_array($k, $arResult['FILTER2LOGIC'])) {
        // Bugfix #26956 - skip empty values in logical filter
        $v = trim($v);
        if ($v !== '') {
            $arFilter['?' . $k] = $v;
        }
        unset($arFilter[$k]);
    } elseif ($k != 'ID' && $k != 'LOGIC' && $k != '__INNER_FILTER' && $k != '__JOINS' && $k != '__CONDITIONS' && strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1) {
        $arFilter['%' . $k] = $v;
        unset($arFilter[$k]);
    }
}

\Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

// POST & GET actions processing -->
if ($actionData['ACTIVE']) {
    if ($actionData['METHOD'] == 'POST') {
        if ($actionData['NAME'] == 'delete') {
            if ((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS']) {
                $arFilterDel = array();
                if (!$actionData['ALL_ROWS']) {
                    $arFilterDel = array('ID' => $actionData['ID']);
                } else {
                    // Fix for issue #26628
                    $arFilterDel += $arFilter;
                }

                $obRes = YNSIRAssociateJob::GetList(array(), $arFilterDel, false, false, array('ID'));
                while ($arAssociate = $obRes->Fetch()) {
                    $IDs[] = $arAssociate['ID'];
                }

//                    $entityAttrs = CCrmDeal::GetPermissionAttributes($IDs, $categoryID);
                foreach ($IDs as $ID) {
                    $DB->StartTransaction();

                    if (YNSIRAssociateJob::Delete($ID)) {
                        $DB->Commit();
                    } else {
                        $DB->Rollback();
                    }
                }
            }
        } elseif ($actionData['NAME'] == 'changeStatus') {
            if ((isset($_REQUEST['STATUS_ID']) && $_REQUEST['STATUS_ID'] != '')
                ||(isset($_REQUEST['STATUS_ROUND_ID']) && $_REQUEST['STATUS_ROUND_ID'] != ''))
                {
                $statusId = $_REQUEST['STATUS_ID'];
                $statusRoundId = $_REQUEST['STATUS_ROUND_ID'];
                $arFilterChangeStatus = array('ID' => $actionData['ID']);

                $obRes = YNSIRAssociateJob::GetList(array(), $arFilterChangeStatus, false, false, array('ID'));
                while ($arAssociate = $obRes->Fetch()) {
                    //CHECK STATUS IS LOCK AND ASSOCIATE IS LOCK BEFORE
                    if(in_array($statusId,$arResult['STATUS_LOCK'])) {
                        $checkLockAssociate = YNSIRAssociateJob::checkCandiateLock($arAssociate['CANDIDATE_ID']);
                        if ($checkLockAssociate['IS_LOCK'] == 'Y') {
                            break;
                        }
                        unset($checkLockAssociate);
                    }
                    //END
                    $IDs[] = $arAssociate['ID'];
                }

//                $arEntityAttr = $userPermissions->GetEntityAttr('ASSOCIATE', $arIDs);
                foreach ($IDs as $ID) {
                    $DB->StartTransaction();
                    $arUpdateData = array();
                    if(key_exists($statusId,$arResult['CONFIG']['CANDIDATE_STATUS_LIST']))
                        $arUpdateData = array('STATUS_ID' => $statusId);
                    if(intval($statusRoundId > 0))
                        $arUpdateData = array_merge($arUpdateData,
                            array('STATUS_ROUND_ID' => $statusRoundId)
                        );

                    if (YNSIRAssociateJob::Update($ID, $arUpdateData, true, true)) {
                        foreach(GetModuleEvents("ynsirecruitment", "OnUpdateStatusOssociate", true) as $arEvent)
                            ExecuteModuleEventEx($arEvent, array('ID' => $ID, 'updateDATA' => $arUpdateData, 'userId' => $userId));
                        $DB->Commit();
                    } else {
                        $DB->Rollback();
                    }
                }
            }
        }
        if (!$actionData['AJAX_CALL']) {
//            LocalRedirect($arParams['PATH_TO_ASSOCIATE_LIST']);
        }
    } else//if ($actionData['METHOD'] == 'GET')
    {
        if ($actionData['NAME'] == 'delete' && isset($actionData['ID'])) {
            $ID = (int)$actionData['ID'];
            $categoryID = CCrmDeal::GetCategoryID($ID);
            $entityAttrs = CCrmDeal::GetPermissionAttributes(array($ID), $categoryID);
            if (CCrmDeal::CheckDeletePermission($ID, $userPermissions, -1, array('ENTITY_ATTRS' => $entityAttrs))) {
                $DB->StartTransaction();

                if ($CCrmBizProc->Delete($ID, $entityAttrs, array('DealCategoryId' => $categoryID))
                    && $CCrmDeal->Delete($ID, array('PROCESS_BIZPROC' => false))
                ) {
                    $DB->Commit();
                } else {
                    $DB->Rollback();
                }
            }
        }

        if (!$actionData['AJAX_CALL']) {
            if ($bInternal) {
                LocalRedirect('?' . $arParams['FORM_ID'] . '_active_tab=tab_deal');
            } elseif ($arResult['CATEGORY_ID'] >= 0) {
                LocalRedirect(
                    CComponentEngine::makePathFromTemplate(
                        $arParams['PATH_TO_DEAL_CATEGORY'],
                        array('category_id' => $arResult['CATEGORY_ID'])
                    )
                );
            } else {
//                LocalRedirect($arParams['PATH_TO_ASSOCIATE_LIST']);
            }
        }
    }
}
// <-- POST & GET actions processing
$_arSort = $gridOptions->GetSorting(array(
    'sort' => array('CREATED_DATE' => 'desc'),
    'vars' => array('by' => 'by', 'order' => 'order')
));
$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

// Remove column for deleted UF
$arSelect = $gridOptions->GetVisibleColumns();

// Fill in default values if empty
if (empty($arSelect)) {
    foreach ($arResult['HEADERS'] as $arHeader) {
        if ($arHeader['default']) {
            $arSelect[] = $arHeader['id'];
        }
    }

    //Disable bizproc fields processing
    $arResult['ENABLE_BIZPROC'] = false;
} else {
    if ($arResult['ENABLE_BIZPROC']) {
        //Check if bizproc fields selected
        $hasBizprocFields = false;
        foreach ($arSelect as &$fieldName) {
            if (substr($fieldName, 0, 8) === 'BIZPROC_') {
                $hasBizprocFields = true;
                break;
            }
        }
        $arResult['ENABLE_BIZPROC'] = $hasBizprocFields;
    }
    unset($fieldName);
}

$arSelectedHeaders = $arSelect;

if (!in_array('TITLE', $arSelect, true)) {
    //Is required for activities management
    $arSelect[] = 'TITLE';
}

if (in_array('CREATED_BY', $arSelect, true)) {
    $arSelect[] = 'CREATED_BY_LOGIN';
    $arSelect[] = 'CREATED_BY_NAME';
    $arSelect[] = 'CREATED_BY_LAST_NAME';
    $arSelect[] = 'CREATED_BY_SECOND_NAME';
}

if (in_array('MODIFY_BY', $arSelect, true)) {
    $arSelect[] = 'MODIFY_BY_LOGIN';
    $arSelect[] = 'MODIFY_BY_NAME';
    $arSelect[] = 'MODIFY_BY_LAST_NAME';
    $arSelect[] = 'MODIFY_BY_SECOND_NAME';
}


// ID must present in select
if (!in_array('ID', $arSelect)) {
    $arSelect[] = 'ID';
}

$nTopCount = false;
if ($isInGadgetMode) {
    $arSelect = array(
        'CREATED_DATE', 'TITLE', 'STAGE_ID', 'TYPE_ID',
        'OPPORTUNITY', 'CURRENCY_ID', 'COMMENTS',
        'CONTACT_ID', 'CONTACT_HONORIFIC', 'CONTACT_NAME', 'CONTACT_SECOND_NAME',
        'CONTACT_LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE'
    );
    $nTopCount = $arParams['DEAL_COUNT'];
}

if ($nTopCount > 0) {
    $arNavParams['nTopCount'] = $nTopCount;
}

if ($isInExportMode)
    $arFilter['PERMISSION'] = 'EXPORT';

// HACK: Make custom sort for ASSIGNED_BY field
$arSort = $arResult['SORT'];

$arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
if (in_array('ACTIVITY_ID', $arSelect, true)) {
    $arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
    $arExportOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
}

if (isset($arSort['deal_client'])) {
    $arSort['contact_last_name'] = $arSort['deal_client'];
    $arSort['contact_name'] = $arSort['deal_client'];
    $arSort['company_title'] = $arSort['deal_client'];
    unset($arSort['deal_client']);
}

if (isset($arParams['IS_EXTERNAL_CONTEXT'])) {
    $arOptions['IS_EXTERNAL_CONTEXT'] = $arParams['IS_EXTERNAL_CONTEXT'];
}

//FIELD_OPTIONS
$arSelect = array_unique($arSelect, SORT_STRING);

$arResult['ASSOCIATE'] = array();
$arResult['DEAL_ID'] = array();

//region Navigation data initialization
$pageNum = 0;
$pageSize = !$isInExportMode
    ? (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['DEAL_COUNT']) : 0;
$enableNextPage = false;
if (isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y') {
    $pageNum = 1;
} elseif ($pageSize > 0 && isset($_REQUEST['page'])) {
    $pageNum = (int)$_REQUEST['page'];
    if ($pageNum < 0) {
        //Backward mode
        $offset = -($pageNum + 1);
        $total = YNSIRAssociateJob::GetList(array(), $arFilter, array());
        $pageNum = (int)(ceil($total / $pageSize)) - $offset;
        if ($pageNum <= 0) {
            $pageNum = 1;
        }
    }
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
$navListOptions = $isInExportMode
    ? $arExportOptions
    : array_merge(
        $arOptions,
        array('QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)))
    );

//Permissions are already checked.
$dbResult = YNSIRAssociateJob::GetList(
    $arSort,
    $arFilter,
    false,
    false,
    $arSelect,
    $navListOptions
);
$qty = 0;
$arResult['JOB_ORDER_ASSOCIATE'] = array();
while($arAssociate = $dbResult->GetNext())
{
    if($pageSize > 0 && ++$qty > $pageSize)
    {
        $enableNextPage = true;
        break;
    }
    if(!in_array($arAssociate['ORDER_JOB_ID'],$arResult['JOB_ORDER_ASSOCIATE'])){
        $arResult['JOB_ORDER_ASSOCIATE'][] = $arAssociate['ORDER_JOB_ID'];
    }
    $arResult['ASSOCIATE'][$arAssociate['ID']] = $arAssociate;
}
/*
*ROUND GET
*/
$arResult['CONFIG']['INTERVIEW'] = YNSIRInterview::getListDetail(array(), array('JOB_ORDER' => $arResult['JOB_ORDER_ASSOCIATE']), false, false, array());
//$arResult['JOB_ORDER']['INTERVIEW'] = $arResult['CONFIG']['INTERVIEW'][$arResult['ID']];


//region Navigation data storing
$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
$arResult['DB_FILTER'] = $arFilter;
if(!isset($_SESSION['YNSIR_GRID_DATA'])) {
    $_SESSION['YNSIR_GRID_DATA'] = array();
}
$_SESSION['YNSIR_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
//endregionY

$arResult['PAGINATION']['URL'] = $APPLICATION->GetCurPageParam('', array('apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal'));
foreach ($arResult['ASSOCIATE'] as &$arAssociate) {
    $entityID = $arAssociate['ID'];

    if (!empty($arAssociate['CREATED_BY']))
        $arAssociate['~CREATED_BY_FULL_NAME'] = CUser::FormatName(
            $arParams["NAME_TEMPLATE"],
            array(
                'LOGIN' => $arAssociate['CREATED_BY_LOGIN'],
                'NAME' => $arAssociate['CREATED_BY_NAME'],
                'LAST_NAME' => $arAssociate['CREATED_BY_LAST_NAME'],
                'SECOND_NAME' => $arAssociate['CREATED_BY_SECOND_NAME']
            ),
            true, false
        );
    $arAssociate['CREATED_BY_FULL_NAME'] = htmlspecialcharsbx($arAssociate['~CREATED_BY_FULL_NAME']);
    $arAssociate['CREATED_BY_LINK'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'], array('user_id' => $arAssociate['CREATED_BY']));
    $arAssociate['CANDIDATE_BY_LINK'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CANDIDATE_DETAIL'], array('candidate_id' => $arAssociate['CANDIDATE_ID']));
    $arAssociate['JOB_ORDER_BY_LINK'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_JOB_ORDER_DETAIL'], array('order_id' => $arAssociate['ORDER_JOB_ID']));
//    $arAssociate['EVENT_NAME'] = htmlspecialcharsbx($arAssociate['~EVENT_NAME']);

    $arAssociate['STATUS'] = $arResult['CONFIG']['CANDIDATE_STATUS_LIST'][$arAssociate['STATUS_ID']];
    if (intval($arAssociate['STATUS_ROUND_ID']) > 0) {
        $arAssociate['STATUS_ROUND'] = GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $arResult['CONFIG']['INTERVIEW'][$arAssociate['ORDER_JOB_ID']][$arAssociate['STATUS_ROUND_ID']]['ROUND_INDEX']));
    } else {
        $arAssociate['STATUS_ROUND'] = '';
    }

    $arAssociate['CREATED_BY_PHOTO_URL'] = '';
    $createdByPhotoID = isset($arAssociate['CREATED_BY_PERSONAL_PHOTO']) ? (int)$arAssociate['CREATED_BY_PERSONAL_PHOTO'] : 0;
    if ($createdByPhotoID > 0) {
        $file = new CFile();
        $fileInfo = $file->ResizeImageGet(
            $createdByPhotoID,
            array('width' => 38, 'height' => 38),
            BX_RESIZE_IMAGE_EXACT
        );
        if (is_array($fileInfo) && isset($fileInfo['src'])) {
            $arAssociate['CREATED_BY_PHOTO_URL'] = $fileInfo['src'];
        }
    }
    $arAssociate['CANDIDATE_FULL_NAME'] = CUser::FormatName(
        $arParams["NAME_TEMPLATE"],
        array(
            "NAME" => $arAssociate['CANDIDATE_FIRST_NAME'],
            "LAST_NAME" => $arAssociate['CANDIDATE_LAST_NAME'],
        )
    );

    if (!empty($arAssociate['MODIFIED_BY']))
        $arAssociate['~MODIFIED_BY_FULL_NAME'] = CUser::FormatName(
            $arParams["NAME_TEMPLATE"],
            array(
                'LOGIN' => $arAssociate['MODIFIED_BY_LOGIN'],
                'NAME' => $arAssociate['MODIFIED_BY_NAME'],
                'LAST_NAME' => $arAssociate['MODIFIED_BY_LAST_NAME'],
                'SECOND_NAME' => $arAssociate['MODIFIED_BY_SECOND_NAME']
            ),
            true, false
        );
    $arAssociate['MODIFIED_BY_FULL_NAME'] = htmlspecialcharsbx($arAssociate['~MODIFIED_BY_FULL_NAME']);
    $arAssociate['MODIFIED_BY_LINK'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'], array('user_id' => $arAssociate['MODIFIED_BY']));
    $arAssociate['EVENT_NAME'] = htmlspecialcharsbx($arAssociate['~EVENT_NAME']);

    $arAssociate['MODIFIED_BY_PHOTO_URL'] = '';
    $arAssociate['PATH_TO_ASSOCIATE_DELETE'] = CHTTP::urlAddParams(
        $bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_ASSOCIATE_LIST'],
        array('action_' . $arResult['GRID_ID'] => 'delete', 'ID' => $entityID, 'sessid' => $arResult['SESSION_ID'])
    );
    $arAssociate['PATH_TO_ASSOCIATE_DELETE'] = CHTTP::urlAddParams(
        $bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_ASSOCIATE_LIST'],
        array(
            'action_' . $arResult['GRID_ID'] => 'delete',
            'ID' => $arAssociate['ID'],
            'sessid' => $arResult['SESSION_ID']
        )
    );

    $createdByPhotoID = isset($arAssociate['MODIFIED_BY_PERSONAL_PHOTO']) ? (int)$arAssociate['MODIFIED_BY_PERSONAL_PHOTO'] : 0;
    if ($createdByPhotoID > 0) {
        $file = new CFile();
        $fileInfo = $file->ResizeImageGet(
            $createdByPhotoID,
            array('width' => 38, 'height' => 38),
            BX_RESIZE_IMAGE_EXACT
        );
        if (is_array($fileInfo) && isset($fileInfo['src'])) {
            $arAssociate['MODIFIED_BY_PHOTO_URL'] = $fileInfo['src'];
        }
    }


    $arResult['ASSOCIATE'][$entityID] = $arAssociate;
}
unset($arAssociate);

$arResult['ENABLE_TOOLBAR'] = isset($arParams['ENABLE_TOOLBAR']) ? $arParams['ENABLE_TOOLBAR'] : false;

$arResult['NEED_FOR_REBUILD_DEAL_ATTRS'] =
$arResult['NEED_FOR_REBUILD_DEAL_SEMANTICS'] =
$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = false;

if (!$bInternal) {
    if (COption::GetOptionString('ynsirecruitment', '~CRM_REBUILD_DEAL_SEARCH_CONTENT', 'N') === 'Y') {
        $arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = true;
    }

    if (YNSIRPerms_::IsAdmin()) {
        if (COption::GetOptionString('ynsirecruitment', '~CRM_REBUILD_DEAL_ATTR', 'N') === 'Y') {
            $arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('ynsirecruitment', 'path_to_perm_list'));
            $arResult['NEED_FOR_REBUILD_DEAL_ATTRS'] = true;
        }
        if (COption::GetOptionString('ynsirecruitment', '~CRM_REBUILD_DEAL_SEMANTICS', 'N') === 'Y') {
            $arResult['NEED_FOR_REBUILD_DEAL_SEMANTICS'] = true;
        }
    }
}

$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix/crm.deal/include/nav.php');
return $arResult['ROWS_COUNT'];
?>
