<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION, $USER;
if (!CModule::IncludeModule("ynsirecruitment")) {
    ShowError(GetMessage("MODULE_NOT_INSTALL"));
    return;
}
if (!CModule::IncludeModule('bizproc')):
    return false;
endif;

CModule::IncludeModule('blog');

$arResult['USER_ID'] = YNSIRSecurityHelper::GetCurrentUserID();
$arResult['FORMAT_DB_TIME'] = 'YYYY-MM-DD';
$arResult['FORMAT_DB_BX_FULL'] = CSite::GetDateFormat("FULL");
$arResult['FORMAT_DB_BX_SHORT'] = CSite::GetDateFormat("SHORT");
$arResult['DATE_TIME_FORMAT'] = 'f j, Y';
$arResult['GRID_ID'] = "RECRUITMENT_JOB_ORDER_LIST_V1";
$arResult['FORM_ID'] = "RECRUITMENT_JOB_ORDER_SHOW_V1";
$arResult['CAN_EDIT'] = true;
$arParams['PATH_TO_ORDER_DETAIL'] = "/recruitment/job-order/detail/#order_id#/";
$arResult['ID'] = intval($arParams['VARIABLES']['id']);
$arConfig = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_bizproc_config'));

// quick  view
$arParams['GUID'] = 'ynsir_job_order_detail_' . $arResult['ID'];
$arResult['GUID'] = isset($arParams['GUID']) ? $arParams['GUID'] : strtolower($arResult['FORM_ID']) . '_qpv';
$arResult['CONFIG'] = CUserOptions::GetOption(
    'ynsirecruitment.job_order.detail.quickpanelview',
    $arResult['GUID'],
    null,
    $USER::GetID()
);
if(!is_array($arResult['CONFIG']) || empty($arResult['CONFIG'])){
    $arResult['CONFIG'] = array('enabled' => 'N', 'expanded' => 'Y', 'fixed' => 'Y');
}
// end quick view

// submit workflow
if(isset($_POST['SUBMIT']) && $_POST['SUBMIT'] == 'Y'){
    $APPLICATION->RestartBuffer();
    $iIdJobOrder = intval($_POST['JOB_ORDER_ID']);
    $arWFResult = YNSIRBizProc::autoStart($iIdJobOrder);
    if($arWFResult['SUCCESS'] == 1)
        $arWFResult['MESS'] = GetMessage("YNSIR_CJOD_SUBMIT_WF_SUCCESS");
    echo json_encode($arWFResult);
    die;
}
// end submit workflow

/* Permission */
$userPermissions = YNSIRPerms_::GetCurrentUserPermissions();

$isPermitted = YNSIRJobOrder::CheckReadPermission($arResult['ID'], $userPermissions);
// TODO User can edit
//$arResult['CAN_EDIT'] = YNSIRJobOrder::CheckUpdatePermission($ID, $userPermissions);
//$hasBpPerms = YNSIRBpPerms::hasBPPerms(array('USER_ID'=>$userPermissions,'ELEMENT_ID'=>$arResult['ID'],'ENTITY'=>YNSIR_JOB_ORDER));
if (!$isPermitted) {
    $APPLICATION->SetTitle(GetMessage('YNSIR_CCD_TITLE'));
    ShowError(GetMessage('YNSIR_DETAIL_ACCESS_DENY'));
    return;
} else {

    //Permission Section

    $arResult['PERM']['BASIC'] = YNSIRJobOrder::CheckReadPermission($arResult['ID'], $userPermissions, YNSIRConfig::OS_BASIC_INFO);;
    $arResult['PERM']['SENSITIVE'] = YNSIRJobOrder::CheckReadPermission($arResult['ID'], $userPermissions, YNSIRConfig::OS_SENSITIVE);
    $arResult['PERM']['INTERVIEWS'] = YNSIRJobOrder::CheckReadPermission($arResult['ID'], $userPermissions, YNSIRConfig::OS_INTERVIEWS);
    $arResult['PERM']['DESCRIPTION'] = YNSIRJobOrder::CheckReadPermission($arResult['ID'], $userPermissions, YNSIRConfig::OS_DESCRIPTION);

    /* Get data job order */
    $arResult['JOB_ORDER'] = YNSIRJobOrder::getById($arResult['ID']);

    if(!empty($arResult['JOB_ORDER'])){
        $arResult['JOB_ORDER']['EXPECTED_END_DATE'] = $DB->FormatDate($arResult['JOB_ORDER']['EXPECTED_END_DATE'], $arResult['FORMAT_DB_BX_FULL'], $arResult['FORMAT_DB_BX_SHORT']);
    }

    $arInterview = YNSIRInterview::getListDetail(array(), array('ID' => $arResult['JOB_ORDER']['INTERVIEW']), false, false, array());
    $arResult['LEVEL'] = YNSIRGeneral::getListType(array('ENTITY' => YNSIRConfig::TL_WORK_POSITION), true);
    ksort($arInterview[$arResult['ID']]);
    $arResult['JOB_ORDER']['INTERVIEW'] = $arInterview[$arResult['ID']];
    $arResult['DEPARTMENT'] = YNSIRGeneral::getDepartment();
    $arResult['STATUS'] = YNSIRGeneral::getListJobStatus();
// Get data user
    $arUser = array(
        $arResult['USER_ID'],
        $arResult['JOB_ORDER']['CREATED_BY'],
        $arResult['JOB_ORDER']['MODIFIED_BY'],
        $arResult['JOB_ORDER']['SUPERVISOR'],
        $arResult['JOB_ORDER']['OWNER'],
        $arResult['JOB_ORDER']['RECRUITER']
    );
    $arUser = array_merge($arUser, $arResult['JOB_ORDER']['SUBORDINATE']);
    foreach ($arResult['JOB_ORDER']['INTERVIEW'] as $arRound) {
        $arUser = array_merge($arUser, $arRound['PARTICIPANT']);
    }
    $arResult['DATA_USER'] = YNSIRGeneral::getUserInfo($arUser);
    $arParams['GUID'] = 'ynsir_job_order_detail_' . $arResult['ID'];
    $arResult['GUID'] = isset($arParams['GUID']) ? $arParams['GUID'] : strtolower($arResult['FORM_ID']) . '_qpv';
    /*
         * LIST TAB
         */

    $arResult['IS_NEW_STATUS'] = $arResult['JOB_ORDER']['STATUS'] == JOStatus::JOSTATUS_NEW;

    $arResult['FIELDS']['tab_activity'][] = array(
        'id' => 'section_activity_grid',
        'name' => 'Job Order activities',
        'type' => 'section'
    );

    $activityBindings = array(array('TYPE_NAME' => YNSIROwnerType::CandidateName));

    $arResult['FIELDS']['tab_activity'][] = array(
        'id' => 'RECRUITMENT_CANDIDATE_ACTIVITY_GRID',
        'name' => "Activity",
        'colspan' => true,
        'type' => 'ynsir_activity_list',
        'componentData' => array(
            'template' => 'grid',
            'enableLazyLoad' => true,
            'params' => array(
                'BINDINGS' => $activityBindings,
                'PREFIX' => 'RECRUITMENT_CANDIDATE_ACTIONS_GRID',
                'PERMISSION_TYPE' => 'WRITE',
                'FORM_TYPE' => 'show',
                'FORM_ID' => $arResult['FORM_ID'],
                'TAB_ID' => 'tab_activity',
                'USE_QUICK_FILTER' => 'Y',
                'PRESERVE_HISTORY' => true,
                'ORDER_ID' => $arResult['ID'],
                'ENABLE_TOOLBAR' => 0

            )
        )
    );
//HISTORY
    $arResult['FIELDS']['tab_event'][] = array(
        'id' => 'section_event_grid',
        'name' => "Job Order log",//GetMessage('YNSIR_SECTION_EVENT_MAIN'),
        'type' => 'section'
    );

    $arResult['FIELDS']['tab_event'][] = array(
        'id' => 'DEAL_EVENT',
        'name' => "Deal Events",//GetMessage('YNSIR_FIELD_DEAL_EVENT'),
        'colspan' => true,
        'type' => 'crm_event_view',
        'componentData' => array(
            'template' => '',
            'enableLazyLoad' => true,
            'contextId' => "CANDIDATE_9_EVENT",
            'params' => array(
                'AJAX_OPTION_ADDITIONAL' => "CANDIDATE_9_EVENT",
                'ENTITY_TYPE' => 'CANDIDATE',
                'ENTITY_ID' => $ID,
                'PATH_TO_USER_PROFILE' => "/company/personal/user/#user_id#/",//$arParams['PATH_TO_USER_PROFILE'],
                'FORM_ID' => $arResult['FORM_ID'],
                'TAB_ID' => 'tab_event',
                'INTERNAL' => 'Y',
                'SHOW_INTERNAL_FILTER' => 'Y',
                'PRESERVE_HISTORY' => true,
                'NAME_TEMPLATE' => "#NAME# #LAST_NAME#",//$arParams['NAME_TEMPLATE']
            )
        )
    );
    /*
     * END LIST TAB
     */
    /*
     * Edit by nhatth2
     * Todo Add tab Add associate and list Associate with current job
     * Date 18-09-2017
     */
    $ID = $arResult['ID'];
    $sFormatName = CSite::GetNameFormat(false);

    $arResult['FIELDS']['tab_order_associate_list'][] = array(
        'id' => 'ORDER_CANDIDATE_ASSOCIATE',
        'name' => "ORDER_CANDIDATE_ASSOCIATE",//GetMessage('YNSIR_FIELD_DEAL_EVENT'),
        'colspan' => true,
        'type' => 'ynsir_order_associate_list',
        'componentData' => array(
            'template' => '',
            'enableLazyLoad' => true,
            'contextId' => "CANDIDATE_ASSOCIATE_" . $ID . "_LIST",
            'params' => array(
                'AJAX_OPTION_ADDITIONAL' => "CANDIDATE_ASSOCIATE_" . $ID . "_LIST",
                'ENTITY_TYPE' => YNSIR_JOB_ORDER,
                'ENTITY_ID' => $ID,
                'PATH_TO_USER_PROFILE' => "/company/personal/user/#user_id#/",
                'PATH_TO_CANDIDATE_DETAIL' => "/recruitment/candidate/detail/#candidate_id#/",
                'PATH_TO_ORDER_DETAIL' => "/recruitment/order/detail/#order_id#/",
                'PATH_TO_ASSOCIATE_LIST' => "/bitrix/components/ynsirecruitment/ynsir.associate.list/lazyload.ajax.php",
                'FORM_ID' => $arResult['FORM_ID'],
                'TAB_ID' => 'tab_order_associate_list',
                'INTERNAL' => 'Y',
                'SHOW_INTERNAL_FILTER' => 'Y',
                'PRESERVE_HISTORY' => true,
                'IS_INTERNAL_ASSOCIATE' => 'Y',
                'INTERNAL_FILTER' => array('ORDER_JOB_ID' => $ID),
                'GRID_ID_SUFFIX' => 'IN_JOB_TAB',
                'NAME_TEMPLATE' => $sFormatName,//$arParams['NAME_TEMPLATE']
            )
        )
    );

    $arResult['FIELDS']['tab_event'][] = array(
        'id' => 'ORDER__EVENT',
        'name' => "Order Tracking",//GetMessage('YNSIR_FIELD_DEAL_EVENT'),
        'colspan' => true,
        'type' => 'ynsir_event_view',
        'componentData' => array(
            'template' => '',
            'enableLazyLoad' => true,
            'contextId' => "ORDER_" . $ID . "_EVENT",
            'params' => array(
                'AJAX_OPTION_ADDITIONAL' => "ORDER_" . $ID . "_EVENT",
                'ENTITY_TYPE' => YNSIR_JOB_ORDER,
                'ENTITY_ID' => $ID,
                'PATH_TO_USER_PROFILE' => "/company/personal/user/#user_id#/",//$arParams['PATH_TO_USER_PROFILE'],
                'FORM_ID' => $arResult['FORM_ID'],
                'TAB_ID' => 'tab_event',
                'INTERNAL' => 'Y',
                'SHOW_INTERNAL_FILTER' => 'Y',
                'PRESERVE_HISTORY' => true,
                'NAME_TEMPLATE' => $sFormatName,
            )
        )
    );
    $arResult['FIELDS']['tab_feedback'][] = array(
        'id' => 'CANDIDATE_EVENT',
        'name' => "Feedback",//GetMessage('YNSIR_FIELD_DEAL_EVENT'),
        'colspan' => true,
        'type' => 'feedback_list',
        'componentData' => array(
            'template' => '',
            'enableLazyLoad' => true,
            'contextId' => "CANDIDATE_".$ID."_FEEDBACK",
            'params' => array(
                'AJAX_OPTION_ADDITIONAL' => "CANDIDATE_".$ID."_FEEDBACK",
                'ENTITY_TYPE' => YNSIR_CANDIDATE,
                'JOB_ORDER_OWNER_IS' => $ID,
                'FORM_ID' => $arResult['FORM_ID'],
                'TAB_ID' => 'tab_feedback',
                'INTERNAL' => 'Y',
                'SHOW_INTERNAL_FILTER' => 'Y',
                'PRESERVE_HISTORY' => true,
                'NAME_TEMPLATE' => $sFormatName,
            )
        )
    );
    /*
     * HISTORY VIEW Order
     */
    YNSIREvent::RegisterViewEvent(YNSIROwnerType::Order, $ID, $arResult['USER_ID']);

    /*
     * END VIEW Order
     */
    if (IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
    {
        $arResult['FIELDS']['tab_bizproc'][] = array(
            'id' => 'section_bizproc',
            'name' => GetMessage('YNSIR_SECTION_BIZPROC_MAIN'),
            'type' => 'section'
        );

        $arResult['BIZPROC'] = 'Y';

        $formTabKey = $arResult['FORM_ID'].'_active_tab';
        $activeTab = isset($_REQUEST[$formTabKey]) ? $_REQUEST[$formTabKey] : '';
        $bizprocTask = isset($_REQUEST['bizproc_task']) ? $_REQUEST['bizproc_task'] : '';
        $bizprocIndex = isset($_REQUEST['bizproc_index']) ? intval($_REQUEST['bizproc_index']) : 0;
        $bizprocAction = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

        if ($bizprocTask !== '')
        {
            ob_start();
            $APPLICATION->IncludeComponent(
                'bitrix:bizproc.task',
                '',
                Array(
                    'TASK_ID' => (int)$_REQUEST['bizproc_task'],
                    'USER_ID' => $currentUserID,
                    'WORKFLOW_ID' => '',
                    'DOCUMENT_URL' =>  CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ORDER_DETAIL'],
                        array('order_id' => $arResult['ID'])
                    ),
                    'SET_TITLE' => 'Y',
                    'SET_NAV_CHAIN' => 'Y'
                ),
                '',
                array('HIDE_ICONS' => 'Y')
            );
            $sVal = ob_get_contents();
            ob_end_clean();
            $arResult['FIELDS']['tab_bizproc'][] = array(
                'id' => 'JOB_ORDER_BIZPROC',
                'name' => GetMessage('YNSIR_FIELD_JOB_ORDER_BIZPROC'),
                'colspan' => true,
                'type' => 'custom',
                'value' => $sVal
            );
        }
        elseif (isset($_REQUEST['bizproc_log']) && strlen($_REQUEST['bizproc_log']) > 0)
        {
            ob_start();
            ?>
            <div class="pagetitle-container pagetitle-align-right-container" style="float: right ;padding: 20px;">
                <a href="/recruitment/job-order/detail/<?=$arResult['ID']?>/?RECRUITMENT_JOB_ORDER_SHOW_V1_active_tab=tab_bizproc"  class="ynsir-candidate-detail-back">
                    <?= GetMessage('YNSIR_JOD_T_BTN_BACK') ?>
                </a>
            </div>
            <?
            $APPLICATION->IncludeComponent('bitrix:bizproc.log',
                '',
                Array(
                    'MODULE_ID' => 'ynsirecruitment',
                    'ENTITY' => 'YNSIRDocumentJobOrder',
                    'DOCUMENT_TYPE' => YNSIR_JOB_ORDER,
                    'COMPONENT_VERSION' => 2,
                    'DOCUMENT_ID' => 'LEAD_'.$arResult['ID'],
                    'ID' => $_REQUEST['bizproc_log'],
                    'SET_TITLE'	=>	'Y',
                    'INLINE_MODE' => 'Y',
                    'AJAX_MODE' => 'N'
                ),
                '',
                array("HIDE_ICONS" => "Y")
            );
            $sVal = ob_get_contents();
            ob_end_clean();
            $arResult['FIELDS']['tab_bizproc'][] = array(
                'id' => 'JOB_ORDER_BIZPROC',
                'name' => GetMessage('YNSIR_FIELD_JOB_ORDER_BIZPROC'),
                'colspan' => true,
                'type' => 'custom',
                'value' => $sVal
            );
        } elseif (isset($_REQUEST['bizproc_start'])
            && isset($_REQUEST['bizproc_start_popup']) && strlen($_REQUEST['bizproc_start_popup']) > 0
            && strlen($_REQUEST['bizproc_start']) > 0)
        {
            $this->IncludeComponentTemplate('biz_process');
        }
        elseif (isset($_REQUEST['bizproc_start']) && strlen($_REQUEST['bizproc_start']) > 0)
        {
            if($_REQUEST['workflow_template_id'] == $arConfig['YNSIR_BIZ_APPROVE_ORDER_ID']) {
                $arChangeStatus = YNSIRJobOrder::changeStatusWaiting($arResult['ID']);
            }
                ob_start();
                $APPLICATION->IncludeComponent('bitrix:bizproc.workflow.start',
                    '',
                    Array(
                        'MODULE_ID' => 'ynsirecruitment',
                        'ENTITY' => 'YNSIRDocumentJobOrder',
                        'DOCUMENT_TYPE' => YNSIR_JOB_ORDER,
                        'DOCUMENT_ID' => YNSIR_JOB_ORDER . '_' . $arResult['ID'],
                        'TEMPLATE_ID' => $_REQUEST['workflow_template_id'],
                        'SET_TITLE' => 'Y'
                    ),
                    '',
                    array('HIDE_ICONS' => 'Y')
                );
                $sVal = ob_get_contents();
                ob_end_clean();
                $arResult['FIELDS']['tab_bizproc'][] = array(
                    'id' => 'JOB_ORDER_BIZPROC',
                    'name' => GetMessage('YNSIR_FIELD_JOB_ORDER_BIZPROC'),
                    'colspan' => true,
                    'type' => 'custom',
                    'value' => $sVal
                );

        }
        else
        {
            if(!($activeTab === 'tab_bizproc' || $bizprocIndex > 0 || $bizprocAction !== ''))
            {
                $bizprocContainerID = $arResult['BIZPROC_CONTAINER_ID'] = $arResult['FORM_ID'].'_bp_wrapper';
                $arResult['ENABLE_BIZPROC_LAZY_LOADING'] = true;
                $arResult['POST_FORM_URI'] = CHTTP::urlAddParams(POST_FORM_ACTION_URI, array($formTabKey => 'tab_bizproc'));

                $arResult['FIELDS']['tab_bizproc'][] = array(
                    'id' => 'JOB_ORDER_BIZPROC',
                    'name' => 'JOB_ORDER_BIZPROC'.GetMessage('YNSIR_FIELD_JOB_ORDER_BIZPROC'),
                    'colspan' => true,
                    'type' => 'custom',
                    'value' => '<div id="'.htmlspecialcharsbx($bizprocContainerID).'"></div>'
                );
            }
            else
            {
                ob_start();
                $APPLICATION->IncludeComponent('bitrix:bizproc.document',
                    '',
                    Array(
                        'MODULE_ID' => 'ynsirecruitment',
                        'ENTITY' => 'YNSIRDocumentJobOrder',
                        'DOCUMENT_TYPE' => YNSIR_JOB_ORDER,
                        'DOCUMENT_ID' => YNSIR_JOB_ORDER.'_'.$arResult['ID'],
                        'TASK_EDIT_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ORDER_DETAIL'],
                            array(
                                'order_id' => $arResult['ID']
                            )),
                            array('bizproc_task' => '#ID#', $formTabKey => 'tab_bizproc')
                        ),
                        'WORKFLOW_LOG_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ORDER_DETAIL'],
                            array(
                                'order_id' => $arResult['ID']
                            )),
                            array('bizproc_log' => '#ID#', $formTabKey => 'tab_bizproc')
                        ),
                        'WORKFLOW_START_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ORDER_DETAIL'],
                            array(
                                'order_id' => $arResult['ID']
                            )),
                            array('bizproc_start' => 1, $formTabKey => 'tab_bizproc')
                        ),
                        'back_url' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ORDER_DETAIL'],
                            array(
                                'order_id' => $arResult['ID']
                            )),
                            array($formTabKey => 'tab_bizproc')
                        ),
                        'SET_TITLE'	=>	'Y'
                    ),
                    '',
                    array('HIDE_ICONS' => 'Y')
                );
                $sVal = ob_get_contents();
                ob_end_clean();
                $arResult['FIELDS']['tab_bizproc'][] = array(
                    'id' => 'JOB_ORDER_BIZPROC',
                    'name' => GetMessage('YNSIR_FIELD_JOB_ORDER_BIZPROC'),
                    'colspan' => true,
                    'type' => 'custom',
                    'value' => $sVal
                );
            }
        }
    }

    if($arResult['JOB_ORDER']['SALARY_FROM'] > 0){
        $arResult['JOB_ORDER']['SALARY_FROM'] = number_format($arResult['JOB_ORDER']['SALARY_FROM'], 0, '.', ' ');
    }
    if($arResult['JOB_ORDER']['SALARY_TO'] > 0){
        $arResult['JOB_ORDER']['SALARY_TO'] = number_format($arResult['JOB_ORDER']['SALARY_TO'], 0, '.', ' ');
    }

    //Work flow get For buttom Automation
    $dbWorkflowTemplate = CBPWorkflowTemplateLoader::GetList(
        array(),
        array(
            "DOCUMENT_TYPE" => array(
                'ynsirecruitment',
                'YNSIRDocumentJobOrder',
                YNSIR_JOB_ORDER),
            "ACTIVE" => "Y",
            '!AUTO_EXECUTE' => CBPDocumentEventType::Automation
        ),
        false,
        false,
        array("ID", "NAME", "DESCRIPTION", "MODIFIED", "USER_ID", "PARAMETERS")
    );
    $tabActiveStr = $arResult['FORM_ID'].'_active_tab=tab_bizproc';

    $URL_DETAIL = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ORDER_DETAIL'], array('order_id' => $arResult['ID']));
    $back_url = 'back_url='.$URL_DETAIL.'?'.$arResult['FORM_ID'].'_active_tab=tab_bizproc';

    while ($arWorkflowTemplate = $dbWorkflowTemplate->GetNext())
    {
        if($arWorkflowTemplate["ID"] == $arConfig['YNSIR_BIZ_APPROVE_ORDER_ID']) {
            $arResult['BIZ']['APPROVE']["TEMPLATES"] = $arWorkflowTemplate;
            $arResult['BIZ']['APPROVE']["TEMPLATES"]["URL"] =
                htmlspecialcharsex($APPLICATION->GetCurPageParam(
                    "workflow_template_id=" . $arWorkflowTemplate["ID"] . '&bizproc_start=1&' . bitrix_sessid_get().'&'.$tabActiveStr.'&'.$back_url,
                    Array("workflow_template_id", "sessid","bizproc_start","back_url",$arResult['FORM_ID'].'_active_tab')));
        }
        if($arWorkflowTemplate["ID"] == $arConfig['BIZPROC_BIZ_SCAN_CV_ID']) {
            $arResult['BIZ']['SCAN']["TEMPLATES"] = $arWorkflowTemplate;
            $arResult['BIZ']['SCAN']["TEMPLATES"]["URL"] =
                htmlspecialcharsex($APPLICATION->GetCurPageParam(
                    "workflow_template_id=" . $arWorkflowTemplate["ID"] . '&bizproc_start=1&' . bitrix_sessid_get().'&'.$tabActiveStr.'&'.$back_url,
                    Array("workflow_template_id", "sessid","bizproc_start",$arResult['FORM_ID'].'_active_tab','back_url')));
        }
    }
    //End
    $this->IncludeComponentTemplate();
}
?>