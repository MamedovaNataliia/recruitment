<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$APPLICATION->SetTitle(GetMessage("YNSIR_E_EMAIL_TEMPLATE_TITLE"));
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
                    'TEXT' =>  GetMessage('YNSIR_JOL_T_ADD_JOB_ORDER'),
                    'TITLE' => GetMessage('YNSIR_JOL_T_ADD_JOB_ORDER'),
                    'LINK' => "/recruitment/config/mailtemplate/edit/0/",
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

foreach ($arResult['EMAIL_TEMMPLATE'] as $ikey => $arItem) {
    // permission here

    // convert data to text
    $arEmailData = array();
    foreach ($arItem as $sKeyField => $itemValue) {
        switch ($sKeyField) {
            // unique user
            case 'CREATED_BY':
            case 'MODIFIED_BY':
                $arEmailData[$sKeyField] = YNSIRGeneral::tooltipUser(
                    $arResult['DATA_USER'][$itemValue], 0,
                    'user_tooltip_' . $ikey . '_' . $sKeyField . '_' . $arResult['DATA_USER'][$itemValue]['ID']
                );
                break;
            default:
                $arEmailData[$sKeyField] = $itemValue;
                break;
        }

    }
    // action on row data
    $arActions = array();
    $bDetail = $bEdit = true;
    if ($bEdit == true) {
        $arActions[] = array(
            'TITLE' => GetMessage('YNSIR_MAIL_TEMPLATE_EDIT'),
            'TEXT' => GetMessage('YNSIR_MAIL_TEMPLATE_EDIT'),
            'ONCLICK' => "jsUtils.Redirect([], '" . CUtil::JSEscape($arItem['PATH_TO_JO_EDIT']) . "');",
            'DEFAULT' => true
        );
    }
    // item grid data
    $arResult['GRID_DATA'][] = array(
        'id' => $arItem['ID'],
        'actions' => $arActions,
        'data' => $arEmailData,
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
                'gridId' => $arResult['GRID_ID'],
                'serviceUrl' => '/bitrix/components/ynsirecruitment/mail_template_list/list.ajax.php?siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
                'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
            ),
            'MESSAGES' => array(
                'deletionDialogTitle' => GetMessage('YNSIR_JOB_T_DELETE_TITLE'),
                'deletionDialogMessage' => GetMessage('YNSIR_JOB_T_DELETE_CONFIRM'),
                'deletionDialogButtonTitle' => GetMessage('YNSIR_MAIL_TEMPLATE_DELETE')
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
