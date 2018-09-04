<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$APPLICATION->SetTitle(GetMessage("YNSIR_JOL_T_JOB_ORDER_TITLE"));
// =================== general import ==========================================
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');
if (SITE_TEMPLATE_ID === 'bitrix24') {
    $APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable()) {
    CBitrix24::initLicenseInfoPopupJS();
}
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/ynsirecruitment/css/autorun_proc.css');


$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
// =================== button add new ===========================================
if ($arResult['PERMS']['ADD']) {
    $APPLICATION->IncludeComponent(
        'bitrix:crm.interface.toolbar',
        'title',
        array(
            'TOOLBAR_ID' => strtolower($arResult['GRID_ID']) . '_toolbar',
            'BUTTONS' => array(
                array(
                    'TEXT' => GetMessage('YNSIR_JOL_T_ADD_JOB_ORDER'),
                    'TITLE' => GetMessage('YNSIR_JOL_T_ADD_JOB_ORDER'),
                    'LINK' => "/recruitment/job-order/edit/0/",
                    'ID' => 'bx-sharepoint-sync'
                ),
            )
        ),
        $component,
        array('HIDE_ICONS' => 'Y')
    );
}
// =================== list grid ================================================
$arResult['GRID_DATA'] = array();

$userPermissions = $arResult['USER_PERMISSIONS'];

foreach ($arResult['LIST_JOB_ORDER'] as $iKeyJO => $arItemJO){
    // permission here
    $bDetail = YNSIRJobOrder::CheckReadPermission($arItemJO['ID'], $userPermissions);
    $bEdit = YNSIRJobOrder::CheckUpdatePermission($arItemJO['ID'], $userPermissions);

    // convert data to text
    $arJOTempData = array();
    foreach ($arItemJO as $sKeyField => $itemValue){
        $bFieldPermission = true;
        if($bFieldPermission == true){
            switch ($sKeyField){
                // unique user
                case 'CREATED_BY':
                case 'MODIFIED_BY':
                case 'SUPERVISOR':
                case 'OWNER':
                case 'RECRUITER':
                    $arJOTempData[$sKeyField] = YNSIRGeneral::tooltipUser(
                        $arResult['DATA_USER'][$itemValue], 0,
                        'user_tooltip_' . $iKeyJO . '_' . $sKeyField . '_' . $arResult['DATA_USER'][$itemValue]['ID']
                    );
                    break;
                // mutiple user
                case 'SUBORDINATE':
                    $sSuborTooltip = '';
                    $iBCount = 0;
                    foreach ($arResult['SUBORDINATE'][$iKeyJO] as $itemSubordinate) {
                        $sSuborTooltip .= '<div>' . YNSIRGeneral::tooltipUser(
                                $arResult['DATA_USER'][$itemSubordinate], 0,
                                'user_tooltip_' . $iKeyJO . '_' . $sKeyField . '_' . $arResult['DATA_USER'][$itemSubordinate]['ID'] . '_' . $iBCount
                            ) . '</div>';
                        $iBCount++;
                    }
                    $arJOTempData[$sKeyField] = $sSuborTooltip;
                    break;
                // department
                case 'DEPARTMENT':
                    $arJOTempData[$sKeyField] = '<a href="/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT='.$itemValue.'"'
                        . ' title="'.$arResult['DEPARTMENT'][$itemValue].'">'
                        .$arResult['DEPARTMENT'][$itemValue] . '</a>';
                    break;
                // new staff
                case 'IS_REPLACE':
                    $arJOTempData[$sKeyField] = intval($itemValue) == 1 ? GetMessage("YNSIR_CJOL_FIELD_TYPE_REPLACE_TITLE") : GetMessage("YNSIR_CJOL_FIELD_TYPE_NEW_TITLE");
                    break;
                // status
                case 'STATUS':
                    $arJOTempData[$sKeyField] = '';
                    if(isset($arResult['JO_STATUS'][$itemValue])){
                        $arJOTempData[$sKeyField] = $arResult['JO_STATUS'][$itemValue];
                    }
                    break;
                // template
                case 'TEMPLATE_ID':
                    $arJOTempData[$sKeyField] = '';
                    if(isset($arResult['JO_TEMPLATE'][$itemValue])){
                        $arJOTempData[$sKeyField] = htmlspecialchars($arResult['JO_TEMPLATE'][$itemValue], ENT_QUOTES);
                    }
                    break;
                // template
                case 'DESCRIPTION':
                    $arJOTempData[$sKeyField] = $itemValue;
                    if(strlen($itemValue) <= 0 && isset($arResult['JO_TEMPLATE'][$itemValue])){
                        $arJOTempData[$sKeyField] = $arResult['JO_TEMPLATE'][$arItemJO['TEMPLATE_ID']];
                    }
                // text
                default:
                    $arJOTempData[$sKeyField] = $itemValue;
                    break;
            }
        }
        else {
            $arJOTempData[$sKeyField] = '';
        }
    }
    // action on row data
    $arActions = array();
    if($bDetail == true){
        $arActions[] = array(
            'TITLE' => GetMessage('YNSIR_JOB_T_ORDER_QUICK_VIEW'),
            'TEXT' => GetMessage('YNSIR_JOB_T_ORDER_QUICK_VIEW'),
            'ONCLICK' => "quikview(" . $iKeyJO . ")",
            'DEFAULT' => true
        );

        $arActions[] = array(
            'TITLE' => GetMessage('YNSIR_JOB_T_ORDER_DETAIL'),
            'TEXT' => GetMessage('YNSIR_JOB_T_ORDER_DETAIL'),
            'ONCLICK' => "jsUtils.Redirect([], '" . CUtil::JSEscape($arItemJO['PATH_TO_JO_DETAIL']) . "');",
            'DEFAULT' => false
        );
        $clickAction = "jsUtils.Redirect([], '" . CUtil::JSEscape($arItemJO['PATH_TO_JO_DETAIL']) . "');";
        $arJOTempData['TITLE'] = '<a href="javascript:void(0)" onclick="'.$clickAction.'">'.$arItemJO['TITLE'].'</a>';
    }
    if($bEdit == true){
        $arActions[] = array(
            'TITLE' => GetMessage('YNSIR_JOB_T_ORDER_EDIT'),
            'TEXT' => GetMessage('YNSIR_JOB_T_ORDER_EDIT'),
            'ONCLICK' => "jsUtils.Redirect([], '" . CUtil::JSEscape($arItemJO['PATH_TO_JO_EDIT']) . "');",
            'DEFAULT' => false
        );
    }
    // item grid data
    $arResult['GRID_DATA'][] = array(
        'id' => $arItemJO['ID'],
        'actions' => $arActions,
        'data' => $arJOTempData,
        'editable' => 'N',
        'DEFAULT' => true
    );
}

//region Action Panel
$allowDelete = $arResult['PERMS']['DELETE'];
$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));
if ($allowDelete) {
    $Removebtn = $snippet->getRemoveButton();
    $Removebtn['ONCHANGE'][0]['CONFIRM_MESSAGE'] = GetMessage('YNSIR_JOB_T_DELETE_CONFIRM');
    $controlPanel['GROUPS'][0]['ITEMS'][] = $Removebtn;
    $actionList[] = $snippet->getRemoveAction();
}
//$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();

$activityEditorID = '';
$gridManagerID = $arResult['GRID_ID'] . '_MANAGER';
$APPLICATION->IncludeComponent(
    'ynsirecruitment:ynsir.interface.grid',
    'titleflex',
    array(
        'GRID_ID' => $arResult['GRID_ID'],
        'HEADERS' => $arResult['HEADERS'],
        'SORT' => $arResult['SORT'],
        'SORT_VARS' => $arResult['SORT_VARS'],
        'ROWS' => $arResult['GRID_DATA'],
        'FORM_ID' => $arResult['FORM_ID'],
        'TAB_ID' => $arResult['TAB_ID'],
        'AJAX_ID' => $arResult['AJAX_ID'],
        'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
        'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
        'FILTER' => $arResult['FILTER'],
        'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
        'ENABLE_LIVE_SEARCH' => true,
        'ACTION_PANEL' => $controlPanel,
        'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
            ? $arResult['PAGINATION'] : array(),
        'ENABLE_ROW_COUNT_LOADER' => true,
        'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
        'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
        'SHOW_CHECK_ALL_CHECKBOXES' => false, // checkbox : check all
        'SHOW_ROW_CHECKBOXES' => true, // checkbox : check for all record in database
        'EXTENSION' => array(
            'ID' => $gridManagerID,
            'CONFIG' => array(
                'ownerTypeName' => YNSIR_OWNER_TYPE_ORDER,
                'gridId' => $arResult['GRID_ID'],
                'activityEditorId' => $activityEditorID,
                'taskCreateUrl' => isset($arResult['TASK_CREATE_URL']) ? $arResult['TASK_CREATE_URL'] : '',
                'serviceUrl' => '/bitrix/components/ynsirecruitment/job_order.list/list.ajax.php?siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
                'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
            ),
            'MESSAGES' => array(
                'deletionDialogTitle' => GetMessage('YNSIR_JOB_T_DELETE_TITLE') ,
                'deletionDialogMessage' => GetMessage('YNSIR_JOB_T_DELETE_CONFIRM') ,
                'deletionDialogButtonTitle' => GetMessage('YNSIR_JOB_T_ORDER_DELETE')
            )
        )
    )
);
?>
<style>
    .crm-lead-header-inner-cell {
        color: red !important;
    }
</style>
<a id="ynsir_show_job_order" href="/recruitment/job-order/detail/1/" hidden></a>