<a id="ynsir_show_candidate_a" href="/recruitment/candidate/detail/1/?QUIK_VIEW=Y" hidden>link</a>
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if (SITE_TEMPLATE_ID === 'bitrix24') {
    $APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable()) {
    CBitrix24::initLicenseInfoPopupJS();
}

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/ynsirecruitment/css/autorun_proc.css');

$isInternal = $arResult['IS_INTERNAL_ASSOCIATE'];
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'];
$allowWrite = $arResult['PERMS']['WRITE'] && !$callListUpdateMode;
$allowDelete = $arResult['PERMS']['DELETE'] && !$callListUpdateMode;
$currentUserID = $arResult['CURRENT_USER_ID'];
$activityEditorID = '';
if (!$isInternal):
    $activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
    $APPLICATION->IncludeComponent(
        'ynsirecruitment:activity.editor',
        '',
        array(
            'EDITOR_ID' => $activityEditorID,
            'PREFIX' => $arResult['GRID_ID'],
            'OWNER_TYPE' => YNSIR_OWNER_TYPE_CANDIDATE,
            'OWNER_ID' => 10230,
            'READ_ONLY' => false,
            'ENABLE_UI' => false,
            'ENABLE_TOOLBAR' => false
        ),
        null,
        array('HIDE_ICONS' => 'Y')
    );
endif;
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

foreach ($arResult['CANDIDATE'] as $sKey => $arProfile) {
    $arEntitySubMenuItems = array();
    $arActivitySubMenuItems = array();
    $arActions = array();
//TODO: COMMON ACTION
    $arResult['LIST_FIELD_KEYS_PERM'] = array();
    $arResult['LIST_FIELD_KEYS_NOT_PERM'] = array();

    $isUpdatePerms = YNSIRCandidate::CheckUpdatePermission($arProfile['ID'], $userPermissions);
    $isReadPerms = YNSIRCandidate::CheckReadPermission($arProfile['ID'], $userPermissions);

//TODO: REMOVE DATA IF USER DOESNOT HAVE PERMISSION
    $isReadPermsSec[YNSIRConfig::CS_BASIC_INFO] = YNSIRCandidate::CheckReadPermission($arProfile['ID'], $userPermissions, YNSIRConfig::CS_BASIC_INFO);
    $isReadPermsSec[YNSIRConfig::CS_ADDRESS_INFORMATION] = YNSIRCandidate::CheckReadPermission($arProfile['ID'], $userPermissions, YNSIRConfig::CS_ADDRESS_INFORMATION);
    $isReadPermsSec[YNSIRConfig::CS_PROFESSIONAL_DETAILS] = YNSIRCandidate::CheckReadPermission($arProfile['ID'], $userPermissions, YNSIRConfig::CS_PROFESSIONAL_DETAILS);
    $isReadPermsSec[YNSIRConfig::CS_OTHER_INFO] = YNSIRCandidate::CheckReadPermission($arProfile['ID'], $userPermissions, YNSIRConfig::CS_OTHER_INFO);
    $isReadPermsSec[YNSIRConfig::CS_ATTACHMENT_INFORMATION] = YNSIRCandidate::CheckReadPermission($arProfile['ID'], $userPermissions, YNSIRConfig::CS_ATTACHMENT_INFORMATION);

    foreach ($arResult['FIELD_CANDIDATE_VIEW'] as $SECTION_key => $SECTION_FIELD) {
        if (!$isReadPermsSec[$SECTION_key]) {
            //TODO: get Array FIELD KEYS cannot access by PERMISSION
            foreach ($SECTION_FIELD['FIELDS'] as $_arFIELDS) {
                switch ($_arFIELDS['KEY']) {
                    case 'ID':
                        break;
                    case 'NAME':
                        $arResult['LIST_FIELD_KEYS_NOT_PERM'][] = 'FIRST_NAME';
                        $arResult['LIST_FIELD_KEYS_NOT_PERM'][] = 'LAST_NAME';
                        $arResult['LIST_FIELD_KEYS_NOT_PERM'][] = '~FIRST_NAME';
                        $arResult['LIST_FIELD_KEYS_NOT_PERM'][] = '~LAST_NAME';
                        break;
                    default:
                        $arResult['LIST_FIELD_KEYS_NOT_PERM'][] = $_arFIELDS['KEY'];
                        $arResult['LIST_FIELD_KEYS_NOT_PERM'][] = '~' . $_arFIELDS['KEY'];
                        break;
                }

            }
            unset($_arFIELDS);
        } else {
            foreach ($SECTION_FIELD['FIELDS'] as $_arFIELDS) {
                $arResult['LIST_FIELD_KEYS_PERM'][] = $_arFIELDS['KEY'];
            }
            unset($_arFIELDS);
        }
    }
    unset($SECTION_key);
    unset($SECTION_FIELD);
    foreach ($arProfile as $KEY_FIELD => $value_field) {
        if (in_array($KEY_FIELD, $arResult['LIST_FIELD_KEYS_NOT_PERM'])) {
            unset($arProfile[$KEY_FIELD]);
        }
    }
    unset($value_field);

//End Remove permission
    $arProfile['FIRST_NAME'] = '<a href="'.$arProfile['PATH_TO_CONTACT_DETAIL'].'">'.$arProfile['FIRST_NAME'].'</a>';
    if ($arResult['IS_INTERNAL_ASSOCIATE']) {

        //ASSOCIATE JOB ORDER
        $arActions[] =  array(
            'TITLE' => GetMessage('YNSIR_ASSOCIATE_ORDER'),
            'TEXT' => GetMessage('YNSIR_ASSOCIATE_ORDER'),
            'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
					'{$gridManagerID}', 
					BX.YNSIRUIGridMenuCommand.associate, 
					{ list_associate_id: '{$sKey}',
					  current_id: '{$arParams['ENTITY_ID']}'}
				)"
        );
    }
    //Check status ID
    $resultItem = array(
        'id' => $arProfile['ID'],
        'actions' => $arActions,
        'data' => $arProfile,
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
    $APPLICATION->ShowViewContent('ynsir-list-internal-filter');
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
        'RENDER_FILTER_INTO_VIEW' => $isInternal ? 'ynsir-list-internal-filter' : '',
        'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
        'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'SHOW_ROW_CHECKBOXES' => true,
        'SHOW_MORE_BUTTON' => false,
        'PAGE_SIZES' => array(
            array("NAME" => "5", "VALUE" => "5"),
        ),
        'SHOW_PAGESIZE' => false,
        'EXTENSION' => array(
            'ID' => $gridManagerID,
            'CONFIG' => array(
                'ownerTypeName' => YNSIR_OWNER_TYPE_CANDIDATE,
                'gridId' => $arResult['GRID_ID'],
                'activityEditorId' => $activityEditorID,
//                'activityServiceUrl' => '/bitrix/components/ynsirecruitment/activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
                'taskCreateUrl' => isset($arResult['TASK_CREATE_URL']) ? $arResult['TASK_CREATE_URL'] : '',
                'serviceUrl' => '/bitrix/components/ynsirecruitment/candidate.list/list.ajax.php?siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
                'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
            ),
        )
    )
);
?>
