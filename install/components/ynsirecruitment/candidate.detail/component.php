<?php
use Bitrix\Disk\File;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION, $USER;
if (!CModule::IncludeModule("ynsirecruitment")) {
    ShowError(GetMessage("MODULE_NOT_INSTALL"));
    return;
}

$arResult['FORMAT_DB_TIME'] = 'YYYY-MM-DD';
$arResult['FORMAT_DB_BX_FULL'] = CSite::GetDateFormat("FULL");
$arResult['FORMAT_DB_BX_SHORT'] = CSite::GetDateFormat("SHORT");
$arResult['DATE_TIME_FORMAT'] = 'f j, Y';
if ($_GET['QUIK_VIEW'] == 'Y') {
    $APPLICATION->IncludeComponent(
        "ynsirecruitment:candidate.iframe.popup",
        "wrap",
        array(
            "ACTION" => 'edit',
            "CANDIDATE_ID" => $arParams['VARIABLES']['id']
        )
    );
    die;
}

$currentUserID = YNSIRSecurityHelper::GetCurrentUserID();
$ID = $arParams['VARIABLES']['id'];
if($ID > 0 && $_GET['job_order_id'] > 0){
  $_GET['candidate_id'] = $ID;
}
$arResult['FIELD_CANDIDATE'] = YNSIRConfig::getFieldsCandidate();
$arResult['FIELD_CANDIDATE_VIEW'] = YNSIRConfig::getFieldsIntabViewCandidate();


$arResult['FORMAT_DB_BX_SHORT'] = CSite::GetDateFormat("SHORT");
$arResult['FORMAT_DB_BX_FULL'] = CSite::GetDateFormat("FULL");
$arResult['GRID_ID'] = "RECRUITMENT_CANDIDATE_LIST_V1";
$arResult['FORM_ID'] = "RECRUITMENT_CANDIDATE_SHOW_V1";
$arResult['GRID_ID_MANAGER'] = "RECRUITMENT_CANDIDATE_LIST_V1_MANAGER";
$arResult['STATUS_LOCK'] = YNSIRConfig::getCandiateStatusLock();

$sFormatName = CSite::GetNameFormat(false);
/*
 * get permission
 */
$userPermissions = YNSIRPerms_::GetCurrentUserPermissions();

$isPermitted                                    = YNSIRCandidate::CheckReadPermission($ID, $userPermissions);
$isPerm[YNSIRConfig::CS_BASIC_INFO]             = YNSIRCandidate::CheckReadPermission($ID,$userPermissions,YNSIRConfig::CS_BASIC_INFO);
$isPerm[YNSIRConfig::CS_ADDRESS_INFORMATION]    = YNSIRCandidate::CheckReadPermission($ID,$userPermissions,YNSIRConfig::CS_ADDRESS_INFORMATION);
$isPerm[YNSIRConfig::CS_PROFESSIONAL_DETAILS]   = YNSIRCandidate::CheckReadPermission($ID,$userPermissions,YNSIRConfig::CS_PROFESSIONAL_DETAILS);
$isPerm[YNSIRConfig::CS_OTHER_INFO]             = YNSIRCandidate::CheckReadPermission($ID,$userPermissions,YNSIRConfig::CS_OTHER_INFO);
$isPerm[YNSIRConfig::CS_ATTACHMENT_INFORMATION] = YNSIRCandidate::CheckReadPermission($ID,$userPermissions,YNSIRConfig::CS_ATTACHMENT_INFORMATION);
// TODO User can edit
$arResult['CAN_EDIT']                           = YNSIRCandidate::CheckUpdatePermission($ID, $userPermissions);

if (!$isPermitted) {
    ShowError(GetMessage('CRM_PERMISSION_DENIED'));
    return;
} else {
    /*
     * Remove section
     */
    foreach($arResult['FIELD_CANDIDATE_VIEW'] as $KEY_SECTION => $arSection) {
        if(!$isPerm[$KEY_SECTION]) {
            unset($arResult['FIELD_CANDIDATE_VIEW'][$KEY_SECTION]);
        }
    }
    unset($KEY_SECTION);
    unset($arSection);
    /*
     * End remove section
     */
    $arSelect = array('ACTIVITY_ID');
    if (in_array('ACTIVITY_ID', $arSelect, true)) {
        $arSelect[] = 'ACTIVITY_TIME';
        $arSelect[] = 'ACTIVITY_SUBJECT';
        $arSelect[] = 'C_ACTIVITY_ID';
        $arSelect[] = 'C_ACTIVITY_TIME';
        $arSelect[] = 'C_ACTIVITY_SUBJECT';
        $arSelect[] = 'C_ACTIVITY_RESP_ID';
        $arSelect[] = 'C_ACTIVITY_RESP_LOGIN';
        $arSelect[] = 'C_ACTIVITY_RESP_NAME';
        $arSelect[] = 'C_ACTIVITY_RESP_LAST_NAME';
        $arSelect[] = 'C_ACTIVITY_RESP_SECOND_NAME';
    }

    if (in_array('ACTIVITY_ID', $arSelect, true)) {
        $arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
    }

    //TODO GET CACHE LIST

    $arResult['CONFIG'] = YNSIRConfig::GetListTypeList();
    $arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS] = YNSIRGeneral::getListJobStatus('CANDIDATE_STATUS');

    foreach ($arResult['CONFIG'] as $KEY_C => $CONFIGLIGT) {
        $arProfile[$KEY_C] = !empty($arProfile[$KEY_C]) ? $CONFIGLIGT[$arProfile[$KEY_C]]['NAME_' . strtoupper(LANGUAGE_ID)] : '';
    }
    //END TODO GET CACHE LIST

    //TODO: BEGIN GET CANDIDATE BY ID
    $resCache = YNSIRCacheHelper::GetCached(YNSIRCandidate::ACTIVITY_CACHE_TIME, YNSIRCandidate::FREFIX_CACHE . $ID, YNSIRCandidate::ACTIVITY_CACHE_URL);
    if (is_array($resCache) && !empty($resCache['DATA'] && false)) { // not use cache
        $arResult['CANDIDATE']  =  $resCache['DATA'];
    } else {
        //SAVE CACHE
        $dbResult = YNSIRCandidate::GetListCandidate(
            array(),
            array('ID' => $ID),
            array(),
            $arOptions,
            $arSelect
        );
        $arResult['CANDIDATE'] = array();
        if ($arProfile = $dbResult->GetNext()) {
            $arProfile['MODIFIED_DATE_SHORT'] = FormatDate('x', MakeTimeStamp($arProfile['MODIFIED_DATE']), (time() + CTimeZone::GetOffset()));
            $arResult['CANDIDATE'] = $arProfile;
        } else {
                ShowError(GetMessage("YNSIR_DETAIL_ELEMENT_NOT_FOUND"));
                return;
        }
        $arResult['CANDIDATE']['CANDIDATE_STATUS'] = $arResult['CONFIG']['CANDIDATE_STATUS'][$arResult['CANDIDATE']['CANDIDATE_STATUS']];

        //Update by nhatth2
        //TODO: View status if current candiate associate with Job order
        //get List Candidate have aready added to current JobOrder
        $arCandidateRes = YNSIRAssociateJob::checkCandiateLock($ID);
        
        if(!empty($arCandidateRes)) {
            $arParams['PATH_TO_ORDER_DETAIL'] = ' /recruitment/job-order/detail/#job_order_id#/';
            $HtmlOrder = '';
            if(intval($arCandidateRes['ORDER_JOB_ID']) > 0) {
                $orderLink = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ORDER_DETAIL'], array('job_order_id' => $arCandidateRes['ORDER_JOB_ID']));
                $HtmlOrder = '<span>&rarr;</span><a href="' . $orderLink . '" title="' . $arCandidateRes['ORDER_JOB_TITLE'] . '">' . $arCandidateRes['ORDER_JOB_TITLE'] . '</a>';
            }
            $classLock = '';
            if($arCandidateRes['IS_LOCK'] == 'Y') {
                $classLock = 'ynsir-associate-candidate-lock-detail';
            }
            $arResult['CANDIDATE']['CANDIDATE_STATUS'] = '<span class="'.$classLock.'">'.$arResult['CONFIG'][YNSIRConfig::TL_CANDIDATE_STATUS][$arCandidateRes['STATUS_ID']] .  $HtmlOrder.'</span>';
        }

        $arResult['CANDIDATE']['NAME'] = CUser::FormatName(
            $sFormatName,
            array(
                "NAME" => $arResult['CANDIDATE']['FIRST_NAME'],
                "LAST_NAME" => $arResult['CANDIDATE']['LAST_NAME'],
            )
        );
        $arResult['CANDIDATE']['FULL_NAME'] = $arResult['CANDIDATE']['NAME'];
        $arResult['CANDIDATE']['NAME'] = $arResult['CANDIDATE']['SALT_NAME'] . $arResult['CANDIDATE']['NAME'];

        $arOWNERTOOLTIP = YNSIRHelper::getTooltipandPhotoUser($arResult['CANDIDATE']['CANDIDATE_OWNER'], 'CANDIDATE_OWNER');;
        $arOWNERTOOLTIP_v = YNSIRHelper::getTooltipandPhotoUser($arResult['CANDIDATE']['CANDIDATE_OWNER'], 'CANDIDATE_OWNER_v');;
        $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER_ID'] = $arResult['CANDIDATE']['CANDIDATE_OWNER'];
        $arResult['CANDIDATE']['CANDIDATE_OWNER'] = $arOWNERTOOLTIP['TOOLTIP'];
        $arResult['CANDIDATE']['CANDIDATE_OWNER_v'] = $arOWNERTOOLTIP_v['TOOLTIP'];
        $arResult['CANDIDATE']['CANDIDATE_OWNER_PHOTO_URL'] = $arOWNERTOOLTIP['PHOTO_URL'];

        $arCREATBYTOOLTIP = YNSIRHelper::getTooltipandPhotoUser($arResult['CANDIDATE']['CREATED_BY'], 'CREATED_BY');
        $arResult['CANDIDATE']['CREATED_BY'] = $arCREATBYTOOLTIP['TOOLTIP'];
        $arResult['CANDIDATE']['CREATED_BY_PHOTO_URL'] = $arCREATBYTOOLTIP['PHOTO_URL'];



        $arMODIFIBYTOOLTIP = YNSIRHelper::getTooltipandPhotoUser($arResult['CANDIDATE']['MODIFIED_BY'], 'MODIFIED_BY');
        $arResult['CANDIDATE']['MODIFIED_BY'] = $arMODIFIBYTOOLTIP['TOOLTIP'];
        $arResult['CANDIDATE']['MODIFIED_BY_PHOTO_URL'] = $arMODIFIBYTOOLTIP['PHOTO_URL'];

        //FILE
        $arResult['CANDIDATE']['FILE_UPLOAD'] = YNSIRFile::getListById($arResult['CANDIDATE']['ID']);

        //TODO: SAVE CACHE
        YNSIRCacheHelper::SetCached($arResult['CANDIDATE'], YNSIRCandidate::ACTIVITY_CACHE_TIME, YNSIRCandidate::FREFIX_CACHE . $ID, YNSIRCandidate::ACTIVITY_CACHE_URL);
    }
    //END SAVE CACHE

    //nearest ACTIVITY
    $now = time() + CTimeZone::GetOffset();
    if (isset($arResult['CANDIDATE']['~ACTIVITY_TIME'])) {
        $time = MakeTimeStamp($arResult['CANDIDATE']['~ACTIVITY_TIME']);
        $arResult['CANDIDATE']['~ACTIVITY_EXPIRED'] = $time <= $now;
        $arResult['CANDIDATE']['~ACTIVITY_IS_CURRENT_DAY'] = $arProfile['~ACTIVITY_EXPIRED'] || YNSIRActivity::IsCurrentDay($time);
    }

    $userActivityID = isset($arResult['CANDIDATE']['~ACTIVITY_ID']) ? intval($arResult['CANDIDATE']['~ACTIVITY_ID']) : 0;
    $commonActivityID = isset($arResult['CANDIDATE']['~C_ACTIVITY_ID']) ? intval($arResult['CANDIDATE']['~C_ACTIVITY_ID']) : 0;
    $gridManagerID = $arResult['GRID_ID_MANAGER'];
    if ($userActivityID > 0) {
        $resultItem['columns']['ACTIVITY_ID'] = CYNSIRViewHelper::RenderNearestActivity(
            array(
                'ENTITY_TYPE_NAME' => YNSIROwnerType::ResolveName(YNSIROwnerType::Candidate),
                'ENTITY_ID' => $arResult['CANDIDATE']['~ID'],
                'ENTITY_RESPONSIBLE_ID' => $arResult['CANDIDATE']['~ASSIGNED_BY'],
                'GRID_MANAGER_ID' => $gridManagerID,
                'ACTIVITY_ID' => $userActivityID,
                'ACTIVITY_SUBJECT' => isset($arResult['CANDIDATE']['~ACTIVITY_SUBJECT']) ? $arResult['CANDIDATE']['~ACTIVITY_SUBJECT'] : '',
                'ACTIVITY_TIME' => isset($arResult['CANDIDATE']['~ACTIVITY_TIME']) ? $arResult['CANDIDATE']['~ACTIVITY_TIME'] : '',
                'ACTIVITY_EXPIRED' => isset($arResult['CANDIDATE']['~ACTIVITY_EXPIRED']) ? $arResult['CANDIDATE']['~ACTIVITY_EXPIRED'] : '', //get for set Class in HTML
                'ALLOW_EDIT' => true,
                'MENU_ITEMS' => $arActivityMenuItems,
                'USE_GRID_EXTENSION' => true
            )
        );

        $counterData = array(
            'CURRENT_USER_ID' => $currentUserID,
            'ENTITY' => $arResult['CANDIDATE'],
            'ACTIVITY' => array(
                'RESPONSIBLE_ID' => $currentUserID,
                'TIME' => isset($arResult['CANDIDATE']['~ACTIVITY_TIME']) ? $arResult['CANDIDATE']['~ACTIVITY_TIME'] : '',
                'IS_CURRENT_DAY' => isset($arResult['CANDIDATE']['~ACTIVITY_IS_CURRENT_DAY']) ? $arResult['CANDIDATE']['~ACTIVITY_IS_CURRENT_DAY'] : false
            )
        );

        if (CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentDealActivies, $counterData)) {
            $resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-deal-today');
        }
    } elseif ($commonActivityID > 0) {
        $resultItem['columns']['ACTIVITY_ID'] = CYNSIRViewHelper::RenderNearestActivity(
            array(
                'ENTITY_TYPE_NAME' => YNSIROwnerType::ResolveName(YNSIROwnerType::Candidate),
                'ENTITY_ID' => $arResult['CANDIDATE']['~ID'],
                'ENTITY_RESPONSIBLE_ID' => $arResult['CANDIDATE']['~ASSIGNED_BY'],
                'GRID_MANAGER_ID' => $gridManagerID,
                'ACTIVITY_ID' => $commonActivityID,
                'ACTIVITY_SUBJECT' => isset($arResult['CANDIDATE']['~C_ACTIVITY_SUBJECT']) ? $arResult['CANDIDATE']['~C_ACTIVITY_SUBJECT'] : '',
                'ACTIVITY_TIME' => isset($arResult['CANDIDATE']['~C_ACTIVITY_TIME']) ? $arResult['CANDIDATE']['~C_ACTIVITY_TIME'] : '',
                'ACTIVITY_RESPONSIBLE_ID' => isset($arResult['CANDIDATE']['~C_ACTIVITY_RESP_ID']) ? intval($arResult['CANDIDATE']['~C_ACTIVITY_RESP_ID']) : 0,
                'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arResult['CANDIDATE']['~C_ACTIVITY_RESP_LOGIN']) ? $arResult['CANDIDATE']['~C_ACTIVITY_RESP_LOGIN'] : '',
                'ACTIVITY_RESPONSIBLE_NAME' => isset($arResult['CANDIDATE']['~C_ACTIVITY_RESP_NAME']) ? $arResult['CANDIDATE']['~C_ACTIVITY_RESP_NAME'] : '',
                'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arResult['CANDIDATE']['~C_ACTIVITY_RESP_LAST_NAME']) ? $arResult['CANDIDATE']['~C_ACTIVITY_RESP_LAST_NAME'] : '',
                'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arResult['CANDIDATE']['~C_ACTIVITY_RESP_SECOND_NAME']) ? $arResult['CANDIDATE']['~C_ACTIVITY_RESP_SECOND_NAME'] : '',
                'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
                'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
                'ALLOW_EDIT' => true,
                'MENU_ITEMS' => $arActivityMenuItems,
                'USE_GRID_EXTENSION' => true
            )
        );
    } else {
        $resultItem['columns']['ACTIVITY_ID'] = CYNSIRViewHelper::RenderNearestActivity(
            array(
                'ENTITY_TYPE_NAME' => YNSIROwnerType::ResolveName(YNSIROwnerType::Candidate),
                'ENTITY_ID' => $arResult['CANDIDATE']['~ID'],
                'ENTITY_RESPONSIBLE_ID' => $arProfile['~ASSIGNED_BY'],
                'GRID_MANAGER_ID' => $gridManagerID,
                'ALLOW_EDIT' => true,
                'MENU_ITEMS' => $arActivityMenuItems,
                'USE_GRID_EXTENSION' => true
            )
        );

        $counterData = array(
            'CURRENT_USER_ID' => $currentUserID,
            'ENTITY' => $arResult['CANDIDATE']
        );

        if (CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentDealActivies, $counterData)) {
            $resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-enitity-action-need');
        }
    }
    $arResult['CANDIDATE']['ACTIVITY'] = $resultItem;
    unset($resultItem);
    //end nearest ACTIVITY

    $gender = YNSIRConfig::getListConfig('GENDER');
    $arResult['CANDIDATE']['GENDER'] = $arResult['CANDIDATE']['GENDER'] ? $gender[$arResult['CANDIDATE']['GENDER']] : '';

    $arResult['CANDIDATE']['EMAIL_OPT_OUT'] = $arResult['CANDIDATE']['EMAIL_OPT_OUT'] == 1 ? 'Yes' : 'No';

    //TODO: GET FIELD MULTIPLE
    $dbMultiField = YNSIRCandidate::GetListMultiField(array(), array('CANDIDATE_ID' => $arResult['CANDIDATE']['ID']));
    while ($multiField = $dbMultiField->GetNext()) {
        switch ($multiField['TYPE']) {
//                case 'APPLY_PO    SITION':
            case 'CURRENT_JOB_TITLE':
                $arResult['CANDIDATE'][$multiField['TYPE']] .= '<span class="recruitment-candidate-info-label-alignment"></span><span class="crm-client-contacts-block-text" style="max-width: 300px;">' . $multiField['CONTENT'] . '
                                        </span><br>';
                break;
            case 'EMAIL':
                $m = '<a href="mailto:' . $multiField['CONTENT'] . '">' . htmlspecialcharsbx($multiField['CONTENT']) . '</a>';

                $arResult['CANDIDATE'][$multiField['TYPE']] .= '<span class="recruitment-candidate-info-label-alignment"></span><span class="crm-client-contacts-block-text" style="max-width: 300px;">' . $m . '
                                        </span><br>';
                break;
            case 'CMOBILE':
            case 'PHONE':
            $arResult['CANDIDATE'][$multiField['TYPE']] .= "<a class='crm-client-contacts-block-text-tel'
                                                title='" . $multiField['CONTENT'] . "'
                                                href='callto://" . $multiField['CONTENT'] . "'
                                                onclick='if(typeof(top.BXIM) !== 'undefined') { top.BXIM.phoneTo('" . $multiField['CONTENT'] . "', {&quot;ENTITY_TYPE&quot;:&quot;YNSIR_LEAD&quot;,&quot;ENTITY_ID&quot;:4}); return BX.PreventDefault(event); }'>" . $multiField['CONTENT'] . "</a><br>";
                break;
            default:
                $lable = $arResult['CONFIG'][$multiField['TYPE']][$multiField['CONTENT']]['ADDITIONAL_INFO_LABEL_EN'];
                $content = $arResult['CONFIG'][$multiField['TYPE']][$multiField['CONTENT']]['NAME_' . strtoupper(LANGUAGE_ID)];
                if ($multiField['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                    $multiField['ADDITIONAL_VALUE'] = $DB->FormatDate($multiField['ADDITIONAL_VALUE'], $arResult['FORMAT_DB_TIME'], $arResult['FORMAT_DB_BX_FULL']);
                    $multiField['ADDITIONAL_VALUE'] = FormatDateEx($multiField['ADDITIONAL_VALUE'], $arResult['FORMAT_DB_BX_FULL'], $arResult['DATE_TIME_FORMAT']);
                }
                if ($multiField['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_USER) {

                    $arOWNERTOOLTIP = YNSIRHelper::getTooltipandPhotoUser($multiField['ADDITIONAL_VALUE'], 'M'.$multiField['ID']);;
                    $arResult['CANDIDATE_DATA']['CANDIDATE_OWNER_ID'] = $arResult['CANDIDATE']['CANDIDATE_OWNER'];
                    $photo = '<div class = "crm-client-photo-wrapper">
                                            <div class="crm-client-user-def-pic">
                                                <img alt="Author Photo" src="'.$arOWNERTOOLTIP['PHOTO_URL'].'"/>
                                            </div>
                                        </div>';
                    $multiField['ADDITIONAL_VALUE'] = $arOWNERTOOLTIP['TOOLTIP'];

                }
                $lable = strlen($lable)>0?$lable.': ':'';
                $additional_value = $multiField['ADDITIONAL_VALUE'] != '' ? ' (' . $lable  . $multiField['ADDITIONAL_VALUE'] . ')' : '';
                $arResult['CANDIDATE'][$multiField['TYPE']] .= $content . $additional_value . '<br>';

        }
    }
    //End TODO: GET FIELD MULTIPLE
//get attact file

    // id storage
    $idStorage = 0;
    // end

    if(isset($arResult['FIELD_CANDIDATE_VIEW'][YNSIRConfig::CS_ATTACHMENT_INFORMATION])) {
//deb($arFile);
        foreach ($arResult['FIELD_CANDIDATE_VIEW'][YNSIRConfig::CS_ATTACHMENT_INFORMATION]['FIELDS'] as $ATTACHT_FIELD_NAME) {
            foreach ($arResult['CANDIDATE']['FILE_UPLOAD'][$ATTACHT_FIELD_NAME['KEY']] as $idFile => $fitem) {
                $file = File::loadById($idFile, array('STORAGE'));
                if(!$file) continue;
//                $arDetailIFile = CFile::GetByID($file->getFileId())->Fetch();

                // id storage
                $iIdStorageTmp = $file->getParentId();
                if($iIdStorageTmp > 0){
                    $object = \Bitrix\Disk\BaseObject::loadById((int)$iIdStorageTmp, array('STORAGE'));
                    if (!$object) {
                        $idStorage = 0;
                        continue;
                    }
                }
                // end

                $arFileInfo = CFile::GetByID($file->getFileId());
                $arDetailIFile = $arFileInfo->arResult;
                $arPhaseName = '';
                if (!empty($arDetailIFile)) {
                    $file_name = $arDetailIFile[0]['FILE_NAME'];
                    $file_size = CFile::FormatSize(
                        $arDetailIFile[0]['FILE_SIZE']
                    );
                    $file_name = TruncateText($file_name, 30);
                    $acttact_by = YNSIRHelper::getTooltipUser($fitem['USER_UPLOAD'], 'acttact_by' . $idFile);
                    $arResult['CANDIDATE']['FILE'][] = array(
                        'file_name' => $file_name,
                        'acttact_by' => $acttact_by,
                        'modify_date' => $arResult['CANDIDATE']['MODIFIED_DATE'],
                        'file_size' => $file_size,
                        'category' => $ATTACHT_FIELD_NAME['NAME'],
                        'src' => '/upload/' . $arDetailIFile[0]['SUBDIR'] . '/' . $arDetailIFile[0]['FILE_NAME'],
                        'id' => $idFile,
                    );
                }
            }
            unset($fitem);
        }
        unset($ATTACHT_FIELD);
        unset($ATTACHT_FIELD_NAME);
    }

    // sharing folder
    if($idStorage > 0){
        if($isPerm[YNSIRConfig::CS_ATTACHMENT_INFORMATION] == true){
            $arDataShare = array(array('ID' => $USER::GetID(),'TYPE' => YNSIRDisk::PERMS_TYPE_USER,'PERMS' => YNSIRDisk::PERMS_ACCESS_READ));
            if($arResult['CAN_EDIT'] == true){
                $arDataShare[0]['PERMS'] = YNSIRDisk::PERMS_ACCESS_EDIT;
            }
            YNSIRDisk::shareFolder($idStorage, $arDataShare);
        }
    }
    // end


    //ENDTODO: BEGIN GET CANDIDATE BY ID

    $arResult["TITLE"] = GetMessage("YNSIR_CCD_TITLE");

    $arParams['GUID'] = 'ynsir_candidate_detail_' . $ID;
    $arResult['GUID'] = isset($arParams['GUID']) ? $arParams['GUID'] : strtolower($arResult['FORM_ID']) . '_qpv';

//CONFIG -->
    $config = CUserOptions::GetOption(
        'ynsirecruitment.candidate.detail.quickpanelview',
        $arResult['GUID'],
        null,
        $USER::GetID()
    );
    $enableDefaultConfig = !is_array($config);
    if ($enableDefaultConfig) {
        $config = array('enabled' => 'N', 'expanded' => 'Y', 'fixed' => 'Y');
    }
    $arResult['CONFIG'] = $config;


    /*
     *
     *
     * CRM ACTIVITY START HERE
     *
     */
    $arResult['EXTENSION'] = array(
        'ID' => $arResult['GRID_ID_MANAGER'],
        'CONFIG' =>
            array(
                'ownerTypeName' => 'CANDIDATE',
                'gridId' => $arResult['GRID_ID'],
                'activityEditorId' => $arResult['GRID_ID'] . '_activity_editor',
                'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID=s1&sessid=563fc767464a08365341672a78db8e87',
            ),
        'MESSAGES' =>
            array(
                'deletionDialogTitle' => 'Delete deal',
                'deletionDialogMessage' => 'Are you sure you want to delete it?',
                'deletionDialogButtonTitle' => 'Delete',
                'moveToCategoryDialogTitle' => 'Move to new pipeline',
                'moveToCategoryDialogMessage' => 'Deals currently in progress will be moved to the initial stage of the new pipeline; won deals will go to the "Deal completed" stage; lost deals - to the "Deal lost" stage. Note that the stage update records belonging to the source pipe will be purged from history. Custom fields that were used to calculate report numbers will be reset. They will collect data from scratch as soon as the deals are moved to the new pipe.',
            ),
    );
    /*
     *
     *
     * END CRM ACTIVIY
     *
     */

    /*
     * LIST TAB
     */

    $activityBindings = array(array('TYPE_NAME' => YNSIROwnerType::CandidateName, 'ID' => $ID));

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
                'PRESERVE_HISTORY' => true
            )
        )
    );
//HISTORY
    $arResult['FIELDS']['tab_event'][] = array(
        'id' => 'CANDIDATE_EVENT',
        'name' => "Candidate Events",//GetMessage('YNSIR_FIELD_DEAL_EVENT'),
        'colspan' => true,
        'type' => 'ynsir_event_view',
        'componentData' => array(
            'template' => '',
            'enableLazyLoad' => true,
            'contextId' => "CANDIDATE_".$ID."_EVENT",
            'params' => array(
                'AJAX_OPTION_ADDITIONAL' => "CANDIDATE_".$ID."_EVENT",
                'ENTITY_TYPE' => YNSIR_CANDIDATE,
                'ENTITY_ID' => $ID,
                'PATH_TO_USER_PROFILE' => "/company/personal/user/#user_id#/",//$arParams['PATH_TO_USER_PROFILE'],
                'FORM_ID' => $arResult['FORM_ID'],
                'TAB_ID' => 'tab_event',
                'INTERNAL' => 'Y',
                'SHOW_INTERNAL_FILTER' => 'Y',
                'PRESERVE_HISTORY' => true,
                'NAME_TEMPLATE' => $sFormatName,//$arParams['NAME_TEMPLATE']
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
                'CANDIDATE_OWNER_IS' => $ID,
                'FORM_ID' => $arResult['FORM_ID'],
                'TAB_ID' => 'tab_feedback',
                'INTERNAL' => 'Y',
                'SHOW_INTERNAL_FILTER' => 'Y',
                'PRESERVE_HISTORY' => true,
                'NAME_TEMPLATE' => $sFormatName,
                'CANDIDATE_ID' => $_GET['candidate_id'],
                'JOB_ORDER_ID' => $_GET['job_order_id'],
                'ROUND_ID' => $_GET['round_id'],
            )
        )
    );
    $arResult['FIELDS']['tab_order_associate_list'][] = array(
        'id' => 'CANDIDATE_ORDER_ASSOCIATE',
        'name' => "CANDIDATE_ORDER_ASSOCIATE",//GetMessage('YNSIR_FIELD_DEAL_EVENT'),
        'colspan' => true,
        'type' => 'ynsir_order_associate_list',
        'componentData' => array(
            'template' => '',
            'enableLazyLoad' => true,
            'contextId' => "CANDIDATE_ASSOCIATE_".$ID."_LIST",
            'params' => array(
                'AJAX_OPTION_ADDITIONAL' => "CANDIDATE_ASSOCIATE_".$ID."_LIST",
                'ENTITY_TYPE' => YNSIR_CANDIDATE,
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
                'INTERNAL_FILTER' => array('CANDIDATE_ID' => $ID),
                'GRID_ID_SUFFIX' => 'IN_CANDIDATE_TAB',
                'NAME_TEMPLATE' => $sFormatName,//$arParams['NAME_TEMPLATE']
            )
        )
    );
    $arResult['ACTION_URI'] = $arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;
    /*
     * END LIST TAB
     */

    /*
     * HISTORY VIEW CANDIDATE
     */
    YNSIREvent::RegisterViewEvent(YNSIROwnerType::Candidate, $ID, $currentUserID);

    /*
     * END VIEW CANDIDATE
     */
    $this->IncludeComponentTemplate();
}
?>