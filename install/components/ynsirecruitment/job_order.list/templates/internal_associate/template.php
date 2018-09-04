<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');
if (SITE_TEMPLATE_ID === 'bitrix24') {
    $APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable()) {
    CBitrix24::initLicenseInfoPopupJS();
}

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/ynsirecruitment/css/autorun_proc.css');


$isInternal = $arParams['IS_INTERNAL_JOB_LIST'] == 'Y';
$currentUserID = $arResult['CURRENT_USER_ID'];
$activityEditorID = '';
$gridManagerID = $arResult['GRID_ID'] . '_MANAGER';
$gridManagerCfg = array(
    'ownerType' => YNSIR_OWNER_TYPE_CANDIDATE,
    'gridId' => $arResult['GRID_ID'],
    'formName' => "form_{$arResult['GRID_ID']}",
    'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
    'activityEditorId' => $activityEditorID,
    'serviceUrl' => '/bitrix/components/bitrix/cxxrm.activity.editor/ajax.php?siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
    'filterFields' => array()
);
$prefix = $arResult['GRID_ID'];
$prefixLC = strtolower($arResult['GRID_ID']);

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
    $arColumns[$arHead['id']] = false;

$userPermissions = YNSIRPerms_::GetCurrentUserPermissions();

foreach ($arResult['LIST_JOB_ORDER'] as $sKey => $arOrder) {
    $arEntitySubMenuItems = array();
    $arActivitySubMenuItems = array();
    $arActions = array();
    // convert data to text
    foreach ($arOrder as $sKeyField => $itemValue){
        $bFieldPermission = true;
        if($bFieldPermission == true){
            switch ($sKeyField){
                // unique user
                case 'CREATED_BY':
                case 'MODIFIED_BY':
                case 'SUPERVISOR':
                case 'OWNER':
                case 'RECRUITER':
                    $arOrder[$sKeyField] = YNSIRGeneral::tooltipUser(
                        $arResult['DATA_USER'][$itemValue], 0,
                        'user_tooltip_' . $sKey . '_' . $sKeyField . '_' . $arResult['DATA_USER'][$itemValue]['ID']
                    );
                    break;
                // mutiple user
                case 'SUBORDINATE':
                    $sSuborTooltip = '';
                    $iBCount = 0;
                    foreach ($arResult['SUBORDINATE'][$sKey] as $itemSubordinate) {
                        $sSuborTooltip .= '<div>' . YNSIRGeneral::tooltipUser(
                                $arResult['DATA_USER'][$itemSubordinate], 0,
                                'user_tooltip_' . $sKey . '_' . $sKeyField . '_' . $arResult['DATA_USER'][$itemSubordinate]['ID'] . '_' . $iBCount
                            ) . '</div>';
                        $iBCount++;
                    }
                    $arOrder[$sKeyField] = $sSuborTooltip;
                    break;
                // department
                case 'DEPARTMENT':
                    $arOrder[$sKeyField] = '<a href="/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT='.$itemValue.'"'
                        . ' title="'.$arResult['DEPARTMENT'][$itemValue].'">'
                        .$arResult['DEPARTMENT'][$itemValue] . '</a>';
                    break;
                // new staff
                case 'IS_REPLACE':
                    $arOrder[$sKeyField] = intval($itemValue) == 1 ? GetMessage("YNSIR_CJOL_FIELD_TYPE_REPLACE_TITLE") : GetMessage("YNSIR_CJOL_FIELD_TYPE_NEW_TITLE");
                    break;
                // status
                case 'STATUS':
                    $arOrder[$sKeyField] = '';
                    if(isset($arResult['JO_STATUS'][$itemValue])){
                        $arOrder[$sKeyField] = $arResult['JO_STATUS'][$itemValue];
                    }
                    break;
                // template
                case 'TEMPLATE_ID':
                    $arOrder[$sKeyField] = '';
                    if(isset($arResult['JO_TEMPLATE'][$itemValue])){
                        $arOrder[$sKeyField] = htmlspecialchars($arResult['JO_TEMPLATE'][$itemValue], ENT_QUOTES);
                    }
                    break;
                // template
                case 'DESCRIPTION':
                    $arOrder[$sKeyField] = $itemValue;
                    if(strlen($itemValue) <= 0 && isset($arResult['JO_TEMPLATE'][$itemValue])){
                        $arOrder[$sKeyField] = $arResult['JO_TEMPLATE'][$arItemJO['TEMPLATE_ID']];
                    }
                // text
                default:
                    $arOrder[$sKeyField] = $itemValue;
                    break;
            }
        }
        else {
            $arOrder[$sKeyField] = '';
        }
    }
    // action on row data
    $arOrder['TITLE'] = '<a href="'.$arOrder['PATH_TO_JO_DETAIL'].'">'.$arOrder['TITLE'].'</a>';

//TODO: COMMON ACTION

    if ($isInternal) {

        //ASSOCIATE JOB ORDER
        $arActions[] =  array(
            'TITLE' => GetMessage('YNSIR_ASSOCIATE_ORDER'),
            'TEXT' => GetMessage('YNSIR_ASSOCIATE_ORDER'),
            'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
					'{$gridManagerID}', 
					BX.YNSIRUIGridMenuCommand.associate, 
					{ list_associate_id: '{$arOrder['ID']}',
					  current_id: '{$arParams['ENTITY_ID']}'}
				)"
        );
    }
    //Check status ID
    $resultItem = array(
        'id' => $arOrder['ID'],
        'actions' => $arActions,
        'data' => $arOrder,
        'editable' => 'N',
        'DEFAULT' => true
    );
    $arResult['GRID_DATA'][] = $resultItem;
    unset($resultItem);
}
if ($arResult['ENABLE_TOOLBAR']) {
    $APPLICATION->IncludeComponent(
        'bitrix:crm.interface.toolbar',
        '',
        array(
            'TOOLBAR_ID' => strtolower($arResult['GRID_ID']) . '_toolbar',
            'BUTTONS' => array(
                array(
                    'TEXT' => GetMessage('YNSIR_CONTACT_LIST_ADD_SHORT'),
                    'TITLE' => GetMessage('YNSIR_CONTACT_LIST_ADD'),
                    'LINK' => $arResult['PATH_TO_CONTACT_ADD'],
                    'ICON' => 'btn-new'
                )
            )
        ),
        $component,
        array('HIDE_ICONS' => 'Y')
    );
}

//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

    $yesnoList = array(
        array('NAME' => GetMessage('MAIN_YES'), 'VALUE' => 'Y'),
        array('NAME' => GetMessage('MAIN_NO'), 'VALUE' => 'N')
    );

    $snippet = new \Bitrix\Main\Grid\Panel\Snippet();
    $applyButton = $snippet->getApplyButton(
        array(
            'ONCHANGE' => array(
                array(
                    'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
                    'DATA' => array(array('JS' => "BX.YNSIRUIGridExtension.processApplyButtonClick('{$gridManagerID}')"))
                )
            )
        )
    );

    $actionList = array(array('NAME' => GetMessage('YNSIR_CONTACT_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));
//    $controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();
    $controlPanel['GROUPS'][0]['ITEMS'][] = array(
        "TYPE" => Bitrix\Main\Grid\Panel\Types::BUTTON,
        "ID" => "grid_associate_button",
        "CLASS" => "grid_associate_button",
        'TEXT' => GetMessage("YNSIR_ASSOCIATE_ORDER"),
        "NAME" =>  GetMessage("YNSIR_ASSOCIATE_ORDER"),
        "VALUE" => $arParams['ENTITY_ID'],
        "ONCHANGE" => array(
            array(
                "ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
                "DATA" => array(array("JS" => "Grid.associateSelectedtoCandidate()"))),
        )
    );
//    $controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();

if($isInternal) {
    // Render toolbar in internal mode
    $APPLICATION->ShowViewContent('ynsir-list-order-internal-filter');
}
//endregion
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
        'RENDER_FILTER_INTO_VIEW' => $isInternal ? 'ynsir-list-order-internal-filter' : '',
        'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
        'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'SHOW_ROW_CHECKBOXES' => true,
        'PAGE_SIZES' => array(
            array("NAME" => "5", "VALUE" => "5"),
        ),
        'SHOW_PAGESIZE' => false,
        'EXTENSION' => array(
            'ID' => $gridManagerID,
            'CONFIG' => array(
                'ownerTypeName' => YNSIR_JOB_ORDER,
                'gridId' => $arResult['GRID_ID'],
                'activityEditorId' => $activityEditorID,
                'taskCreateUrl' => isset($arResult['TASK_CREATE_URL']) ? $arResult['TASK_CREATE_URL'] : '',
                'serviceUrl' => '/bitrix/components/ynsirecruitment/job_order.list/list.ajax.php?siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
                'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
            ),
        )
    )
);
?>
