<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION, $USER;
//$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->AddHeadScript('/bitrix/js/ynsirecruitment/interface_grid.js');

if(isset($arResult['ERROR']))
{
    echo $arResult['ERROR'];
    return;
}
if (!CModule::IncludeModule("blog")) {
//    ShowError(GetMessage("MODULE_NOT_INSTALL"));
    return;
}
$user_id = $USER->GetID();
$arConfig = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_bizproc_config'));
$arConfigOnboardingStatus = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_onboarding_status'));

$dbResult = CBPWorkflowTemplateLoader::GetList(array(), array("ID" => array_values($arConfig)), false, false, array("ID", "MODULE_ID", "ENTITY", "NAME"));
while ($rs = $dbResult->Fetch()){
    $bpTemplate[$rs['ID']] = $rs;
}
$gridManagerID = $managerID = $arResult['GRID_ID'].'_MANAGER';
$prefix = $arResult['GRID_ID'];
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

$arResult['GRID_DATA'] = array();
//TODO: Normalize status for select round status popup

foreach($arResult['CONFIG']['INTERVIEW'] as $id_order => $each_order) {
    foreach($each_order as $interview_id => $interview) {
        $arResult['CONFIG']['INTERVIEW_NORMALIZE'][$id_order][]= array( 'KEY'=>$interview['ID'],
                                                                        'VALUE'=>GetMessage('YNSIR_ROUND_LABEL',array('#ROUND_INDEX#' => $interview['ROUND_INDEX'])),
                                                                        'PARTICIPANTS' => array_keys($interview['PARTICIPANT']));
    }
    unset($interview_id);
    unset($interview);
}
unset($id_order);
unset($each_order);

//TODO: Normalize status for select status popup
$arStatus = $arResult['CONFIG']['CANDIDATE_STATUS_LIST'];
foreach($arStatus as $status_id => $status_obj){
    $arResult['CONFIG']['STATUS_NORMALIZE'][] = array('KEY'=> $status_id,'VALUE'=> $status_obj);
}
unset($status_id);
unset($status_obj);

//End TODO
foreach($arResult['ASSOCIATE'] as $arAssociate) {
    $arActions = array();
    $b_AllowDel = false;
    //Disable DELETE event deletion

    $pathToRemove = CUtil::JSEscape($arAssociate['PATH_TO_ASSOCIATE_DELETE']);
    $arActions[] = array(
        'TITLE' => GetMessage('YNSIR_ASSOCIATE_DELETE_TITLE'),
        'TEXT' => GetMessage('YNSIR_ASSOCIATE_DELETE'),
        'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
                '{$gridManagerID}', 
                BX.YNSIRUIGridMenuCommand.remove, 
                { pathToRemove: '{$pathToRemove}' }
            )"
    );

    $arActions[] = array(
        'TITLE' => GetMessage('YNSIR_ASSOCIATE_CHANGE_STATUS_TITLE'),
        'TEXT' => GetMessage('YNSIR_ASSOCIATE_CHANGE_STATUS_TITLE'),
        'ONCLICK' => "AssociateGrid.ChangeStatusDialog(" . $arAssociate['ID'] . ",'" . $arAssociate['STATUS_ID'] . "'," . CUtil::PhpToJSObject($arResult['CONFIG']['STATUS_NORMALIZE']) . ",'STATUS',null,null)"
    );

    if( !empty($arResult['CONFIG']['INTERVIEW_NORMALIZE'][$arAssociate['ORDER_JOB_ID']])) {
        $arActions[] = array(
            'TITLE' => GetMessage('YNSIR_CHANGESTATUS_ROUND_TITLE'),
            'TEXT' => GetMessage('YNSIR_CHANGESTATUS_ROUND_TITLE'),
            'ONCLICK' => "AssociateGrid.ChangeStatusDialog(" . $arAssociate['ID'] . ",'" . $arAssociate['STATUS_ROUND_ID'] . "'," . CUtil::PhpToJSObject($arResult['CONFIG']['INTERVIEW_NORMALIZE'][$arAssociate['ORDER_JOB_ID']]) . ",'STATUS_ROUND',null,null)"
        );
    }
    //Activity
    $arActivitySubMenuItemsLv2 = array();
    $arActivityFeedbackLv2 = array();
    $arActivitySubMenuItems = array();
    foreach ($arResult['CONFIG']['INTERVIEW_NORMALIZE'][$arAssociate['ORDER_JOB_ID']] as $idx => $arRound) {
        $arActivitySubMenuItemsLv2[] = array(
            'TITLE' => $arRound['VALUE'],
            'TEXT' => $arRound['VALUE'],
            'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.YNSIRUIGridMenuCommand.createActivity, 
						{ typeId: BX.YNSIRActivityType.meeting,
						settings: { ownerID: {$arAssociate['CANDIDATE_ID']},ownerOrder:{$arAssociate['ORDER_JOB_ID']},
						            ownerRound:'{$arRound['VALUE']}',ownerRoundID:'{$arRound['KEY']}',ownerInterViewer:[" . implode(',', $arRound['PARTICIPANTS']) . "]}}
					)"
        );
        $arActivityFeedbackLv2[] = array(
            'TITLE' => $arRound['VALUE'],
            'TEXT' => $arRound['VALUE'],
            'ONCLICK' => "addFeedbackElementOwner({$arAssociate['CANDIDATE_ID']},{$arAssociate['ORDER_JOB_ID']},{$arRound['KEY']})"
        );
    }
    unset($idx);
    unset($arRound);
    //start action to run business process
    $arBpSubMenuItemsLv2 = array();
    if($arParams ['ENTITY_TYPE'] == YNSIR_JOB_ORDER && $arConfig['BIZPROC_BIZ_SCAN_CV_ID'] > 0 && !empty($bpTemplate[$arConfig['BIZPROC_BIZ_SCAN_CV_ID']])&&$arAssociate['STATUS_ID'] == 'NEW') {
        $arBpSubMenuItemsLv2[] = array(
            'TITLE' => $bpTemplate[$arConfig['BIZPROC_BIZ_SCAN_CV_ID']]['NAME'],
            'TEXT' =>$bpTemplate[$arConfig['BIZPROC_BIZ_SCAN_CV_ID']]['NAME'],
            'ONCLICK' => "AssociateGrid.showPopUpScanCV(" . $arAssociate['CANDIDATE_ID'] . ")"
        );
    }

    if($arParams ['ENTITY_TYPE'] == YNSIR_JOB_ORDER && $arConfig['BIZPROC_BIZ_ONBOARDING_ID'] > 0
        && !empty($bpTemplate[$arConfig['BIZPROC_BIZ_ONBOARDING_ID']])
        && in_array($arAssociate['STATUS_ID'],$arConfigOnboardingStatus))
    {
        $arBpSubMenuItemsLv2[] = array(
            'TITLE' => $bpTemplate[$arConfig['BIZPROC_BIZ_ONBOARDING_ID']]['NAME'],
            'TEXT' => $bpTemplate[$arConfig['BIZPROC_BIZ_ONBOARDING_ID']]['NAME'],
            'ONCLICK' => "AssociateGrid.showPopUpOnboardingProcess(" . $arAssociate['CANDIDATE_ID'] . ")"
        );
    }

    if(!empty($arBpSubMenuItemsLv2)) {
        $arActions[] = array(
            'TITLE' => GetMessage('YNSIR_LABLE_RUN_BP'),
            'TEXT' => GetMessage('YNSIR_LABLE_RUN_BP'),
            'MENU' => $arBpSubMenuItemsLv2
        );
    }
    //end action to run business process
    if (!empty($arActivitySubMenuItemsLv2)) {

        $arActivitySubMenuItems[] = array(
            'TITLE' => GetMessage('YNSIR_CANDIDATE_TITLE_INTERVIEW'),
            'TEXT' => GetMessage('YNSIR_CANDIDATE_TITLE_INTERVIEW'),
            'MENU' => $arActivitySubMenuItemsLv2
        );
    }
    $arActivitySubMenuItems[] = array(
        'TITLE' => GetMessage('YNSIR_ASSOCIATE_TITLE_CALL'),
        'TEXT' => GetMessage('YNSIR_ASSOCIATE_TITLE_CALL'),
        'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
                    '{$gridManagerID}', 
                    BX.YNSIRUIGridMenuCommand.createActivity, 
                    { typeId: BX.YNSIRActivityType.call, settings: { ownerID: {$arAssociate['CANDIDATE_ID']},ownerOrder:{$arAssociate['ORDER_JOB_ID']} } }
                )"
    );
    $arActivitySubMenuItems[] = array(
        'TITLE' => GetMessage('YNSIR_ASSOCIATE_TITLE_TASK'),
        'TEXT' => GetMessage('YNSIR_ASSOCIATE_TITLE_TASK'),
        'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.YNSIRUIGridMenuCommand.createActivity,
						{ typeId: BX.YNSIRActivityType.task, settings: { ownerID: {$arAssociate['CANDIDATE_ID']}, associateID: {$arAssociate['ID']} } }
					)"
    );
    if($arParams['ENTITY_TYPE'] == YNSIR_JOB_ORDER) {
        $arActions[] = array(
            'TITLE' => GetMessage('YNSIR_LABLE_SEND_EMAIL'),
            'TEXT' => GetMessage('YNSIR_LABLE_SEND_EMAIL'),
            'ONCLICK' => "BX.YNSIRUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.YNSIRUIGridMenuCommand.createActivity, 
						{ typeId: BX.YNSIRActivityType.email, settings: { ownerID: {$arAssociate['CANDIDATE_ID']},job_orderID: {$arAssociate['ORDER_JOB_ID']} } }
					)"
        );
        if (!empty($arActivityFeedbackLv2)) {
            if($arParams['ENTITY_TYPE'] == YNSIR_JOB_ORDER) {
                $rs = YNSIRJobOrder::getById($arParams['ENTITY_ID']);
                if ($rs['RECRUITER'] > 0 && $user_id == $rs['RECRUITER']) {
                    $arActions[] = array(
                        'TITLE' => GetMessage('YNSIR_LABLE_FEEDBACK'),
                        'TEXT' => GetMessage('YNSIR_LABLE_FEEDBACK'),
                        'MENU' => $arActivityFeedbackLv2
                    );
                }
            }
        }

    }

    if($USER->GetID() == $arParams){

    }
    if (!empty($arActivitySubMenuItems)) {
        $arActions[] = array(
            'TITLE' => GetMessage('YNSIR_ASSOCIATE_TITLE_PLAN'),
            'TEXT' => GetMessage('YNSIR_ASSOCIATE_TITLE_PLAN'),
            'MENU' => $arActivitySubMenuItems
        );
    }
    //End
    $authorHtml = '';
    if ($arAssociate['CREATED_BY_FULL_NAME'] !== '') {
        $authorHtml = "<div class = \"crm-client-summary-wrapper\">
				<div class = \"crm-client-photo-wrapper\">
					<div class=\"crm-client-def-pic\">
						<img alt=\"Author Photo\" src=\"{$arAssociate['CREATED_BY_PHOTO_URL']}\"/>
					</div>
				</div>
				<div class=\"crm-client-info-wrapper\">
					<div class=\"crm-client-title-wrapper\">
						<a href=\"{$arAssociate['CREATED_BY_LINK']}\" id=\"balloon_{$arResult['GRID_ID']}_{$arAssociate['ID']}\">{$arAssociate['CREATED_BY_FULL_NAME']}</a>
						<script type=\"text/javascript\">BX.tooltip({$arAssociate['CREATED_BY']}, \"balloon_{$arResult['GRID_ID']}_{$arAssociate['ID']}\", \"\");</script>
					</div>
				</div>
			</div>";
    }
    if ($arAssociate['MODIFIED_BY_FULL_NAME'] !== '') {
        $modifiledHtml = "<div class = \"crm-client-summary-wrapper\">
				<div class = \"crm-client-photo-wrapper\">
					<div class=\"crm-client-def-pic\">
						<img alt=\"Author Photo\" src=\"{$arAssociate['MODIFIED_BY_PHOTO_URL']}\"/>
					</div>
				</div>
				<div class=\"crm-client-info-wrapper\">
					<div class=\"crm-client-title-wrapper\">
						<a href=\"{$arAssociate['MODIFIED_BY_LINK']}\" id=\"balloon_{$arResult['GRID_ID']}_{$arAssociate['ID']}\">{$arAssociate['MODIFIED_BY_FULL_NAME']}</a>
						<script type=\"text/javascript\">BX.tooltip({$arAssociate['MODIFIED_BY']}, \"balloon_{$arResult['GRID_ID']}_{$arAssociate['ID']}\", \"\");</script>
					</div>
				</div>
			</div>";
    }
    if ($arAssociate['CANDIDATE_FULL_NAME'] !== '') {
        $arAssociate['CANDIDATE_FULL_NAME'] = "<a href=\"{$arAssociate['CANDIDATE_BY_LINK']}\" id=\"candidate_{$arResult['GRID_ID']}_{$arAssociate['CANDIDATE_ID']}\">{$arAssociate['CANDIDATE_FULL_NAME']}</a>";
    }
    if ($arAssociate['ORDER_JOB_TITLE'] !== '') {
        $arAssociate['ORDER_JOB_TITLE'] = "<a href=\"{$arAssociate['JOB_ORDER_BY_LINK']}\" id=\"order_{$arResult['GRID_ID']}_{$arAssociate['ORDER_JOB_ID']}\">{$arAssociate['ORDER_JOB_TITLE']}</a>";
    }

    //CHECK LOCK CANDIDATE
    $checkLockAssociate = YNSIRAssociateJob::checkCandiateLock($arAssociate['CANDIDATE_ID']);
    if ($checkLockAssociate['IS_LOCK'] == 'Y') {
        if(($checkLockAssociate['ORDER_JOB_ID'] != $arAssociate['ORDER_JOB_ID'] && $arParams ['ENTITY_TYPE'] == YNSIR_CANDIDATE)
            || ($checkLockAssociate['ORDER_JOB_ID'] != $arParams['ENTITY_ID'] && $arParams ['ENTITY_TYPE'] == YNSIR_JOB_ORDER)) {
            //remove action if candidate is locked by other job
            $arActions = array();
            if($arParams ['ENTITY_TYPE'] == YNSIR_JOB_ORDER) {
                if(strlen($checkLockAssociate['ORDER_JOB_TITLE']) > 0) {
                    //Associate is Locked with Jobs
                    $arAssociate['CANDIDATE_FULL_NAME'] = '<div class="ynsir-associate-candidate-lock" title="'.GetMessage("YNSIR_ASSOCIATE_CA_LOCK_TITLE", array('#JOB_TITLE#'=>$checkLockAssociate['ORDER_JOB_TITLE'])).'">' . $arAssociate['CANDIDATE_FULL_NAME'] . '</div>';
                } else {
                    //Candiate is locked itself
                    $arAssociate['CANDIDATE_FULL_NAME'] = '<div class="ynsir-associate-candidate-lock" title="'.GetMessage("YNSIR_ASSOCIATE_CA_LOCK_ITSELF_TITLE", array('#JOB_TITLE#'=>$checkLockAssociate['ORDER_JOB_TITLE'])).'">' . $arAssociate['CANDIDATE_FULL_NAME'] . '</div>';
                }
            }
        } else {
            $arAssociate['CANDIDATE_FULL_NAME'] = '<div class="ynsir-associate-candidate-lock">' . $arAssociate['CANDIDATE_FULL_NAME'] . '</div>';
            $arAssociate['ORDER_JOB_TITLE'] = '<div class="ynsir-associate-candidate-lock">' . $arAssociate['ORDER_JOB_TITLE'] . '</div>';
            $arActions = array(array(
                'TITLE' => GetMessage('YNSIR_ASSOCIATE_CHANGE_STATUS_TITLE') . GetMessage('YNSIR_ASSOCIATE_CHANGE_STATUS_UNLOCK'),
                'TEXT' => GetMessage('YNSIR_ASSOCIATE_CHANGE_STATUS_TITLE') .  GetMessage('YNSIR_ASSOCIATE_CHANGE_STATUS_UNLOCK'),
                'ONCLICK' => "AssociateGrid.ChangeStatusDialog(" . $arAssociate['ID'] . ",'" . $arAssociate['STATUS_ID'] . "'," . CUtil::PhpToJSObject($arResult['CONFIG']['STATUS_NORMALIZE']) . ",'STATUS',null,null)"
            ));
        }
    }
    $candiateHtml = $arAssociate['CANDIDATE_FULL_NAME'];
    $orderHtml = $arAssociate['ORDER_JOB_TITLE'];

    $arColumns = array(
        'CREATED_BY_FULL_NAME' => $authorHtml,
        'MODIFIED_BY_FULL_NAME' => $modifiledHtml,
        'ORDER_JOB_TITLE' => $orderHtml,
        'CANDIDATE_FULL_NAME' => $candiateHtml,
        'CREATED_DATE' => FormatDate('x', MakeTimeStamp($arAssociate['CREATED_DATE']), (time() + CTimeZone::GetOffset())),
        'MODIFIED_DATE' => FormatDate('x', MakeTimeStamp($arAssociate['MODIFIED_DATE']), (time() + CTimeZone::GetOffset()))
    );


    $arResult['GRID_DATA'][] = array(
        'id' => $arAssociate['ID'],
        'data' => $arAssociate,
        'actions' => $arActions,
        'editable' =>($USER->IsAdmin() || ($arAssociate['CREATED_BY'] == $USER->GetId() && $arAssociate['EVENT_TYPE'] == 0))? true: false,
        'columns' => $arColumns
    );
}

$APPLICATION->IncludeComponent('bitrix:main.user.link',
    '',
    array(
        'AJAX_ONLY' => 'Y',
        'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"]
    ),
    false,
    array('HIDE_ICONS' => 'Y')
);

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

    $unassociateBtn = $snippet->getRemoveButton();
    $unassociateBtn['TEXT'] = GetMessage("YNSIR_UNASSOCIATE_GROUP_BUTTON");
    $unassociateBtn['NAME'] = GetMessage("YNSIR_UNASSOCIATE_GROUP_BUTTON");
    $unassociateBtn['ONCHANGE'][0]['CONFIRM_APPLY_BUTTON'] ='Unassociate';
    $unassociateBtn['ONCHANGE'][0]['CONFIRM_MESSAGE'] ='Confirm action for selected items';
    $unassociateBtn['ONCHANGE'][0]['CONFIRM_CANCEL_BUTTON'] ='Cancel';


if($arResult['INTERNAL'])
{
    // Render toolbar in internal mode
    $APPLICATION->ShowViewContent('associate-interal-filter');
}
if($arParams['ENTITY_TYPE'] == YNSIR_JOB_ORDER) {
//    JOStatus::JOSTATUS_CLOSED
    $rsOrder = YNSIRJobOrder::GetList(array(),array('ID' => $arParams['ENTITY_ID'],'CHECK_PERMISSIONS' => 'N'));
    $arOrder = $rsOrder->Fetch();
    $contentURL = "/bitrix/components/ynsirecruitment/candidate.list/lazyload.ajax.php?&site=".SITE_ID."&".bitrix_sessid_get();
    if($arOrder['STATUS'] != JOStatus::JOSTATUS_CLOSED):
    ?>
    <div id="ynsir-associate-btn" class="associate-grid-add-btn webform-small-button-blue pagetitle-container pagetitle-align-right-container associate-grid-add-btn">
        <span class="main-grid-add-text" onClick="AddAssociate()"
              title=""><?= GetMessage('YNSIR_TITLE_SEARCH_CANDIDATE') ?></span>
        <span class="main-grid-more-load-text"><?=GetMessage('YNSIR_LOADING_DATA_TITLE')?></span>
        <span class="main-grid-more-icon"></span>
    </div>
    <?
    endif;

    $arMessagesSearchAssociate = array(
        'YNSIR_TITLE_SEARCH_POPUP' => GetMessage('YNSIR_TITLE_SEARCH_CANDIDATE'),
    );
    $arPostDataSearchAssociate = array(
        'template' => 'internal_associate',
        'enableLazyLoad' => true,
        'contextId' => "CANDIDATE_" . $arParams['ENTITY_ID'] . "_LIST",
        'params' => array(
            'CONTACT_COUNT' => 5,
            'AJAX_OPTION_ADDITIONAL' => "CANDIDATE_" . $arParams['ENTITY_ID'] . "_LIST",
            'ENTITY_TYPE' => YNSIR_JOB_ORDER,
            'ENTITY_ID' => $arParams['ENTITY_ID'],
            'PATH_TO_USER_PROFILE' => "/company/personal/user/#user_id#/",//$arParams['PATH_TO_USER_PROFILE'],
            'FORM_ID' => $arResult['FORM_ID'],
            'TAB_ID' => 'tab_candidate_list',
            'INTERNAL' => 'Y',
            'SHOW_INTERNAL_FILTER' => 'Y',
            'PRESERVE_HISTORY' => true,
            'IS_INTERNAL_ASSOCIATE' => 'Y',
            'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
        )
    );
} else if($arParams['ENTITY_TYPE'] == YNSIR_CANDIDATE) {
    $checkLockAssociate = YNSIRAssociateJob::checkCandiateLock($arParams['ENTITY_ID']);
    $contentURL = "/bitrix/components/ynsirecruitment/job_order.list/lazyload.ajax.php?&site=" . SITE_ID . "&" . bitrix_sessid_get();
    if ($checkLockAssociate['IS_LOCK'] !== 'Y') {
        ?>
        <div id="ynsir-associate-btn"
             class="associate-grid-add-btn webform-small-button-blue pagetitle-container pagetitle-align-right-container associate-grid-add-btn">
        <span class="main-grid-add-text" onClick="AddAssociate()"
              title=""><?= GetMessage('YNSIR_TITLE_SEARCH_JOB_ORDER') ?></span>
            <span class="main-grid-more-load-text"><?=GetMessage('YNSIR_LOADING_DATA_TITLE')?></span>
            <span class="main-grid-more-icon"></span>
        </div>
        <?
    }
        $arMessagesSearchAssociate = array(
            'YNSIR_TITLE_SEARCH_POPUP' => GetMessage('YNSIR_TITLE_SEARCH_JOB_ORDER'),
        );
        $arPostDataSearchAssociate = array(
            'template' => 'internal_associate',
            'enableLazyLoad' => true,
            'contextId' => "CANDIDATE_" . $arParams['ENTITY_ID'] . "_LIST",
            'params' => array(
                'PAGE_COUNT' => 5,
                'AJAX_OPTION_ADDITIONAL' => "CANDIDATE_" . $arParams['ENTITY_ID'] . "_LIST",
                'ENTITY_TYPE' => YNSIR_CANDIDATE,
                'ENTITY_ID' => $arParams['ENTITY_ID'],
                'PATH_TO_USER_PROFILE' => "/company/personal/user/#user_id#/",//$arParams['PATH_TO_USER_PROFILE'],
                'FORM_ID' => $arResult['FORM_ID'],
                'TAB_ID' => 'tab_internal_order_list',
                'INTERNAL' => 'Y',
                'SHOW_INTERNAL_FILTER' => 'Y',
                'PRESERVE_HISTORY' => true,
                'IS_INTERNAL_JOB_LIST' => 'Y',
                'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
            )
        );
}

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
        'RENDER_FILTER_INTO_VIEW' => $arResult['INTERNAL'] ? 'associate-interal-filter' : '',
        'DISABLE_SEARCH' => true,
        'ACTION_PANEL' => array(),
        'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
            ? $arResult['PAGINATION'] : array(),
        'ENABLE_ROW_COUNT_LOADER' => true,
        'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
        'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'SHOW_ROW_CHECKBOXES' => false,
        'EXTENSION' => array(
            'ID' => $gridManagerID,
            'CONFIG' => array(
                'ownerTypeName' => YNSIR_OWNER_TYPE_CANDIDATE,
                'gridId' => $arResult['GRID_ID'],
                'activityEditorId' => $activityEditorID,
                'serviceUrl' => '/bitrix/components/ynsirecruitment/ynsir.associate.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
                'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
            ),
            'MESSAGES' => array(
                'deletionDialogTitle' => GetMessage('YNSIR_UNASSOCIATE_TITLE'),
                'deletionDialogMessage' => GetMessage('YNSIR_UNASSOCIATE_CONFIRM'),
                'deletionDialogButtonTitle' => GetMessage('YNSIR_UNASSOCIATE_BUTTON')
            )
        ),
    ),
    $component
);
$arMessage = array(
    'ChangeStatusDialogTitle' => GetMessage('YNSIR_ASSOCIATE_CHANGE_STATUS_TITLE'),
    'ChangeStatusDialogMessage' => GetMessage('YNSIR_CHANGESTATUS_CONFIRM'),
    'ChangeStatusDialogButtonTitle' => GetMessage('YNSIR_CHANGESTATUS_BUTTON'),
    'startBpButtonTitle' => GetMessage('YNSIR_START_BP_BUTTON'),
    'startBpOnboardingButtonTitle' => GetMessage('YNSIR_START_BP_ONBOARDING_BUTTON'),
    'ChangeStatusDialogButtonCancel' => GetMessage('YNSIR_BUTTON_CANCEL'),
);
if($arParams['ENTITY_TYPE'] == YNSIR_JOB_ORDER) {

    $link = '/recruitment/job-order/detail/#order_id#/index.php?'.$arResult['FORM_ID'].'_active_tab=tab_bizproc&bizproc_start_popup=#start_popup#&bizproc_start=#biz_start#&workflow_template_id=#workflow_template_id#&sessid=#secsion_id#&back_url=#back_url#';
    $URL_BP_SCAN = CComponentEngine::MakePathFromTemplate(
            $link,
            array('order_id' => $arAssociate['ORDER_JOB_ID'],
                    'start_popup' => 1,
                    'biz_start' => 1,
                    'workflow_template_id' => $arConfig['BIZPROC_BIZ_SCAN_CV_ID'],
                    'secsion_id' => bitrix_sessid(),
                    'back_url'=>''//'/recruitment/job-order/detail/1/?RECRUITMENT_JOB_ORDER_SHOW_V1_active_tab=tab_bizproc',

                ));
    $URL_BP_ONBOARDIND = CComponentEngine::MakePathFromTemplate(
            $link,
            array('order_id' => $arAssociate['ORDER_JOB_ID'],
                    'start_popup' => 1,
                    'biz_start' => 1,
                    'workflow_template_id' => $arConfig['BIZPROC_BIZ_ONBOARDING_ID'],
                    'secsion_id' => bitrix_sessid(),
                    'back_url'=>''//'/recruitment/job-order/detail/1/?RECRUITMENT_JOB_ORDER_SHOW_V1_active_tab=tab_bizproc',

                ));
    }
?>
<div class="<?=$arResult['GRID_ID']?>-scancv-dialog" id ="chang-status-confirm-dialog" style="display:none">
</div>
<div class="<?=$arResult['GRID_ID']?>-associate-dialog" id ="chang-status-confirm-dialog" style="display:none">
    <div id ="<?=$arResult['GRID_ID']?>-associate-dialog-content">
        <div class="main-grid-confirm-content">
            <div class="main-grid-confirm-content-title"><?=GetMessage('YNSIR_CHANGESTATUS_CONFIRM')?></div>
            <select class="recruitment-item-table-select" id="<?=$arResult['GRID_ID']?>-status-select">
            </select>
        </div>
    </div>
</div>
<div style="display:none">
    <div id ="<?=$arResult['GRID_ID']?>-associate-change-round-status-dialog-content">
        <div class="main-grid-confirm-content">
            <div class="main-grid-confirm-content-title"><?=GetMessage('YNSIR_CHANGESTATUS_ROUND_CONFIRM')?></div>
            <select class="recruitment-item-table-select" id="<?=$arResult['GRID_ID']?>-status-round-select">
            </select>
        </div>
    </div>
</div>

<div id="popupAddFeedbackOwner" class="ajax-popup"  hidden>

</div>
<div id = "popupnonificationOwner" class="feed-add-success" style="display: none;width: 300px;">
    <div id="div_success">
    </div>
</div>
<script type="text/javascript">
    BX.ready(
        function()
        {
            AssociateGrid = new Associategrid(
                {'messages':<?=CUtil::PhpToJSObject($arMessage)?>,
                'url_bp_scan':<?=CUtil::PhpToJSObject($URL_BP_SCAN)?>,
                'url_bp_onboarding':<?=CUtil::PhpToJSObject($URL_BP_ONBOARDIND)?>,
                },
                "<?=$arResult['GRID_ID']?>"
            );
            //console.log(<?=CUtil::PhpToJSObject($URL_BP_SCAN)?>);
        }
    );
    function AddAssociate(){
            var isLock = <?=CUtil::PhpToJSObject($checkLockAssociate['IS_LOCK'] == 'Y' && $arParams['ENTITY_TYPE'] == YNSIR_CANDIDATE)?>;
            var Height = Math.max(550, window.innerHeight - 400);
            var Width = Math.max(800, window.innerWidth - 400);
            var minHeight = 550;
            var minWidth = 800;
            if(isLock) {
                Height = 50;
                Width = 300;
                minHeight = 50;
                minWidth = 300;
            }
            var dlg = YNSIRAssociateSearchDialogWindow.create({
                content_url: "<?=$contentURL?>",
                grid_id:"<?= CUtil::JSEscape($arResult['GRID_ID'])?>",
                contentdata:<?=CUtil::PhpToJSObject($arPostDataSearchAssociate)?>,
                jsEventsManagerId: this.jsEventsManagerId,
                height: Height,
                width: Width,
                minHeight: minHeight,
                minWidth: minWidth,
                draggable: true,
                resizable: true,
                messages:<?=CUtil::PhpToJSObject($arMessagesSearchAssociate)?>
            });
            dlg.show();
    }
</script>
<script>
    function addFeedbackElementOwner(canddiate_id,job_order_id,round_id) {
        $('#strErr span.error').html('');
        BX.ajax.insertToNode('/recruitment/feedback/edit/0/?candidate_id='+canddiate_id+'&job_order_id='+job_order_id+'&round_id='+round_id, BX('popupAddFeedbackOwner'));
        popupformOwner.setTitleBar({content: BX.create("span", {html: JSMessOwner.YNSIR_ADD_LIST_TITLE,props:{'className': 'popup-window-titlebar-text'}})});
        $('#add_feedbackOwner').text(JSMessOwner.YNSIR_LIST_ADD_BTN);
        popupformOwner.show();
        $("#popupAddFeedbackOwner").css("height", "auto");
    }

    var JSMessOwner = {
        YNSIR_ADD_LIST_TITLE: '<?=GetMessage('YNSIR_ADD_LIST_TITLE', array('#ENTITY#' => $arResult['TYPE_LIST']['NAME']))?>',
        YNSIR_EDIT_LIST_TITLE: '<?=GetMessage('YNSIR_EDIT_LIST_TITLE', array('#ENTITY#' => $arResult['TYPE_LIST']['NAME']))?>',
        YNSIR_LIST_ADD_BTN: '<?=GetMessage('YNSIR_ADD_BTN')?>',
        YNSIR_LIST_EDIT_BTN: '<?=GetMessage('YNSIR_EDIT_BTN')?>',
        YNSIR_SUCCESS_MESSAGE: '<?=GetMessage('YNSIR_SUCCESS_MESSAGE')?>',
        YNSIR_LIST_CANCEL_BTN: '<?=GetMessage('YNSIR_CANCEL_BTN')?>',
        YNSIR_LIST_KEY_EN_NULL: "<?=GetMessage('YNSIR_KEY_EN_NULL')?>",
        YNSIR_TITLE_NOTIFICATION: "<?=GetMessage('YNSIR_KEY_NOTIFICATION')?>",
        YNSIR_FEEDBACK_DELETE_CONFIRM: "<?=GetMessage('YNSIR_FEEDBACK_DELETE_CONFIRM')?>"
    }
    BX.ready(function () {
        var Height = Math.max(450, window.innerHeight - 500);
        var Width = Math.max(750, window.innerWidth - 600);
        var minHeight = 400;
        var minWidth = 600;
        popupformOwner = new BX.PopupWindow("schema", null, {
            content: BX('popupAddFeedbackOwner'),
            zIndex: 100,
            offsetLeft: 0,
            offsetTop: 0,
            height: Height,
            width: Width,
            className: 'fixed-position',
            minHeight: minHeight,
            minWidth: minWidth,
            draggable: {restrict: true},
            overlay: true,
            "titleBar":
                {
                    "content": BX.create("SPAN", {
                        "attrs":
                            {"className": "popup-window-titlebar-text"},
                        "text": JSMessOwner.YNSIR_ADD_OTS_TITLE
                    })

                },
            closeIcon: {right: "12px", top: "12px"},
            buttons: [
                new BX.PopupWindowButton({
                    text: JSMessOwner.YNSIR_LIST_ADD_BTN,
                    className: "",
                    id: "add_feedbackOwner",
                    events: {
                        click: function () {
                            try {
                                var popup = this;
                                var error = false;
                                var error_div = $("#feedback_error");
                                error_div.hide();
                                $(".error-validate").hide();
                                var error_msg = '';
                                var btn = $('#add_feedbackOwner');
                                btn.css("pointer-events", "none");
                                var title = $("#feedback_title").val();
                                var candidate_id = $("#feedback_candidate_id").val();
                                var job_order_id = $("#feedback_job_order_id").val();
                                var round_id = $("#feedback_round_id").val();
                                var description = $("#bxed_feedback_description").val();
                                if(title == ''){
                                    error = true;
                                    $("#feedback_title").addClass('input-error');
                                    error_msg = 'Title is not specified.';
                                }
                                if(candidate_id <= 0){
                                    error = true;
                                    $("#feedback_job_order_id").addClass('input-error');
                                    error_msg = 'Candidate is not specified.';
                                }
                                if(job_order_id <= 0){
                                    error = true;
                                    $("#feedback_job_order_id").addClass('input-error');
                                    error_msg = 'Job Order is not specified.';
                                }
                                if(round_id <= 0){
                                    error = true;
                                    $("#feedback_round_id").addClass('input-error');
                                    error_msg = 'Round is not specified.';
                                }
                                if(description == ''){
                                    error = true;
                                    $("#popupAddFeedbackOwner .feed-add-post-form").addClass('input-error');
                                    error_msg = 'Description is not specified.';
                                    $("#ERROR_MSG_DESCRIPTION").html(error_msg);
                                    $("#ERROR_MSG_DESCRIPTION").show();
                                }
                                if (error) {
                                    btn.css("pointer-events", "auto");
                                }
                                else {
                                    BX.showWait();
                                    BX.ajax.submit(BX("ynsirecruitment-config-list"), function (data) {
                                        data = $.parseJSON(data);
                                        BX.closeWait();
                                        if (data.STATUS != 'SUCCESS') {
                                            //console.log(data.ERROR);
                                            var error_msg = '';
                                            for(var i in data.ERROR) {
                                                $("#ERROR_MSG_"+i).html(data.ERROR[i]['msg']);
                                                $("#ERROR_MSG_"+i).show();
                                                error_msg += data.ERROR[i]['msg']+'<br>';
                                            }

                                            error_div.html(error_msg);
                                            error_div.show();
                                            btn.css("pointer-events", "auto");
                                        }
                                        else {
                                            if (data.EDIT) {
                                                $('#div_success').text(JSMessOwner.YNSIR_EDIT_LIST_TITLE + ' ' + JSMessOwner.YNSIR_SUCCESS_MESSAGE);
                                            } else {
                                                $('#div_success').text(JSMessOwner.YNSIR_ADD_LIST_TITLE + ' ' + JSMessOwner.YNSIR_SUCCESS_MESSAGE);
                                            }
                                            actionSuccessPopupOwner.show();
                                            setTimeout(function () {
                                                actionSuccessPopupOwner.close();
                                                BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
                                            }, 2000);
                                            popup.popupWindow.close();
                                            btn.css("pointer-events", "auto");
                                        }
                                    });
                                }
                            } catch (e) {
                                console.log(e);
//                                window.location.reload();
                            }
                        }
                    }
                }),
                new BX.PopupWindowButton({
                    text: JSMessOwner.YNSIR_LIST_CANCEL_BTN,
                    className: "popup-window-button-link",
                    events: {
                        click: function () {
                            var popup = this;
                            popup.popupWindow.close();
                            var btn = $('#add_feedbackOwner');
                            btn.css("pointer-events", "auto");
                            $('#item-error-label').text("");
                        }
                    }
                })
            ]
        });
        oPopupDeleteConfirm = new BX.PopupWindow('popup_editOwner', window.body, {
            content: BX('popupdeleteconfirm'),
            autoHide: true,
            offsetTop: 1,
            overlay: false,
            offsetLeft: 0,
            lightShadow: true,
            overlay: true,
            closeByEsc: true,
            draggable: {restrict: false},
            titleBar: {content: BX.create("span", {html: JSMessOwner.YNSIR_FEEDBACK_DELETE_CONFIRM})},
            closeIcon: {right: "18px", top: "18px"},
            buttons: [
                new BX.PopupWindowButton({
                    text: "<?=GetMessage("YNSIR_FEEDBACK_DELETE_BTN")?>",
                    className: "",
                    id: "del_status_button",
                    events: {
                        click: function () {
                            var popup = this;
                            var button = $("#del_status_button");
                            button.css("pointer-events", "none");
                            try {
                                BX.showWait();
                                BX.ajax.submit(BX("ynsirecruitment-config-list-delete"), function (data) {
                                    data = $.parseJSON(data);
                                    if (data.SUCCESS) {
                                        button.css("pointer-events", "auto");
                                        BX.closeWait();
                                        popup.popupWindow.close();
                                        $('#div_success').text("<?=GetMessage('YNSIR_FEEDBACK_DELETE_SUCCESS')?>");
                                        actionSuccessPopupOwner.show();
                                        setTimeout(function () {
                                            actionSuccessPopupOwner.close();
                                            BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
                                        }, 2000);
                                    } else {
                                        BX.closeWait();
                                        $('#div_error_del').show().find("span").text("<?=GetMessage('YNSIR_FEEDBACK_ERROR')?>");
                                        button.css("pointer-events", "auto");
                                    }
                                });
                            } catch (e) {
                                $('#div_error_del').show().find("span").text("<?=GetMessage('YNSIR_FEEDBACK_ERROR')?>");
                                button.css("pointer-events", "auto");
                            }
                        }
                    }
                })
                ,
                new BX.PopupWindowButton({
                    text: "<?=GetMessage("YNSIR_CANCEL_BTN")?>",
                    className: "popup-window-button-link",
                    events: {
                        click: function () {
                            this.popupWindow.close();
                            var button = $("#del_status_button");
                            $('#div_error_del').show().find("span").text("");
                            button.css("pointer-events", "auto");
                        }
                    }
                })
            ]
        });
        actionSuccessPopupOwner = new BX.PopupWindow('popup_info', window.body, {
            content: BX('popupnonificationOwner'),
            autoHide: true,
            offsetTop: 1,
            offsetLeft: 0,
            overlay: true,
            closeByEsc: true,
            draggable: {restrict: true},
            titleBar: {content: BX.create("span", {html: "<?=GetMessage('YNSIR_KEY_NOTIFICATION')?>"})},
            closeIcon: {right: "12px", top: "12px"},
        });
        actionPermossionPopup = new BX.PopupWindow('popup_info', window.body, {
            content: BX('popupnopermissionOwner'),
            autoHide: true,
            offsetTop: 1,
            offsetLeft: 0,
            overlay: true,
            closeByEsc: true,
            draggable: {restrict: true},
            titleBar: {content: BX.create("span", {html: JSMessOwner.YNSIR_ADD_OTS_TITLE})},
            closeIcon: {right: "12px", top: "12px"},
        });

    });
</script>
