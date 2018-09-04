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

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/ynsirecruitment/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/ynsirecruitment/css/autorun_proc.css');

if ($arResult['PERMS']['ADD']) {

    $listAction = array();
    $listAction[] = array(
        "id" => "importFromExcel",
        "text" => 'Import CV From Excel',
        "action" => "showModalConvertFromExcel()",
        "className" => "hrm-report-export-div-print",
    );

    $listAction[] = array(
        "id" => "importFromResume",
        "text" => GetMessage('YNSIR_CT_IMPORT_FROM_RESUME'),
        "action" => "showModalConvert()",
        "className" => "hrm-report-export-div-csv",
    );

    $APPLICATION->IncludeComponent(
        'bitrix:crm.interface.toolbar',
        'title',
        array(
            'TOOLBAR_ID' => strtolower($arResult['GRID_ID']) . '_toolbar',
            'BUTTONS' => array(
                array(
                    'TEXT' => GetMessage('YNSIR_CT_ADD_CANDIDATE'),
                    'TITLE' => GetMessage('YNSIR_CT_ADD_CANDIDATE'),
                    'LINK' => "/recruitment/candidate/edit/0/",
                    'ID' => 'bx-sharepoint-sync'
                ),
            )
        ),
        $component,
        array('HIDE_ICONS' => 'Y')
    );

    $this->SetViewTarget('pagetitle', 225);
    ?>
    <div class="pagetitle-container pagetitle-align-right-container">
        <?
        if ($listAction): ?>
            <span id="lists-title-action"
                  class="webform-small-button webform-small-button-transparent bx-filter-button">
        <span class="webform-small-button-text"><?= GetMessage('YNSIR_CANDIDATE_TITLE_ACTION') ?></span>
        <span id="lists-title-action-icon" class="webform-small-button-icon"></span>
    </span>
        <? endif; ?>
    </div>

    <?
    $this->EndViewTarget();
}
//die;
if ($arResult['NEED_FOR_REBUILD_DUP_INDEX']):
    ?>
    <div id="rebuildContactDupIndexMsg" class="crm-view-message">
    <?= GetMessage('YNSIR_CONTACT_REBUILD_DUP_INDEX', array('#ID#' => 'rebuildContactDupIndexLink', '#URL#' => '#')) ?>
    </div><?
endif;

if ($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):
    ?>
    <div id="rebuildContactSearchWrapper"></div><?
endif;

if ($arResult['NEED_FOR_REBUILD_CONTACT_ATTRS']):
    ?>
    <div id="rebuildContactAttrsMsg" class="crm-view-message">
    <?= GetMessage('YNSIR_CONTACT_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildContactAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST'])) ?>
    </div><?
endif;

if ($arResult['NEED_FOR_TRANSFER_REQUISITES']):
    ?>
    <div id="transferRequisitesMsg" class="crm-view-message">

    <?= Bitrix\YNSIR\Requisite\EntityRequisiteConverter::getIntroMessage(
    array(
        'EXEC_ID' => 'transferRequisitesLink', 'EXEC_URL' => '#',
        'SKIP_ID' => 'skipTransferRequisitesLink', 'SKIP_URL' => '#'
    )
) ?>
    </div><?
endif;
$isInternal = $arResult['INTERNAL'];
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

    // share folder
    if($isReadPermsSec[YNSIRConfig::CS_ATTACHMENT_INFORMATION] == true && isset($arResult['SHARE_FOLDER'][$arProfile['ID']]) && !$USER->IsAdmin()){
        YNSIRDisk::shareFolder($arResult['SHARE_FOLDER'][$arProfile['ID']], array(
                array('ID' => $USER::GetID(),'TYPE' => YNSIRDisk::PERMS_TYPE_USER,'PERMS' => YNSIRDisk::PERMS_ACCESS_READ))
        );
    }
    // end

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


    if ($isReadPerms) {
        if ($arProfile['PATH_TO_CONTACT_DETAIL'] != '') {
            $arActions[] = array(
                'TITLE' => GetMessage('YNSIR_CANDIDATE_TITLE_QUICK_VIEW'),
                'TEXT' => GetMessage('YNSIR_CANDIDATE_TITLE_QUICK_VIEW'),
                'ONCLICK' => "quikview(" . $sKey . ")",
                'DEFAULT' => true
            );
            ?>
            <a id="ynsir_show_candidate_<?= $sKey ?>" href="/recruitment/candidate/detail/<?= $sKey ?>/?QUIK_VIEW=Y"
               hidden></a>
            <?
        }
        if ($arProfile['PATH_TO_CONTACT_DETAIL'] != '') {
            $clickAction = "jsUtils.Redirect([], '" . CUtil::JSEscape($arProfile['PATH_TO_CONTACT_DETAIL']) . "');";
            $arProfile['FIRST_NAME'] = '<a href="javascript:void(0)" onclick="'.$clickAction.'">'.$arProfile['FIRST_NAME'].'</a>';
            $arActions[] = array(
                'TITLE' => GetMessage('YNSIR_CANDIDATE_TITLE_DETAIL_PROFILE'),
                'TEXT' => GetMessage('YNSIR_CANDIDATE_TITLE_DETAIL_PROFILE'),
                'ONCLICK' => "jsUtils.Redirect([], '" . CUtil::JSEscape($arProfile['PATH_TO_CONTACT_DETAIL']) . "');",
            );
        }
    }
    if ($isUpdatePerms) {
        if (!empty($arProfile['PATH_TO_CONTACT_EDIT'])) {
            $arActions[] = array(
                'TITLE' => GetMessage('YNSIR_CANDIDATE_TITLE_EDIT_PROFILE'),
                'TEXT' => GetMessage('YNSIR_CANDIDATE_TITLE_EDIT_PROFILE'),
                'ONCLICK' => "jsUtils.Redirect([], '" . CUtil::JSEscape($arProfile['PATH_TO_CONTACT_EDIT']) . "');"
            );
        }
    }
    //ACTION
//    echo $sKey ;
//    $sKey = 5;
    $arActions[] = array(
        'TITLE' => 'Send email',
        'TEXT' => 'Send email',
        'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.YNSIRUIGridMenuCommand.createActivity, 
						{ typeId: BX.YNSIRActivityType.email, settings: { ownerID: {$sKey} } }
					)"
    );
    $arActivitySubMenuItems[] = array(
        'TITLE' => GetMessage('YNSIR_CANDIDATE_TITLE_CALL'),
        'TEXT' => GetMessage('YNSIR_CANDIDATE_TITLE_CALL'),
        'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
                        '{$gridManagerID}', 
                        BX.YNSIRUIGridMenuCommand.createActivity, 
                        { typeId: BX.YNSIRActivityType.call, settings: { ownerID: {$sKey} } }
                    )"
    );

    $arActivitySubMenuItems[] = array(
        'TITLE' => GetMessage('YNSIR_CANDIDATE_TITLE_INTERVIEW'),
        'TEXT' => GetMessage('YNSIR_CANDIDATE_TITLE_INTERVIEW'),
        'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
                        '{$gridManagerID}', 
                        BX.YNSIRUIGridMenuCommand.createActivity, 
                        { typeId: BX.YNSIRActivityType.meeting, settings: { ownerID: {$sKey} } }
                    )"
    );
    $arActivitySubMenuItems[] = array(
        'TITLE' => GetMessage('YNSIR_CANDIDATE_TITLE_TASK'),
        'TEXT' => GetMessage('YNSIR_CANDIDATE_TITLE_TASK'),
        'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
                        '{$gridManagerID}', 
                        BX.YNSIRUIGridMenuCommand.createActivity,
                        { typeId: BX.YNSIRActivityType.task, settings: { ownerID: {$sKey} } }
                    )"
    );
//    echo $sKey . '<hr>';

    $arActions[] = array(
        'TITLE' => GetMessage('YNSIR_CANDIDATE_TITLE_PLAN'),
        'TEXT' => GetMessage('YNSIR_CANDIDATE_TITLE_PLAN'),
        'MENU' => $arActivitySubMenuItems
    );
    $resultItem = array(
        'id' => $arProfile['ID'],
        'actions' => $arActions,
        'data' => $arProfile,
        'editable' => 'N',
        'DEFAULT' => true
    );
    $userActivityID = isset($arProfile['~ACTIVITY_ID']) ? intval($arProfile['~ACTIVITY_ID']) : 0;
    $commonActivityID = isset($arProfile['~C_ACTIVITY_ID']) ? intval($arProfile['~C_ACTIVITY_ID']) : 0;
    if ($userActivityID > 0) {
        $resultItem['columns']['ACTIVITY_ID'] = CYNSIRViewHelper::RenderNearestActivity(
            array(
                'ENTITY_TYPE_NAME' => YNSIROwnerType::ResolveName(YNSIR_OWNER_TYPE_CANDIDATE),
                'ENTITY_ID' => $arProfile['~ID'],
                'ENTITY_RESPONSIBLE_ID' => $arProfile['~ASSIGNED_BY'],
                'GRID_MANAGER_ID' => $gridManagerID,
                'ACTIVITY_ID' => $userActivityID,
                'ACTIVITY_SUBJECT' => isset($arProfile['~ACTIVITY_SUBJECT']) ? $arProfile['~ACTIVITY_SUBJECT'] : '',
                'ACTIVITY_TIME' => isset($arProfile['~ACTIVITY_TIME']) ? $arProfile['~ACTIVITY_TIME'] : '',
                'ACTIVITY_EXPIRED' => isset($arProfile['~ACTIVITY_EXPIRED']) ? $arProfile['~ACTIVITY_EXPIRED'] : '',
                'ALLOW_EDIT' => 'Y',
                'MENU_ITEMS' => $arActivitySubMenuItems,
                'USE_GRID_EXTENSION' => true
            )
        );

        $counterData = array(
            'CURRENT_USER_ID' => $currentUserID,
            'ENTITY' => $arProfile,
            'ACTIVITY' => array(
                'RESPONSIBLE_ID' => $currentUserID,
                'TIME' => isset($arProfile['~ACTIVITY_TIME']) ? $arProfile['~ACTIVITY_TIME'] : '',
                'IS_CURRENT_DAY' => isset($arProfile['~ACTIVITY_IS_CURRENT_DAY']) ? $arProfile['~ACTIVITY_IS_CURRENT_DAY'] : false
            )
        );

        if ($counterData['ACTIVITY']['IS_CURRENT_DAY']) {
            $resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-deal-today');
        }
    } elseif ($commonActivityID > 0) {
        $resultItem['columns']['ACTIVITY_ID'] = CYNSIRViewHelper::RenderNearestActivity(
            array(
                'ENTITY_TYPE_NAME' => YNSIROwnerType::ResolveName(YNSIR_OWNER_TYPE_CANDIDATE),
                'ENTITY_ID' => $arProfile['~ID'],
                'ENTITY_RESPONSIBLE_ID' => $arProfile['~ASSIGNED_BY'],
                'GRID_MANAGER_ID' => $gridManagerID,
                'ACTIVITY_ID' => $commonActivityID,
                'ACTIVITY_SUBJECT' => isset($arProfile['~C_ACTIVITY_SUBJECT']) ? $arProfile['~C_ACTIVITY_SUBJECT'] : '',
                'ACTIVITY_TIME' => isset($arProfile['~C_ACTIVITY_TIME']) ? $arProfile['~C_ACTIVITY_TIME'] : '',
                'ACTIVITY_RESPONSIBLE_ID' => isset($arProfile['~C_ACTIVITY_RESP_ID']) ? intval($arProfile['~C_ACTIVITY_RESP_ID']) : 0,
                'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arProfile['~C_ACTIVITY_RESP_LOGIN']) ? $arProfile['~C_ACTIVITY_RESP_LOGIN'] : '',
                'ACTIVITY_RESPONSIBLE_NAME' => isset($arProfile['~C_ACTIVITY_RESP_NAME']) ? $arProfile['~C_ACTIVITY_RESP_NAME'] : '',
                'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arProfile['~C_ACTIVITY_RESP_LAST_NAME']) ? $arProfile['~C_ACTIVITY_RESP_LAST_NAME'] : '',
                'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arProfile['~C_ACTIVITY_RESP_SECOND_NAME']) ? $arProfile['~C_ACTIVITY_RESP_SECOND_NAME'] : '',
                'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
                'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
                'ALLOW_EDIT' => true,
                'MENU_ITEMS' => $arActivitySubMenuItems,
                'USE_GRID_EXTENSION' => true
            )
        );
    } else {
        $resultItem['columns']['ACTIVITY_ID'] = CYNSIRViewHelper::RenderNearestActivity(
            array(
                'ENTITY_TYPE_NAME' => YNSIROwnerType::ResolveName(YNSIR_OWNER_TYPE_CANDIDATE),
                'ENTITY_ID' => $arProfile['ID'],
                'ENTITY_RESPONSIBLE_ID' => 12,
                'GRID_MANAGER_ID' => $gridManagerID,
                'ALLOW_EDIT' => 1,
                'MENU_ITEMS' => $arActivitySubMenuItems,
                'USE_GRID_EXTENSION' => true
            )
        );

        $counterData = array(
            'CURRENT_USER_ID' => $currentUserID,
            'ENTITY' => $arProfile
        );

//        if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentLeadActivies, $counterData))
        {
            $resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-enitity-action-need');
        }
    }
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

if (!$isInternal
    && ($allowWrite || $allowDelete || $callListUpdateMode)) {
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

    if ($allowWrite) {
        //region Add Email
        if (IsModuleInstalled('subscribe')) {
            $actionList[] = array(
                'NAME' => GetMessage('YNSIR_CONTACT_SUBSCRIBE'),
                'VALUE' => 'subscribe',
                'ONCHANGE' => array(
                    array(
                        'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
                        'DATA' => array($applyButton)
                    ),
                    array(
                        'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
                        'DATA' => array(array('JS' => "BX.YNSIRUIGridExtension.processActionChange('{$gridManagerID}', 'subscribe')"))
                    )
                )
            );
        }
        //endregion
        //region Add Task
        if (IsModuleInstalled('tasks')) {
            $actionList[] = array(
                'NAME' => GetMessage('YNSIR_CONTACT_TASK'),
                'VALUE' => 'tasks',
                'ONCHANGE' => array(
                    array(
                        'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
                        'DATA' => array($applyButton)
                    ),
                    array(
                        'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
                        'DATA' => array(array('JS' => "BX.YNSIRUIGridExtension.processActionChange('{$gridManagerID}', 'tasks')"))
                    )
                )
            );
        }
        //endregion
        //region Assign To
        //region Render User Search control
        if (!Bitrix\Main\Grid\Context::isInternalRequest()) {
            //action_assigned_by_search + _control
            //Prefix control will be added by main.ui.grid
            $APPLICATION->IncludeComponent(
                'bitrix:intranet.user.selector.new',
                '',
                array(
                    'MULTIPLE' => 'N',
                    'NAME' => "{$prefix}_ACTION_ASSIGNED_BY",
                    'INPUT_NAME' => 'action_assigned_by_search_control',
                    'SHOW_EXTRANET_USERS' => 'NONE',
                    'POPUP' => 'Y',
                    'SITE_ID' => SITE_ID,
                    'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE']
                ),
                null,
                array('HIDE_ICONS' => 'Y')
            );
        }
        //endregion
        $actionList[] = array(
            'NAME' => GetMessage('YNSIR_CONTACT_ASSIGN_TO'),
            'VALUE' => 'assign_to',
            'ONCHANGE' => array(
                array(
                    'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
                    'DATA' => array(
                        array(
                            'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
                            'ID' => 'action_assigned_by_search',
                            'NAME' => 'ACTION_ASSIGNED_BY_SEARCH'
                        ),
                        array(
                            'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
                            'ID' => 'action_assigned_by_id',
                            'NAME' => 'ACTION_ASSIGNED_BY_ID'
                        ),
                        $applyButton
                    )
                ),
                array(
                    'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
                    'DATA' => array(
                        array('JS' => "BX.YNSIRUIGridExtension.prepareAction('{$gridManagerID}', 'assign_to',  { searchInputId: 'action_assigned_by_search_control', dataInputId: 'action_assigned_by_id_control', componentName: '{$prefix}_ACTION_ASSIGNED_BY' })")
                    )
                ),
                array(
                    'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
                    'DATA' => array(array('JS' => "BX.YNSIRUIGridExtension.processActionChange('{$gridManagerID}', 'assign_to')"))
                )
            )
        );
    }

    if ($allowDelete) {
        $Removebtn = $snippet->getRemoveButton();
        $Removebtn['ONCHANGE'][0]['CONFIRM_MESSAGE'] = GetMessage('YNSIR_CANDIDATE_DELETE_CONFIRM');
        $controlPanel['GROUPS'][0]['ITEMS'][] = $Removebtn;
        $actionList[] = $snippet->getRemoveAction();
    }

    if ($allowWrite) {
        //region Edit Button
        $controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getEditButton();
        $actionList[] = $snippet->getEditAction();
        //endregion
        //region Mark as Opened
        $actionList[] = array(
            'NAME' => GetMessage('YNSIR_CONTACT_MARK_AS_OPENED'),
            'VALUE' => 'mark_as_opened',
            'ONCHANGE' => array(
                array(
                    'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
                    'DATA' => array(
                        array(
                            'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
                            'ID' => 'action_opened',
                            'NAME' => 'ACTION_OPENED',
                            'ITEMS' => $yesnoList
                        ),
                        $applyButton
                    )
                ),
                array(
                    'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
                    'DATA' => array(array('JS' => "BX.YNSIRUIGridExtension.processActionChange('{$gridManagerID}', 'mark_as_opened')"))
                )
            )
        );
        //endregion
        //region Export
        $actionList[] = array(
            'NAME' => GetMessage('YNSIR_CONTACT_EXPORT'),
            'VALUE' => 'export',
            'ONCHANGE' => array(
                array(
                    'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
                    'DATA' => array(
                        array(
                            'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
                            'ID' => 'action_export',
                            'NAME' => 'ACTION_EXPORT',
                            'ITEMS' => $yesnoList
                        ),
                        $applyButton
                    )
                ),
                array(
                    'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
                    'DATA' => array(array('JS' => "BX.YNSIRUIGridExtension.processActionChange('{$gridManagerID}', 'mark_as_opened')"))
                )
            )
        );
        //endregion
    }

    if ($callListUpdateMode) {
        $controlPanel['GROUPS'][0]['ITEMS'][] = array(
            "TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
            "TEXT" => GetMessage("YNSIR_CONTACT_UPDATE_CALL_LIST"),
            "ID" => "update_call_list",
            "NAME" => "update_call_list",
            'ONCHANGE' => array(
                array(
                    'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
                    'DATA' => array(array('JS' => "BX.YNSIRUIGridExtension.updateCallList('{$gridManagerID}', {$arResult['CALL_LIST_ID']}, '{$arResult['CALL_LIST_CONTEXT']}')"))
                )
            )
        );
    }
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
        'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
        'SHOW_CHECK_ALL_CHECKBOXES' => true, // checkbox : check all
        'SHOW_ROW_CHECKBOXES' => true,
        'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
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
            'MESSAGES' => array(
                'deletionDialogTitle' => GetMessage('YNSIR_CANDIDATE_DELETE_TITLE'),
                'deletionDialogMessage' => GetMessage('YNSIR_CANDIDATE_DELETE_CONFIRM'),
                'deletionDialogButtonTitle' => GetMessage('YNSIR_CANDIDATE_DELETE')
            )
        )
    )
);
?>
<div id="popupshowModalConvert" style="display: none">
    <div id="file-selectdialog-RESUME_FILE" class="file-selectdialog" style="display: block; opacity: 1;"
         dropzone="copy f:*/*">
        <div class="file-extended" style="display: none;">
            <span class="file-label">Files:</span>
            <div class="file-placeholder">
                <table id="files-resume-list" class="files-list" cellspacing="0">
                    <tbody class="file-placeholder-tbody">

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?
    $APPLICATION->IncludeComponent(
        'ynsirecruitment:disk.file.upload',
        '.default',
        array(
            'BUTTON_MESSAGE' => 'Upload',
            'ONUPLOADDONE' => 'convertcv',
            'ALLOW_UPLOAD_EXT' => array('doc', 'docx', 'pdf'),
            'HTML_ELEMENT_ID' => 'convertcv',
        )
    );
    ?>
    <p style="color: red" id="upload_empty"></p>

</div>
<div id="popupshowModalConvertFromExcel" style="display: none">
    <div id="file-selectdialog-EXCEL_FILE" class="file-selectdialog" style="display: block; opacity: 1;"
         dropzone="copy f:*/*">
        <div class="file-extended" style="display: none;">
            <span class="file-label">Files:</span>
            <div class="file-placeholder">
                <table id="files-excel-list" class="files-list" cellspacing="0">
                    <tbody class="file-placeholder-tbody">

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?
    $APPLICATION->IncludeComponent(
        'ynsirecruitment:disk.file.upload',
        '.default',
        array(
            'BUTTON_MESSAGE' => 'Upload',
            'ONUPLOADDONE' => 'convertexcel',
            'ALLOW_UPLOAD_EXT' => array('xlsx'),
            'HTML_ELEMENT_ID' => 'convertexcel',
            'FILE_MULTIPLE' => false
        )
    );
    ?>
    <p style="color: red" id="upload_empty"></p>
    <div id='file_template' class="ynsir-show-ext-file-cv">Download: <a href="/recruitment/candidate-system/candidate-system.xlsx" downloaded>File template</a></div>
</div>
<script>


    // Get the <span> element that closes the modal
    function uploadResume() {

    }

    $('#hrm_profile_list_print').click(function () {

    });

    $('.main-ui-filter-reset').click(function () {
        setTimeout(function () {
            document.location.href = '/hrm/profile/';
        }, 1000)
    });


    function showModalConvert() {
        $('#upload_empty').text('');
        var convertResume = new BX.PopupWindow('popup_showModalConvert', window.body, {
            content: BX('popupshowModalConvert'),
            width: 700,
            autoHide: false,
            offsetTop: 1,
            offsetLeft: 0,
            overlay: true,
            closeByEsc: false,
            async: true,
            draggable: {restrict: true},
            titleBar: {content: BX.create("span", {html: "<?=GetMessage('YNSIR_CT_TITLE_CONVERT')?>"})},
            buttons: [
                new BX.PopupWindowButton({
                    text: "<?=GetMessage('YNSIR_CT_TITLE_CONVERT')?>    ",
                    className: "popup-window-button-accept",
                    id: "convertResume_btn",
                    events: {
                        click: function () {
                            try {
                                BX.showWait();
                                var popup = this;
                                var er = $('#file_empty');
                                if(er != undefined){
                                    er.remove();
                                }
                                var btn = $('#convertResume_btn');
                                btn.css("pointer-events", "none");
                                btn.addClass("bp-button-wait");

                                var nFile = $("input[name^='RESUME_ID[]']").length;
                                var arFile = [];
                                var i = 0;
                                $("input[name^='RESUME_ID[]']").each(function (index) {
                                    var idFile = $(this).val();
                                    arFile[i] = idFile;
                                    $("#wd-doc" + idFile).find(".files-storage-block").html('<div class="pending"><b>Pending</b></div>');
                                    i++;
                                });

                                function callAjaxFunc(index, value) {

                                    $("#wd-doc" + value).find(".del-but").remove();
                                    $("#wd-doc" + value).find(".files-storage-block").html('<div id="loader_ ' + value + '" class="loader"></div>');

                                    BX.ajax({
                                        url: '/recruitment/candidate/edit/0/',
                                        method: 'POST',
                                        dataType: 'json',
                                        data: {'ACTION': 'CONVERT', "MULTIPLE": "Y", 'ID': value},
                                        onsuccess: function (data) {
                                            if (index < nFile - 1) {
                                                callAjaxFunc(index + 1, arFile[index + 1]);
                                            } else {
                                                BX.closeWait();
                                                $(".file-selector").hide();
                                                $('#bx-disk-container-convert').hide();
                                                $("#btn_cancel").hide();
                                                $("#convertResume_btn").hide();
                                                $("#btn_close").css("display", "block");
                                            }
                                            $("#loader_" + data.FILE_ID).remove();
                                            if (data.ERROR == 'Y') {
                                                $("#wd-doc" + data.FILE_ID).find(".files-storage-block").html(
                                                    '<div style="float: left" class="ynsir-a-convert bp-status-failed"></div>' +
                                                    '<div style="padding-top: 3px">' +
                                                    '<a style="color: red;" class="ynsir-a-convert-failed" href="/recruitment/candidate/edit/' + data.CANDIDATE_ID + '/" target="_blank">' + data.MESSAGE + '</a>' +
                                                    '<a class="ynsir-a-convert-failed" href="/recruitment/candidate/detail/' + data.CANDIDATE_ID + '/?QUIK_VIEW=Y" target="_blank"> <?=GetMessage('YNSIR_CANDIDATE_TITLE_DETAIL')?></a></div>');
                                            } else if (data.ERROR == 'E') {
                                                $("#wd-doc" + data.FILE_ID).find(".files-storage-block").html(
//                                                    '<div style="float: left" class="ynsir-a-convert bp-status-failed"></div>' +
                                                    '<div style="color: red;" class="ynsir-a-convert-failed" >' + data.MESSAGE + '</div>'
                                                    );

                                            } else {
                                                $("#wd-doc" + data.FILE_ID).find(".files-storage-block").html(
                                                    '<div class="ynsir-a-convert bp-status"></div>' +
                                                    '<a class="ynsir-a-convert" href="/recruitment/candidate/detail/' + data.CANDIDATE_ID + '/?QUIK_VIEW=Y" target="_blank"><?=GetMessage('YNSIR_CANDIDATE_TITLE_DETAIL')?></a>'
                                                    + '<a class="ynsir-a-convert" href="/recruitment/candidate/edit/' + data.CANDIDATE_ID + '/" target="_blank"><?=GetMessage('YNSIR_CANDIDATE_TITLE_EDIT')?></a>');
                                            }
                                        }
                                    });
                                }
                                if(nFile > 0){
                                    $('.cv-profile-user-item-del').remove();
                                    $('#bx-disk-container-convertcv').hide();
                                    callAjaxFunc(0, arFile[0]);
                                }else {
                                    btn.removeClass("bp-button-wait");
                                    $('#bx-disk-container-convertcv').append('<div style="color:red" id="file_empty"><?=GetMessage('YNSIR_CANDIDATE_TITLE_ERROR_EMPTY')?></div>');
                                    BX.closeWait();
                                    btn.css("pointer-events", "auto");
                                }
                            } catch (e) {
                                console.log(e);
//                                window.location.reload();
                            }
                        }
                    }
                }),
                new BX.PopupWindowButton({
                    text: "<?=GetMessage('YNSIR_CT_BUTTON_CANCEL')?>",
                    className: "popup-window-button-cancel",
                    id: "btn_cancel",
                    events: {
                        click: function () {
                            window.location.reload();
                        }
                    }
                }),
                new BX.PopupWindowButton({
                    text: "DONE AND CLOSE",
                    className: "popup-window-button-accept hidden",
                    id: "btn_close",
                    events: {
                        click: function () {
                            this.popupWindow.close();
                            window.location.reload();
                        }
                    }
                })
            ]
        });
        convertResume.show();
    }

    function showModalConvertFromExcel() {
        $('#upload_empty').text('');
        var convertResume = new BX.PopupWindow('popup_showModalConvertFromExcel', window.body, {
            content: BX('popupshowModalConvertFromExcel'),
            width: 580,
            autoHide: false,
            offsetTop: 1,
            offsetLeft: 0,
            overlay: true,
            closeByEsc: false,
            async: true,
            draggable: {restrict: true},
            titleBar: {content: BX.create("span", {html: "Import candidate from Excel"})},
            buttons: [
                new BX.PopupWindowButton({
                    text: "Import candidate from Excel",
                    className: "popup-window-button-accept",
                    id: "convertResumeFromExcel_btn",
                    events: {
                        click: function () {
                            try {
                                BX.showWait();
                                var popup = this;
                                var btn = $('#btn_excel_close');
                                btn.css("pointer-events", "none");
                                btn.addClass("bp-button-wait");
                                var file = $("input[name^='RESUME_EXCEL_ID']").val();
                                var er = $('#file_empty');
                                if(er != undefined){
                                    er.remove();
                                }
                                if (file > 0) {
                                    $('.excel-profile-user-item-del').remove();

                                    $(".file-selector").hide();
                                    $('#bx-disk-container-convertexcel').hide();
                                    $('#file_template').hide();
                                    $('#convertResumeFromExcel_btn').css("pointer-events", "none");
                                    $('#btn_excel_cancel').css("pointer-events", "none");
                                    var htlmLoad = '<div id="load_samsum">' +
                                        '    <svg xmlns="http://www.w3.org/2000/svg" version="1.1">' +
                                        '        <defs>' +
                                        '            <filter id="gooey">' +
                                        '                <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur"></feGaussianBlur>' +
                                        '                <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -7" result="goo"></feColorMatrix>' +
                                        '                <feBlend in="SourceGraphic" in2="goo"></feBlend>' +
                                        '            </filter>' +
                                        '        </defs>' +
                                        '    </svg>' +
                                        '    <div class="blob blob-0"></div>' +
                                        '    <div class="blob blob-1"></div>' +
                                        '    <div class="blob blob-2"></div>' +
                                        '    <div class="blob blob-3"></div>' +
                                        '    <div class="blob blob-4"></div>' +
                                        '    <div class="blob blob-5"></div>' +
                                        '</div>';
                                    $('#popupshowModalConvertFromExcel').css('padding-bottom', '60px');
                                    $('#popupshowModalConvertFromExcel').append(htlmLoad);

                                    BX.ajax({
                                        url: '/recruitment/candidate/edit/0/',
                                        method: 'POST',
                                        dataType: 'json',
                                        data: {'ACTION': 'IMPORT_EXCEL', "MULTIPLE": "Y", 'ID': file},
                                        onsuccess: function (data) {
                                            $('#load_samsum').hide();
                                            $('#popupshowModalConvertFromExcel').css('padding-bottom', '5px');
                                            var nSUCCESS = 0;
                                            var nError = 0;

                                            if (data.SUCCESS != undefined)
                                                nSUCCESS = data.SUCCESS.length;
                                            if (data.ERROR != undefined)
                                                nError = data.ERROR.length;
                                            $("#btn_excel_cancel").hide();
                                            $("#convertResumeFromExcel_btn").hide();
                                            $("#btn_excel_close").css("display", "block");
                                            $('#popupshowModalConvertFromExcel').append(
                                                '<div style="padding-left: 10px"> ' +
                                                '<div  style="padding-left: 89px;float: left">Result: </div>' +
                                                '<div style="float: left">' +
                                                '        <span  class="ynsir-a-convert bp-status">' + nSUCCESS +
                                                '        </span>' +
                                                '</div>' +
                                                '<div style="float: left">' +
                                                '        <span style="float: left" class="ynsir-a-convert bp-status-failed">' + nError +
                                                '        </span> ' +
                                                '</div>' +
                                                '<div style="clear: both;"></div>' +
                                                '<a id="show-less-detail" stage="less-detail" onclick="toggleDetail()"><?=GetMessage('YNSIR_CANDIDATE_TITLE_SHOW_MORE');?></a>' +
                                                '<table id="show_report" hidden>' +
                                                '  <tr>' +
                                                '    <th style="width: 30%"><?=GetMessage('YNSIR_CANDIDATE_TITLE_LAST_NAME')?></th>' +
                                                '    <th><?=GetMessage('YNSIR_CANDIDATE_TITLE_FIST_NAME')?></th>' +
                                                '    <th><?=GetMessage('YNSIR_CANDIDATE_TITLE_STATUS')?></th>' +
                                                '    <th><?=GetMessage('YNSIR_CANDIDATE_TITLE_DETAIL')?></th>' +
                                                '    <th><?=GetMessage('YNSIR_CANDIDATE_TITLE_EDIT')?></th>' +
                                                '  </tr>' +
                                                '</table>' +
                                                '</div>'
                                            );
//                                            var nError = data.ERROR.length;

                                            if (data.SUCCESS && data.SUCCESS.length > 0) {
                                                for (i in data.SUCCESS) {
                                                    $('#popupshowModalConvertFromExcel').find('#show_report').append(
                                                        '<tr>' +
                                                        '   <td>' + data.SUCCESS[i]['CANDIDATE_NAME']['LAST_NAME'] + '</td>' +
                                                        '   <td>' + data.SUCCESS[i]['CANDIDATE_NAME']['FIRST_NAME'] + '</td>' +
                                                        '   <td style="color: #63ab02">' + data.SUCCESS[i]['MESSAGE'] + '</td>' +
                                                        '   <td>' + '<a style="color: #63ab02" href="/recruitment/candidate/detail/' + data.SUCCESS[i]['CANDIDATE_ID'] + '/?QUIK_VIEW=Y" target="_blank"><?=GetMessage('YNSIR_CANDIDATE_TITLE_DETAIL')?></a>' +
                                                        '   </td>' +
                                                        '   <td>' + '<a style="color: #63ab02" href="/recruitment/candidate/edit/' + data.SUCCESS[i]['CANDIDATE_ID'] + '/" target=" _blank"><?=GetMessage('YNSIR_CANDIDATE_TITLE_EDIT')?></a>' +
                                                        '   </td>' +
                                                        '</tr>'
                                                    );
                                                }
                                            }
                                            if (data.ERROR && data.ERROR.length > 0) {
                                                for (i in data.ERROR) {
                                                    $('#popupshowModalConvertFromExcel').find('#show_report').append(
                                                        '<tr>' +
                                                        '   <td>' + data.ERROR[i]['CANDIDATE_NAME']['LAST_NAME'] + '</td>' +
                                                        '   <td>' + data.ERROR[i]['CANDIDATE_NAME']['FIRST_NAME'] + '</td>' +
                                                        '   <td style="color: red;">' + data.ERROR[i]['MESSAGE'] + '</td>' +
                                                        '   <td>' +
                                                        '   <a style="color: red;"   href="/recruitment/candidate/detail/' + data.ERROR[i]['CANDIDATE_ID'] + '/?QUIK_VIEW=Y" target="_blank"><?=GetMessage('YNSIR_CANDIDATE_TITLE_DETAIL')?></a>' +
                                                        '   </td>' +
                                                        '   <td>' + '<a style="color: red;" href="/recruitment/candidate/edit/' + data.ERROR[i]['CANDIDATE_ID'] + '/" target="_blank"><?=GetMessage('YNSIR_CANDIDATE_TITLE_EDIT')?></a>' +
                                                        '   </td>' +
                                                        '</tr>'
                                                    );
                                                }
                                            }
                                            BX.closeWait();
                                            btn.css("pointer-events", "auto");
                                            btn.removeClass("bp-button-wait");
                                        }
                                    });
                                } else {
                                    BX.closeWait();
                                    $('#bx-disk-container-convertexcel').append('<div style="color:red" id="file_empty"><?=GetMessage('YNSIR_CANDIDATE_TITLE_ERROR_EMPTY')?></div>');
                                    btn.css("pointer-events", "auto");
                                    btn.removeClass("bp-button-wait");
                                }

                            } catch (e) {
                                console.log(e);
//                                window.location.reload();
                            }
                        }
                    }
                }),
                new BX.PopupWindowButton({
                    text: "<?=GetMessage('YNSIR_CT_BUTTON_CANCEL')?>",
                    className: "popup-window-button-cancel",
                    id: "btn_excel_cancel",
                    events: {
                        click: function () {
                            window.location.reload();
                        }
                    }
                }),
                new BX.PopupWindowButton({
                    text: "DONE AND CLOSE",
                    className: "popup-window-button-accept hidden",
                    id: "btn_excel_close",
                    events: {
                        click: function () {
                            this.popupWindow.close();
                            window.location.reload();
                        }
                    }
                })
            ]
        });
        convertResume.show();
    }
    
    //endregion
    function toggleDetail() {
        var stage = $("#show-less-detail").attr('stage');
        if(stage == 'less-detail'){
            $("#show-less-detail").attr('stage','show-detail');
            $("#show-less-detail").text("<?=GetMessage('YNSIR_CANDIDATE_TITLE_LESS_MORE');?>");
        }else{
            $("#show-less-detail").attr('stage','less-detail');
            $("#show-less-detail").text("<?=GetMessage('YNSIR_CANDIDATE_TITLE_SHOW_MORE');?>");
        }

        $("#show_report").toggle();
    }

    $(document).ready(function () {
        ListActions = new ListActions({
            listAction: <?=\Bitrix\Main\Web\Json::encode($listAction)?>
        });
    });
</script><?
?>
<script type="text/javascript">
    BX.ready(
        function () {

            if (typeof(BX.YNSIRSipManager.messages) === 'undefined') {
                BX.YNSIRSipManager.messages =
                    {
                        "unknownRecipient": "<?= GetMessageJS('YNSIR_SIP_MGR_UNKNOWN_RECIPIENT')?>",
                        "makeCall": "<?= GetMessageJS('YNSIR_SIP_MGR_MAKE_CALL')?>"
                    };
            }
        }
    );
</script>

<? if (!$isInternal || true): ?>
    <script type="text/javascript">

        BX.ready(
            function () {

                BX.YNSIRActivityEditor.items['<?= CUtil::JSEscape($activityEditorID)?>'].addActivityChangeHandler(
                    function () {
                        BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
                    }
                );
                BX.namespace('BX.YNSIR.Activity');

                if (typeof BX.YNSIR.Activity.Planner !== 'undefined') {
                    BX.YNSIR.Activity.Planner.Manager.setCallback(
                        'onAfterActivitySave',
                        function () {
                            BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
                        }
                    );
                }
            }
        );

    </script>
<? endif; ?>
<script type="text/javascript">
    BX.ready(
        function () {
            BX.YNSIRLongRunningProcessDialog.messages =
                {
                    startButton: "<?=GetMessageJS('YNSIR_CONTACT_LRP_DLG_BTN_START')?>",
                    stopButton: "<?=GetMessageJS('YNSIR_CONTACT_LRP_DLG_BTN_STOP')?>",
                    closeButton: "<?=GetMessageJS('YNSIR_CONTACT_LRP_DLG_BTN_CLOSE')?>",
                    wait: "<?=GetMessageJS('YNSIR_CONTACT_LRP_DLG_WAIT')?>",
                    requestError: "<?=GetMessageJS('YNSIR_CONTACT_LRP_DLG_REQUEST_ERR')?>"
                };
        }
    );
</script>
<? if ($arResult['NEED_FOR_REBUILD_DUP_INDEX']): ?>
    <script type="text/javascript">
        BX.ready(
            function () {
                BX.YNSIRDuplicateManager.messages =
                    {
                        rebuildContactIndexDlgTitle: "<?=GetMessageJS('YNSIR_CONTACT_REBUILD_DUP_INDEX_DLG_TITLE')?>",
                        rebuildContactIndexDlgSummary: "<?=GetMessageJS('YNSIR_CONTACT_REBUILD_DUP_INDEX_DLG_SUMMARY')?>"
                    };
                var mgr = BX.YNSIRDuplicateManager.create("mgr", {
                    entityTypeName: "<?=CUtil::JSEscape(YNSIROwnerType::ContactName)?>",
                    serviceUrl: "<?=SITE_DIR?>bitrix/components/ynsirecruitment/candidate.list/list.ajax.php?&<?=bitrix_sessid_get()?>"
                });
                BX.addCustomEvent(
                    mgr,
                    'ON_CONTACT_INDEX_REBUILD_COMPLETE',
                    function () {
                        var msg = BX("rebuildContactDupIndexMsg");
                        if (msg) {
                            msg.style.display = "none";
                        }
                    }
                );

                var link = BX("rebuildContactDupIndexLink");
                if (link) {
                    BX.bind(
                        link,
                        "click",
                        function (e) {
                            mgr.rebuildIndex();
                            return BX.PreventDefault(e);
                        }
                    );
                }
            }
        );
    </script>
<? endif; ?>
<? if ($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']): ?>
    <script type="text/javascript">
        BX.ready(
            function () {
                if (BX.AutorunProcessPanel.isExists("rebuildContactSearch")) {
                    return;
                }

                BX.AutorunProcessManager.messages =
                    {
                        title: "<?=GetMessageJS('YNSIR_CONTACT_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
                        stateTemplate: "<?=GetMessageJS('YNSIR_REBUILD_SEARCH_CONTENT_STATE')?>"
                    };
                var manager = BX.AutorunProcessManager.create("rebuildContactSearch",
                    {
                        serviceUrl: "<?='/bitrix/components/ynsirecruitment/candidate.list/list.ajax.php?' . bitrix_sessid_get()?>",
                        actionName: "REBUILD_SEARCH_CONTENT",
                        container: "rebuildContactSearchWrapper",
                        enableLayout: true
                    }
                );
                manager.runAfter(100);
            }
        );
    </script>
<? endif; ?>
<? if ($arResult['NEED_FOR_REBUILD_CONTACT_ATTRS']): ?>
    <script type="text/javascript">
        BX.ready(
            function () {
                var link = BX("rebuildContactAttrsLink");
                if (link) {
                    BX.bind(
                        link,
                        "click",
                        function (e) {
                            var msg = BX("rebuildContactAttrsMsg");
                            if (msg) {
                                msg.style.display = "none";
                            }
                        }
                    );
                }
            }
        );
    </script>
<? endif; ?>
<? if ($arResult['NEED_FOR_TRANSFER_REQUISITES']): ?>
    <script type="text/javascript">
        BX.ready(
            function () {
                BX.YNSIRRequisitePresetSelectDialog.messages =
                    {
                        title: "<?=GetMessageJS("YNSIR_CONTACT_RQ_TX_SELECTOR_TITLE")?>",
                        presetField: "<?=GetMessageJS("YNSIR_CONTACT_RQ_TX_SELECTOR_FIELD")?>"
                    };

                BX.YNSIRRequisiteConverter.messages =
                    {
                        processDialogTitle: "<?=GetMessageJS('YNSIR_CONTACT_RQ_TX_PROC_DLG_TITLE')?>",
                        processDialogSummary: "<?=GetMessageJS('YNSIR_CONTACT_RQ_TX_PROC_DLG_DLG_SUMMARY')?>"
                    };

                var converter = BX.YNSIRRequisiteConverter.create(
                    "converter",
                    {
                        entityTypeName: "<?=CUtil::JSEscape(YNSIROwnerType::ContactName)?>",
                        serviceUrl: "<?=SITE_DIR?>bitrix/components/ynsirecruitment/candidate.list/list.ajax.php?&<?=bitrix_sessid_get()?>"
                    }
                );

                BX.addCustomEvent(
                    converter,
                    'ON_CONTACT_REQUISITE_TRANFER_COMPLETE',
                    function () {
                        var msg = BX("transferRequisitesMsg");
                        if (msg) {
                            msg.style.display = "none";
                        }
                    }
                );

                var transferLink = BX("transferRequisitesLink");
                if (transferLink) {
                    BX.bind(
                        transferLink,
                        "click",
                        function (e) {
                            converter.convert();
                            return BX.PreventDefault(e);
                        }
                    );
                }

                var skipTransferLink = BX("skipTransferRequisitesLink");
                if (skipTransferLink) {
                    BX.bind(
                        skipTransferLink,
                        "click",
                        function (e) {
                            converter.skip();

                            var msg = BX("transferRequisitesMsg");
                            if (msg) {
                                msg.style.display = "none";
                            }

                            return BX.PreventDefault(e);
                        }
                    );
                }
            }
        );
    </script>
<? endif; ?>
