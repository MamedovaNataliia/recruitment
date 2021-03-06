<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

CUtil::InitJSCore(array('window'));

if(SITE_TEMPLATE_ID === 'bitrix24')
{
    $APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/bitrix24/crm-entity-show.css');
    $bodyClass = $APPLICATION->GetPageProperty('BodyClass');
    $APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-paddings pagetitle-toolbar-field-view flexible-layout crm-pagetitle-view crm-toolbar');
}

$asset = Bitrix\Main\Page\Asset::getInstance();
$asset->addJs('/bitrix/js/main/popup_menu.js');
$asset->addJs('/bitrix/js/crm/common.js');

$gridID = isset($arParams['~GRID_ID']) ? $arParams['~GRID_ID'] : '';
$prefix = $gridID;
$prefixLC = strtolower($gridID);

$nameTemplate = isset($arParams['~NAME_TEMPLATE']) ? $arParams['~NAME_TEMPLATE'] : '';
$extension = isset($arParams['~EXTENSION']) && is_array($arParams['~EXTENSION'])
    ? $arParams['~EXTENSION'] : array();
$pagination = isset($arParams['~PAGINATION']) && is_array($arParams['~PAGINATION'])
    ? $arParams['~PAGINATION'] : array();
$actionPanel = isset($arParams['~ACTION_PANEL']) && is_array($arParams['~ACTION_PANEL'])
    ? $arParams['~ACTION_PANEL'] : array('GROUPS' => array(array('ITEMS' => array())));

//region Filter
//Skip reneding of grid filter for internal grid request (filter already created)
if(!Bitrix\Main\Grid\Context::isInternalRequest() && isset($arParams['~FILTER']) && isset($arParams['~FILTER_PRESETS']))
{
    $APPLICATION->IncludeComponent(
        'bitrix:crm.interface.filter',
        isset($arParams['~FILTER_TEMPLATE']) ? $arParams['~FILTER_TEMPLATE'] : 'title',
        array(
            'GRID_ID' => $gridID,
            'FILTER_ID' => $gridID,
            'FILTER' => $arParams['~FILTER'],
            'FILTER_PRESETS' => $arParams['~FILTER_PRESETS'],
            'RENDER_INTO_VIEW' => isset($arParams['~RENDER_FILTER_INTO_VIEW']) ? $arParams['~RENDER_FILTER_INTO_VIEW'] : '',
            'NAVIGATION_BAR' => isset($arParams['~NAVIGATION_BAR']) ? $arParams['~NAVIGATION_BAR'] : null,
            'ENABLE_LIVE_SEARCH' => isset($arParams['~ENABLE_LIVE_SEARCH']) && $arParams['~ENABLE_LIVE_SEARCH'] === true,
            'DISABLE_SEARCH' => isset($arParams['~DISABLE_SEARCH']) && $arParams['~DISABLE_SEARCH'] === true,
        ),
        $component,
        array('HIDE_ICONS' => 'Y')
    );
}

//endregion

//region Navigation
$navigationHtml = '';
$navigationObject = null;
if(isset($arParams['~PAGINATION']) && is_array($arParams['~PAGINATION']))
{
    ob_start();
    $APPLICATION->IncludeComponent(
        'bitrix:crm.pagenavigation',
        'ynsir_pagenavigation',
        $pagination,
        $component,
        array('HIDE_ICONS' => 'Y')
    );
    $navigationHtml = ob_get_contents();
    ob_end_clean();
}
elseif(isset($arParams['~NAV_OBJECT']) && is_object($arParams['~NAV_OBJECT']))
{
    $navigationObject = $arParams['~NAV_OBJECT'];
}
//endregion

//region Row Count
$rowCountHtml = '';
if(isset($arParams['~ENABLE_ROW_COUNT_LOADER']) && $arParams['~ENABLE_ROW_COUNT_LOADER'] === true)
{
    $rowCountHtml = str_replace(
        array('%prefix%', '%all%', '%show%'),
        array(CUtil::JSEscape(strtolower($gridID)), GetMessage('YNSIR_ALL'), GetMessage('YNSIR_SHOW_ROW_COUNT')),
        '<div id="%prefix%_row_count_wrapper">%all%: <a id="%prefix%_row_count" href="#">%show%</a></div>'
    );
}

//edit by Nhatth2
$arPageSizetmp = array(
    array("NAME" => "5", "VALUE" => "5"),
    array("NAME" => "10", "VALUE" => "10"),
    array("NAME" => "20", "VALUE" => "20"),
    array("NAME" => "50", "VALUE" => "50"),
    array("NAME" => "100", "VALUE" => "100"),
    //Temporary limited by 100
    //array("NAME" => "200", "VALUE" => "200"),
);
$pageSize = isset($arParams['PAGE_SIZES']) && !empty($arParams['PAGE_SIZES']) ? $arParams['PAGE_SIZES']:$arPageSizetmp;
//end edit by Nhatth2
//endregion
//region Grid
$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    array(
        'GRID_ID' => $gridID,
        'HEADERS' => isset($arParams['~HEADERS']) ? $arParams['~HEADERS'] : array(),
        'SORT' => isset($arParams['~SORT']) ? $arParams['~SORT'] : array(),
        'SORT_VARS' => isset($arParams['~SORT_VARS']) ? $arParams['~SORT_VARS'] : array(),
        'ROWS' => isset($arParams['~ROWS']) ? $arParams['~ROWS'] : array(),
        'AJAX_MODE' => 'Y', //Strongly required
        'FORM_ID' => isset($arParams['~FORM_ID']) ? $arParams['~FORM_ID'] : '',
        'TAB_ID' => isset($arParams['~TAB_ID']) ? $arParams['~TAB_ID'] : '',
        'AJAX_ID' => isset($arParams['~AJAX_ID']) ? $arParams['~AJAX_ID'] : '',
        'AJAX_OPTION_JUMP' => $arParams['~AJAX_OPTION_JUMP'],
        'AJAX_OPTION_HISTORY' => $arParams['~AJAX_OPTION_HISTORY'],
        "PRESERVE_HISTORY" => isset($arParams['~PRESERVE_HISTORY']) ? $arParams['~PRESERVE_HISTORY'] : false,
        'NAV_OBJECT' => $navigationObject,
        "NAV_STRING" => $navigationHtml,
        "NAV_PARAM_NAME" => 'page',
        "CURRENT_PAGE" => isset($pagination['PAGE_NUM']) ? (int)$pagination['PAGE_NUM'] : 1,
        "ENABLE_NEXT_PAGE" => isset($pagination['ENABLE_NEXT_PAGE']) ? (bool)$pagination['ENABLE_NEXT_PAGE'] : false,
        "PAGE_SIZES" => $pageSize,
        "ALLOW_COLUMNS_SORT" => true,
        "ALLOW_ROWS_SORT" => false,
        "ALLOW_COLUMNS_RESIZE" => true,
        "ALLOW_HORIZONTAL_SCROLL" => true,
        "ALLOW_SORT" => true,
        "ALLOW_PIN_HEADER" => true,
        "ACTION_PANEL" => $actionPanel,
        "SHOW_CHECK_ALL_CHECKBOXES" => isset($arParams['SHOW_CHECK_ALL_CHECKBOXES'])? $arParams['SHOW_CHECK_ALL_CHECKBOXES'] : true,
        "SHOW_ROW_CHECKBOXES" => isset($arParams['SHOW_ROW_CHECKBOXES'])? $arParams['SHOW_ROW_CHECKBOXES'] : true,
        "SHOW_ROW_ACTIONS_MENU" => true,
        "SHOW_GRID_SETTINGS_MENU" => true,
        "SHOW_MORE_BUTTON" => isset($arParams['SHOW_MORE_BUTTON'])?$arParams['SHOW_MORE_BUTTON']:true,
        "SHOW_NAVIGATION_PANEL" => true,
        "SHOW_PAGINATION" => true,
        "SHOW_SELECTED_COUNTER" => true,
        "SHOW_TOTAL_COUNTER" => true,
        "SHOW_PAGESIZE" => isset($arParams['SHOW_PAGESIZE'])? $arParams['SHOW_PAGESIZE']:true,
        "SHOW_ACTION_PANEL" => true,
        "TOTAL_ROWS_COUNT_HTML" => $rowCountHtml
    ),
    $component,
    array('HIDE_ICONS' => 'Y')
);
//endregion

$extensionConfig = isset($extension['CONFIG']) ? $extension['CONFIG'] : null;
if(is_array($extensionConfig))
{
    $extensionID = isset($extension['ID']) ? $extension['ID'] : $gridID;
    $extensionMessages = isset($extension['MESSAGES']) && is_array($extension['MESSAGES']) ? $extension['MESSAGES'] : array();
    ?>
    <script type="text/javascript">
        BX.ready(
            function()
            {
                
                BX.YNSIRUIGridExtension.messages = <?=CUtil::PhpToJSObject($extensionMessages)?>;
                BX.YNSIRUIGridExtension.create(
                    "<?=CUtil::JSEscape($extensionID)?>",
                    <?=CUtil::PhpToJSObject($extensionConfig)?>
                );
            }
        );
    </script><?
}
?>
