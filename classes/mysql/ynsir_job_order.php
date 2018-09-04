<?
class YNSIRJobOrder
{
    const TABLE_NAME = 'b_ynsir_job_order';
    const DB_TYPE = 'MYSQL';
    const TABLE_ALIAS = 'YJO';
    const ACTIVITY_CACHE_TIME = 86400;
    const ACTIVITY_CACHE_URL = '/ynsirecruitment/job_order/';
    const FREFIX_CACHE = 'RECRUITMENT_JOB_ORDER_';
    const JO_ENTITY_SUPERVISOR = 'SUPERVISOR';
    const JO_ENTITY_SUBORDINATE = 'SUBORDINATE';
    const JO_ENTITY_PARTICIPANT = 'PARTICIPANT';
    const JO_ENTITY_OWNER = 'OWNER';
    const JO_ENTITY_RECRUITER = 'RECRUITER';
    const JO_ENTITY_INTERVIEW = 'INTERVIEW';

    protected static $TYPE_NAME = YNSIR_JOB_ORDER;

    static function GetJobOrderFields($bJoinMultiple = false)
    {
        $arFields = array(
            "ID" => array('FIELD' => 'YJO.ID', 'TYPE' => 'int'),
            "CREATED_BY" => array('FIELD' => 'YJO.CREATED_BY', 'TYPE' => 'int'),
            "MODIFIED_BY" => array('FIELD' => 'YJO.MODIFIED_BY', 'TYPE' => 'int'),
            "DATE_CREATE" => array('FIELD' => 'YJO.DATE_CREATE', 'TYPE' => 'datetime'),
            "DATE_MODIFY" => array('FIELD' => 'YJO.DATE_MODIFY', 'TYPE' => 'datetime'),
            "HEADCOUNT" => array('FIELD' => 'YJO.HEADCOUNT', 'TYPE' => 'int'),
            "TITLE" => array('FIELD' => 'YJO.TITLE', 'TYPE' => 'string'),
            "LEVEL" => array('FIELD' => 'YJO.LEVEL', 'TYPE' => 'int'),
            "DEPARTMENT" => array('FIELD' => 'YJO.DEPARTMENT', 'TYPE' => 'int'),
            "EXPECTED_END_DATE" => array('FIELD' => 'YJO.EXPECTED_END_DATE', 'TYPE' => 'datetime'),
            "STATUS" => array('FIELD' => 'YJO.STATUS', 'TYPE' => 'string'),
            "VACANCY_REASON" => array('FIELD' => 'YJO.VACANCY_REASON', 'TYPE' => 'string'),
            "IS_REPLACE" => array('FIELD' => 'YJO.IS_REPLACE', 'TYPE' => 'int'),
            "SALARY_FROM" => array('FIELD' => 'YJO.SALARY_FROM', 'TYPE' => 'int'),
            "SALARY_TO" => array('FIELD' => 'YJO.SALARY_TO', 'TYPE' => 'int'),
            "NOTE_SALARY" => array('FIELD' => 'YJO.NOTE_SALARY', 'TYPE' => 'string'),
            "TEMPLATE_ID" => array('FIELD' => 'YJO.TEMPLATE_ID', 'TYPE' => 'int'),
            "DESCRIPTION" => array('FIELD' => 'YJO.DESCRIPTION', 'TYPE' => 'string'),
            "EXTEND_TEMPLATE" => array('FIELD' => 'YJO.EXTEND_TEMPLATE', 'TYPE' => 'int'),
            "WF_ELEMENT" => array('FIELD' => 'YJO.WF_ELEMENT', 'TYPE' => 'int'),
//            "SEARCH_CONTENT" => array('FIELD' => 'YJO.SEARCH_CONTENT', 'TYPE' => 'string'),
            "ACTIVE" => array('FIELD' => 'YJO.ACTIVE', 'TYPE' => 'int'),
            // JOIN
            // => NOTE : alias : BYUR1
            static::JO_ENTITY_SUPERVISOR => array(
                'FIELD' => 'BYUR1.USER_ID',
                'TYPE' => 'int',
                'FROM'=> 'LEFT JOIN b_ynsir_user_relation BYUR1 ON YJO.ID = BYUR1.SOURCE_ID AND BYUR1.ENTITY=\'' . static::JO_ENTITY_SUPERVISOR . '\'',
            ),
            // alias : BYUR3
            static::JO_ENTITY_OWNER => array(
                'FIELD' => 'BYUR3.USER_ID',
                'TYPE' => 'int',
                'FROM'=> 'LEFT JOIN b_ynsir_user_relation BYUR3 ON YJO.ID = BYUR3.SOURCE_ID AND BYUR3.ENTITY=\'' . static::JO_ENTITY_OWNER . '\'',
            ),
            // alias : BYUR4
            static::JO_ENTITY_RECRUITER => array(
                'FIELD' => 'BYUR4.USER_ID',
                'TYPE' => 'int',
                'FROM'=> 'LEFT JOIN b_ynsir_user_relation BYUR4 ON YJO.ID = BYUR4.SOURCE_ID AND BYUR4.ENTITY=\'' . static::JO_ENTITY_RECRUITER . '\'',
            )
        );
        if($bJoinMultiple == true){
            $arFields[static::JO_ENTITY_SUBORDINATE] = array(
                'FIELD' => 'BYUR2.USER_ID',
                'TYPE' => 'int',
                'FROM'=> 'LEFT JOIN b_ynsir_user_relation BYUR2 ON YJO.ID = BYUR2.SOURCE_ID AND BYUR2.ENTITY=\'' . static::JO_ENTITY_SUBORDINATE . '\'',
            );
            $arFields[static::JO_ENTITY_INTERVIEW] = array(
                'FIELD' => 'BYIR.ID',
                'TYPE' => 'int',
                'FROM'=> 'LEFT JOIN b_ynsir_interview_round BYIR ON YJO.ID = BYIR.JOB_ORDER',
            );
        }
        return $arFields;
    }

    public static function Update($iId = 0, $arFields = array(), $arOldData = array(),$bCompare = true, $arPerms = array(),$bUpdatePerm = true)
    {
        global $DB, $USER;
        $iResult = 0;
        $arUserTemp = array();
        $permissionEntityType = YNSIR_PERM_ENTITY_ORDER;
        $arFieldCompare = YNSIRConfig::getFieldsJobOrder();


        if ($iId > 0 && !empty($arFields)) {
            $iUserId = $USER->GetID();

            //PERMISSION CANDIDATE_OWNER
            $assignedByID = (int)$arFields['OWNER'];
            $arEntityAttr = self::BuildEntityAttr($assignedByID, array());
            $userPerms = YNSIRPerms_::GetUserPermissions($assignedByID);
            $sEntityPerm = $userPerms->GetPermType($permissionEntityType, 'WRITE', $arEntityAttr);
            self::PrepareEntityAttrs($arEntityAttr, $sEntityPerm);
            //End permission/
            //PERMISSION CANDIDATE_RECRUITER
            $assignedByID = (int)$arFields['RECRUITER'];
            $arEntityAttr_recruiter = self::BuildEntityAttr($assignedByID, array());
            $userPerms = YNSIRPerms_::GetUserPermissions($assignedByID);
            $sEntityPerm = $userPerms->GetPermType($permissionEntityType, 'WRITE', $arEntityAttr_recruiter);
            self::PrepareEntityAttrs($arEntityAttr_recruiter, $sEntityPerm);
            //End permission

            //Permission Personal For Interview
            $arEntityAttr_interview = array();
            $arInterviews[0] = array();
            if($arPerms[YNSIRConfig::OS_INTERVIEWS] == false && !empty($arOldData['INTERVIEW'])){
                foreach ($arOldData['INTERVIEW'] as $arInfoInterview) {
                    foreach ($arInfoInterview['PARTICIPANT'] as $iIdUser) {
                        $arInterviews[0][] = $iIdUser;
                    }
                }
            }
            else {
                $arInterviews = $arFields['INTERVIEW_PARTICIPANT'];
            }
            foreach($arInterviews as $INTERVIEW_IDs) {
                foreach ($INTERVIEW_IDs as $INTERVIEW_ID) {
                    $arEntityAttr_interview[] = "U{$INTERVIEW_ID}";
                    $arUserAttrs = $INTERVIEW_ID > 0 ? YNSIRPerms_::GetUserAttr($INTERVIEW_ID) : array();
                    if (!empty($arUserAttrs['INTRANET'])) {
                        $arEntityAttr_interview[] = "IU{$INTERVIEW_ID}";
                    }
                }
            }
            //MergeEntity
            $arEntityAttr = array_merge($arEntityAttr,$arEntityAttr_recruiter,$arEntityAttr_interview);
            $arEntityAttr = array_unique($arEntityAttr);

            // update job order
            $arEvents = array();
            $arFieldsUpdate = array();
            //Compare field and Get Field Change
            $arEvents = self::compareFields($arFields,$arOldData,$arFieldsUpdate,$arUserTemp);
            // subordinate

            $arFields[static::JO_ENTITY_SUBORDINATE] = is_array($arFields[static::JO_ENTITY_SUBORDINATE]) ? $arFields[static::JO_ENTITY_SUBORDINATE] : array();
            $arSubBordinateAdd = array_diff($arFields[static::JO_ENTITY_SUBORDINATE], $arOldData[static::JO_ENTITY_SUBORDINATE]);
            $arSubBordinateDel = array_diff($arOldData[static::JO_ENTITY_SUBORDINATE], $arFields[static::JO_ENTITY_SUBORDINATE]);

            if(!empty($arSubBordinateDel) || !empty($arSubBordinateAdd)){
                if(!empty($arSubBordinateDel)) {
                    // tracking Del subordinate
                    foreach ($arSubBordinateDel as $subId) {
                        if(intval($subId) > 0 && !key_exists($subId,$arUserTemp)) {
                            $arUserTemp[$subId] = YNSIRGeneral::getUserNameByID($subId);
                        }
                        $arEvents[] = Array(
                            'ENTITY_FIELD' => static::JO_ENTITY_SUBORDINATE,
                            'EVENT_NAME' => GetMessage('YNSIR_FIELD_COMPARE',
                                array('#FIELD#' => $arFieldCompare[static::JO_ENTITY_SUBORDINATE])),
                            'EVENT_TEXT_1' => $arUserTemp[$subId],
                            'EVENT_TEXT_2' => GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                        );
                    }
                    unset($subId);
                    unset($arSubBordinateDel);
                }
                if(!empty($arSubBordinateAdd)) {
                    // tracking Add subordinate
                    foreach ($arSubBordinateAdd as $subId) {
                        if(intval($subId) > 0 && !key_exists($subId,$arUserTemp)) {
                            $arUserTemp[$subId] = YNSIRGeneral::getUserNameByID($subId);
                        }
                        $arEvents[] = Array(
                            'ENTITY_FIELD' => static::JO_ENTITY_SUBORDINATE,
                            'EVENT_NAME' => GetMessage('YNSIR_FIELD_COMPARE',
                                array('#FIELD#' => $arFieldCompare[static::JO_ENTITY_SUBORDINATE])),
                            'EVENT_TEXT_1' => GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                            'EVENT_TEXT_2' => $arUserTemp[$subId],
                        );
                    }
                    unset($subId);
                    unset($arSubBordinateAdd);
                }
            } else {
                unset($arFields[static::JO_ENTITY_SUBORDINATE]);
            }

            $arFieldsUpdate['DATE_MODIFY'] = $DB->CurrentTimeFunction();
            $arFieldsUpdate['MODIFIED_BY'] = $iUserId;
            $iResult = $DB->Update(static::TABLE_NAME, $arFieldsUpdate, "WHERE ID='".intval($iId)."'");

            if($arPerms[YNSIRConfig::OS_BASIC_INFO] == true){
                $bSuboInsert = static::addUserRelation($arFields, $iId);
            }

            // interview
            if($arPerms[YNSIRConfig::OS_INTERVIEWS] == true){
                $arEvents = array_merge($arEvents,static::interviewUpdate(
                    $iId,
                    $arFields['INTERVIEW_PARTICIPANT'],
                    $arFields['INTERVIEW_NOTE'],
                    $arOldData[static::JO_ENTITY_INTERVIEW],
                    true
                ));
            }

            // permission
            if($iResult > 0 || $bSuboInsert) {
                if($bUpdatePerm) {
                    YNSIRPerms_::UpdateEntityAttr($permissionEntityType, $iId, $arEntityAttr);
                    //Update Associate
                    $DB->Query("DELETE FROM b_ynsir_entity_perms WHERE ENTITY='" . YNSIR_CANDIDATE . "' AND TYPE = '" . YNSIREntityPerms::PERMS_ENTITY_TYPE_ASSOCIATE . "' AND SOURCE = $iId", false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
                    $AsRs = YNSIRAssociateJob::GetList(array(), array('ORDER_JOB_ID' => $iId));
                    while ($arAs = $AsRs->Fetch()) {
                        foreach ($arEntityAttr as $eachAttr) {
                            YNSIRPerms_::AddEntityAttr(YNSIR_CANDIDATE, $arAs['CANDIDATE_ID'], array($eachAttr), YNSIREntityPerms::PERMS_ENTITY_TYPE_ASSOCIATE, $arAs['ORDER_JOB_ID']);
                        }
                    }
                    unset($AsRs);
                    unset($arAs);
                }
                //ENd

                Bitrix\YNSIR\Search\YNSIRSearchContentBuilderFactory::create(YNSIROwnerType::Order)->build($iId);
                if ($bCompare) {
                   // $arEvents = array();//self::CompareFields($arRow, $arFields);
                    foreach ($arEvents as $arEvent) {
                        $arEvent['ENTITY_TYPE'] = YNSIR_JOB_ORDER;
                        $arEvent['ENTITY_ID'] = $iId;
                        $arEvent['EVENT_TYPE'] = YNSIREvent::TYPE_CHANGE;

                        if (!isset($arEvent['USER_ID'])) {
                            if ($iUserId > 0) {
                                $arEvent['USER_ID'] = $iUserId;
                            } else if (isset($arFields['MODIFIED_BY']) && $arFields['MODIFIED_BY'] > 0) {
                                $arEvent['USER_ID'] = $arFields['MODIFIED_BY'];
                            }
                        }
                        $YNSIREvent = new YNSIREvent();
                        $eventID = $YNSIREvent->Add($arEvent, false);
                    }
                    unset($arEvent);
                    unset($arEvents);
                    unset($YNSIREvent);
                }
                foreach(GetModuleEvents("ynsirecruitment", "OnAfterJobOrderUpdate", true) as $arEvent)
                    ExecuteModuleEventEx($arEvent, array('ID' => $iId,'arOldData' => $arOldData, 'arNewData' => $arFields));

            }
        }
        return $iResult;
    }

    public static function OnAfterJobOrderUpdate($iId,$arOldData,$arFields) {
        //Notify when change STATUS => close
        switch ($arFields['STATUS']) {
            case JOStatus::JOSTATUS_CLOSED:
                //get Job Owner
                $obRes = YNSIRJobOrder::getList(array(), array('ID'=>intval($iId),'CHECK_PERMISSIONS' => 'N'), false, false);
                if ($arJoborder = $obRes->Fetch()) {
                    $arUserNotifi['JOB_OWNER'] = array(intval($arJoborder[YNSIRJobOrder::JO_ENTITY_OWNER]));
                }
                if($arFields['STATUS'] == $arOldData['STATUS']) return;
                $arUserNotifi['RM'] = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_recruitment_manager_config'));
                if(!is_array($arUserNotifi['RM'])) $arUserNotifi['RM'] = array();
                $arUserNotifi = array_merge($arUserNotifi['RM'],$arUserNotifi['JOB_OWNER']);
                $arUserNotifi = array_unique($arUserNotifi);
                $jobURL = CComponentEngine::MakePathFromTemplate(
                    '/recruitment/job-order/detail/#job_id#/',
                    array('job_id' => $iId)
                );

                $JOB_TITLE = '<a href="'.$jobURL.'" title="'.$arFields['TITLE'].'">'.$arFields['TITLE'].'</a>';
                $strMessage = GetMessage('YNSIR_NOTIFY_STATUS_CLOSE_JOB_ORDER', array(
                    '#JOB_TITLE#' => $JOB_TITLE
                ));

                foreach($arUserNotifi as $iAddressID) {
                    $tag = "YNSIRECRUITMENT|CHANGE_STATUS|".intval($iAddressID)."|".intval($iId)."|".JOStatus::JOSTATUS_CLOSED;
                    if(intval($iAddressID) <=0 ) continue;
                    YNSIRNotifier::Notify(
                        intval($iAddressID),
                        $strMessage,
                        $strMessage,
                        YNSIRNotifierSchemeType::UpdateJobOrderStatus,
                        $tag
                    );
                }
                break;
            default:
                break;
        }
        return;
    }

    public static function compareFields(&$arFields,$arOldData,&$arFieldsUpdate, &$arUserTemp){
        global $DB;
        $FORMAT_DB_TIME = 'YYYY-MM-DD';
        $FORMAT_DB_BX_SHORT = CSite::GetDateFormat("SHORT");

        $arMsg = array();
        $arFieldCompare = YNSIRConfig::getFieldsJobOrder();
        $arFieldConfig = static::GetJobOrderFields(false);
        // check note salary
        $bShowNoteSalary = COption::GetOptionString("ynsirecruitment", "ynsir_order_salary_note");
        if($bShowNoteSalary != 'Y'){
            unset($arFieldConfig['NOTE_SALARY']);
        }
        foreach ($arFieldConfig as $sIdCField => $arConfig) {
            if(!isset($arFields[$sIdCField]))
                continue;
            switch ($sIdCField){
                case 'ID':
                case 'ACTIVE':
                case 'CREATED_BY':
                case 'DATE_CREATE':
                case 'DATE_MODIFY':
                case 'MODIFIED_BY':
                case 'EXTEND_TEMPLATE':
                case 'SEARCH_CONTENT':
                    break;
                case 'DESCRIPTION':
                    if($arFields[$sIdCField] != $arOldData[$sIdCField]){
                        $arMsg[] = Array(
                            'ENTITY_FIELD' => $sIdCField,
                            'EVENT_NAME' => GetMessage('YNSIR_FIELD_COMPARE',
                                array('#FIELD#' => $arFieldCompare[$sIdCField])),
                            'EVENT_TEXT_1' => '',
                            'EVENT_TEXT_2' => '',
                        );

                        // end tracking normal field
                        $arFieldsUpdate[$sIdCField] = "'" . $DB->ForSql($arFields[$sIdCField]) . "'";
                    }
                    break;
                case static::JO_ENTITY_SUPERVISOR:
                case static::JO_ENTITY_OWNER:
                case static::JO_ENTITY_RECRUITER:
                // tracking : supervisor, owner, recruiter
                    if($arFields[$sIdCField] == $arOldData[$sIdCField]) {
                        unset($arFields[$sIdCField]);
                    }
                    else {
                        if(intval($arOldData[$sIdCField]) > 0 && !key_exists($arOldData[$sIdCField],$arUserTemp)) {
                            $arUserTemp[$arOldData[$sIdCField]] = YNSIRGeneral::getUserNameByID($arOldData[$sIdCField]);
                        }

                        if(intval($arFields[$sIdCField]) > 0 && !key_exists($arFields[$sIdCField],$arUserTemp)) {
                            $arUserTemp[$arFields[$sIdCField]] = YNSIRGeneral::getUserNameByID($arFields[$sIdCField]);
                        }
                        $arMsg[] = Array(
                            'ENTITY_FIELD' => $sIdCField,
                            'EVENT_NAME' => GetMessage('YNSIR_FIELD_COMPARE',
                                array('#FIELD#' => $arFieldCompare[$sIdCField])),
                            'EVENT_TEXT_1' => intval($arOldData[$sIdCField]) > 0 ? $arUserTemp[$arOldData[$sIdCField]] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                            'EVENT_TEXT_2' => intval($arFields[$sIdCField]) > 0 ? $arUserTemp[$arFields[$sIdCField]] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                        );
                    }
                    break;

                default:
                    if($arFields[$sIdCField] != $arOldData[$sIdCField]){
                        // tracking normal field
                        if ($sIdCField == 'DEPARTMENT') {
                            $arIDdepartment = array();
                            if(intval($arFields[$sIdCField]) > 0)
                                $arIDdepartment[] = intval($arFields[$sIdCField]);
                            if(intval($arOldData[$sIdCField]) > 0)
                                $arIDdepartment[] = intval($arOldData[$sIdCField]);
                            if(!empty($arIDdepartment)) {
                                $arDepReturn = YNSIRGeneral::getDepartment(array('ID' => $arIDdepartment));
                                $arOldData[$sIdCField] = $arDepReturn[$arOldData[$sIdCField]];
                                $arFieldsTracking[$sIdCField]  = $arDepReturn[$arFields[$sIdCField]];
                                unset($arDepReturn);
                            } else break;
                        } else if ($sIdCField == 'STATUS') {
                            $arJobStatus = YNSIRGeneral::getListJobStatus();
                            $arOldData[$sIdCField] = strlen($arJobStatus[$arOldData[$sIdCField]]) > 0 ? $arJobStatus[$arOldData[$sIdCField]] : '';
                            $arFieldsTracking[$sIdCField]  = strlen($arJobStatus[$arFields[$sIdCField]]) > 0 ? $arJobStatus[$arFields[$sIdCField]] : '';
                            unset($arJobStatus);
                        } else if ($sIdCField == 'EXPECTED_END_DATE') {
                                //Format short date
                            if (strlen($arOldData[$sIdCField]) > 0) {
                                $arOldData[$sIdCField] = $DB->FormatDate($arOldData[$sIdCField], $FORMAT_DB_TIME, $FORMAT_DB_BX_SHORT);
                            }
                            if (strlen($arFields[$sIdCField]) > 0) {
                                $arFieldsTracking[$sIdCField] = $DB->FormatDate($arFields[$sIdCField], $FORMAT_DB_TIME, $FORMAT_DB_BX_SHORT);
                            }
                        } else if ($sIdCField == 'IS_REPLACE') {
                                //Format short date
                                $arOldData[$sIdCField] = intval($arOldData[$sIdCField]) == 1 ? GetMessage('YNSIR_FIELD_TYPE_REPLACE') : GetMessage('YNSIR_FIELD_TYPE_NEW');
                                $arFieldsTracking[$sIdCField] = intval($arFields[$sIdCField]) == 1 ? GetMessage('YNSIR_FIELD_TYPE_REPLACE') : GetMessage('YNSIR_FIELD_TYPE_NEW');
                                if($arOldData[$sIdCField] == $arFieldsTracking[$sIdCField]) break;

                        } else if($sIdCField == 'LEVEL'){
                            $arLevel = YNSIRGeneral::getListType(array('ENTITY' => YNSIRConfig::TL_WORK_POSITION), true);
                            $arFieldsTracking[$sIdCField] = $arLevel[$arFields[$sIdCField]];
                            $arOldData[$sIdCField]  = $arLevel[$arOldData[$sIdCField]];
                        } else if($sIdCField == 'TEMPLATE_ID'){
                            $arTemplate = YNSIRJobOrderTemplate::getList(array('ACTIVE' => 1), true);
                            $arFieldsTracking[$sIdCField] = $arTemplate[$arFields[$sIdCField]]['NAME_TEMPLATE'];
                            $arOldData[$sIdCField]  = $arTemplate[$arOldData[$sIdCField]]['NAME_TEMPLATE'];
                        }
                        else {
                            $arFieldsTracking[$sIdCField] = $arFields[$sIdCField];
                        }


                        $arMsg[] = Array(
                            'ENTITY_FIELD' => $sIdCField,
                            'EVENT_NAME' => GetMessage('YNSIR_FIELD_COMPARE',
                                array('#FIELD#' => $arFieldCompare[$sIdCField])),
                            'EVENT_TEXT_1' => strlen($arOldData[$sIdCField]) > 0 ? $arOldData[$sIdCField] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                            'EVENT_TEXT_2' => strlen($arFieldsTracking[$sIdCField]) > 0 ? $arFieldsTracking[$sIdCField] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                        );

                        // end tracking normal field
                        $arFieldsUpdate[$sIdCField] = "'" . $DB->ForSql($arFields[$sIdCField]) . "'";
                    }
                    break;
            }
        }
        return $arMsg;
    }

    public static function interviewUpdate($iId = 0, $arNewPartData, $arNewNoteData, $arOldData,$saveEvent = true){
        // sort new
        $arNewInter = array();
        $arMsg = array();
        foreach ($arNewPartData as $sKeyTemp => $arParticipant) {
            $arNewInter[] = array(
                'PARTICIPANT' => $arParticipant,
                'NOTE' => $arNewNoteData[$sKeyTemp],
            );
        }
        // sort old
        $arOldInter = array();
        foreach ($arOldData as $sKeyTemp => $arDataRound) {
            $arOldInter[] = array(
                'PARTICIPANT' => $arDataRound['PARTICIPANT'],
                'NOTE' => $arDataRound['NOTE'],
                'ID' => $arDataRound['ID'],
            );
        }
        $iSizeNew = count($arNewInter);
        $iSizeOld = count($arOldInter);
        if($iSizeNew > $iSizeOld){
            // add new
            $iCountRound = $iSizeOld + 1;
            for($i = $iSizeOld; $i <= $iSizeNew - 1; $i++){
                YNSIRInterview::Add(array(
                    'JOB_ORDER' => $iId,
                    'ROUND_INDEX' => $iCountRound,
                    'NOTE' => $arNewInter[$i]['NOTE'],
                    YNSIRJobOrder::JO_ENTITY_PARTICIPANT => $arNewInter[$i]['PARTICIPANT']
                ));
                if($saveEvent) {
                    $arMsg[] = Array(
                        'ENTITY_FIELD' => YNSIRConfig::OS_INTERVIEWS,
                        'EVENT_NAME' => GetMessage('YNSIR_ADDITIONAL_FIELD_ADD_ROUND'),
                        'EVENT_TEXT_1' => strlen($arNewInter[$i]['NOTE']) > 0 ? GetMessage('YNSIR_FIELD_LABEL_NOTE',array('#NOTE#' => $arNewInter[$i]['NOTE'])) : '',
                    );
                }
                $iCountRound++;
                unset($arNewInter[$i]);
            }
        }
        else {
            // remove
            for ($i = $iSizeNew; $i <= $iSizeOld - 1; $i++) { 
                YNSIRInterview::delete($arOldInter[$i]['ID']);
                if($saveEvent) {
                    $arMsg[] = Array(
                        'ENTITY_FIELD' => YNSIRConfig::OS_INTERVIEWS,
                        'EVENT_NAME' => GetMessage('YNSIR_ADDITIONAL_FIELD_REMOVE_ROUND'),
                        'EVENT_TEXT_1' => strlen($arOldInter[$i]['NOTE']) > 0 ? GetMessage('YNSIR_FIELD_LABEL_NOTE',array('#NOTE#' => $arOldInter[$i]['NOTE'])) : '',
                    );
                }
            }
        }
        
        // compare and update
        $iCountRound = 0;
        foreach ($arNewInter as $arItemNewRound) {
            $arAddParticipant = array_diff($arItemNewRound['PARTICIPANT'], $arOldInter[$iCountRound]['PARTICIPANT']);
            $arDelParticipant = array_diff($arOldInter[$iCountRound]['PARTICIPANT'], $arItemNewRound['PARTICIPANT']);
            if(!empty($arDelParticipant)
                || !empty($arAddParticipant)
                || $arItemNewRound['NOTE'] != $arOldInter[$iCountRound]['NOTE']){
                // update
                $arItemNewRound['ID'] = $arOldInter[$iCountRound]['ID'];
                YNSIRInterview::Update($arOldInter[$iCountRound]['ID'], $arItemNewRound);
                if($saveEvent){
                    $arMsg[] = Array(
                        'ENTITY_FIELD' => static::JO_ENTITY_SUBORDINATE,
                        'EVENT_NAME' => GetMessage('YNSIR_FIELD_COMPARE',
                            array('#FIELD#' => GetMessage('YNSIR_ROUND_LABEL',array('#ROUND_INDEX#' => $iCountRound+1))))
                    );
                }


            }
            $iCountRound++;
        }
        return $arMsg;
    }

    public static function Add($arFields = array()){
        global $DB, $USER;
        $iResult = 0;
        $permissionEntityType = YNSIR_PERM_ENTITY_ORDER;

        if(!empty($arFields)){
            $iUserId = $USER->GetID();
            $arDataInsert = array();
            $arFieldConfig = static::GetJobOrderFields(false);
            $arCFieldNotCheck = array(
                'ID', 'ACTIVE', static::JO_ENTITY_SUPERVISOR, static::JO_ENTITY_OWNER, static::JO_ENTITY_RECRUITER
            );
            foreach ($arFieldConfig as $sIdCField => $arConfig) {
                if(in_array($sIdCField, $arCFieldNotCheck, true)) continue;
                $arDataInsert[$sIdCField] = "'" . $DB->ForSql($arFields[$sIdCField]) . "'";
            }
            $arDataInsert['DATE_CREATE'] = $DB->CurrentTimeFunction();
            $arDataInsert['DATE_MODIFY'] = $DB->CurrentTimeFunction();
            $arDataInsert['CREATED_BY'] = $arDataInsert['MODIFIED_BY'] = $iUserId;
            // search content

//            $arDataInsert['SEARCH_CONTENT'] = static::initSearchContent($arDataInsert);

            //PERMISSION CANDIDATE_OWNER
            $IdOwner = (int)$arFields['OWNER'];
            $arEntityAttr = self::BuildEntityAttr($IdOwner, array());
            $userPerms = YNSIRPerms_::GetUserPermissions($IdOwner);
            $sEntityPerm = $userPerms->GetPermType($permissionEntityType, 'ADD', $arEntityAttr);
            self::PrepareEntityAttrs($arEntityAttr, $sEntityPerm);
            //End permission

            //PERMISSION CANDIDATE_RECRUITER
            $assignedByID = (int)$arFields['RECRUITER'];
            $arEntityAttr_recruiter = self::BuildEntityAttr($assignedByID, array());
            $userPerms = YNSIRPerms_::GetUserPermissions($assignedByID);
            $sEntityPerm = $userPerms->GetPermType($permissionEntityType, 'WRITE', $arEntityAttr_recruiter);
            self::PrepareEntityAttrs($arEntityAttr_recruiter, $sEntityPerm);
            //End permission

            //Permission Personal For Interview
            $arEntityAttr_interview = array();
            foreach($arFields['INTERVIEW_PARTICIPANT'] as $INTERVIEW_IDs) {
                foreach ($INTERVIEW_IDs as $INTERVIEW_ID) {
                    $arEntityAttr_interview[] = "U{$INTERVIEW_ID}";
                    $arUserAttrs = $INTERVIEW_ID > 0 ? YNSIRPerms_::GetUserAttr($INTERVIEW_ID) : array();
                    if (!empty($arUserAttrs['INTRANET'])) {
                        $arEntityAttr_interview[] = "IU{$INTERVIEW_ID}";
                    }
                }
            }
            //MergeEntity
            $arEntityAttr = array_merge($arEntityAttr,$arEntityAttr_recruiter,$arEntityAttr_interview);
            $arEntityAttr = array_unique($arEntityAttr);

            // insert new job order
            $iResult = $DB->Insert(static::TABLE_NAME, $arDataInsert);
            // insert user relation
            if($iResult > 0) {
                YNSIRPerms_::UpdateEntityAttr($permissionEntityType, $iResult, $arEntityAttr);
                Bitrix\YNSIR\Search\YNSIRSearchContentBuilderFactory::create(YNSIROwnerType::Order)->build($iResult);
            }

            $bSuboInsert = static::addUserRelation($arFields, $iResult);
            // insert interview round
            $iCountRound = 1;
            foreach ($arFields['INTERVIEW_PARTICIPANT'] as $sKeyRound => $arParticipant) {
                YNSIRInterview::Add(array(
                    'JOB_ORDER' => $iResult,
                    'ROUND_INDEX' => $iCountRound,
                    'NOTE' => isset($arFields['INTERVIEW_NOTE'][$sKeyRound]) ? $arFields['INTERVIEW_NOTE'][$sKeyRound] : '',
                    YNSIRJobOrder::JO_ENTITY_PARTICIPANT => $arParticipant
                ));
                $iCountRound++;
            }
        }
        return $iResult;
    }

    public static function initSearchContent($arFields){
        $sResult = '';
        if(!empty($arFields)){
            $arFieldSearch = array('TITLE', 'VACANCY_REASON');
            foreach ($arFieldSearch as $sFieldSearch) {
                if(array_key_exists($sFieldSearch, $arFields)){
                    switch ($sFieldSearch) {
                        case 'OTHER':
                            // TODO
                            break;
                        default:
                            $sResult .= str_rot13($arFields[$sFieldSearch]);
                            break;
                    }
                }
            }
        }
        return $sResult;
    }

    public static function addUserRelation($arFields = array(), $iSource = 0){
        $bResult = true;
        $iSource = intval($iSource);
        if(!empty($arFields) && $iSource > 0){
            $arUserRelation = array();
            if(array_key_exists(static::JO_ENTITY_SUPERVISOR, $arFields)){
                $arUserRelation[] = array(
                    'SOURCE_ID'=> $iSource,
                    'USER_ID'=> intval($arFields[static::JO_ENTITY_SUPERVISOR]),
                    'ENTITY'=> static::JO_ENTITY_SUPERVISOR,
                );
                YNSIRUserRelation::delete(array('SOURCE_ID' => $iSource, 'ENTITY' => static::JO_ENTITY_SUPERVISOR));
            }
            if(array_key_exists(static::JO_ENTITY_OWNER, $arFields)){
                $arUserRelation[] = array(
                    'SOURCE_ID'=> $iSource,
                    'USER_ID'=> intval($arFields[static::JO_ENTITY_OWNER]),
                    'ENTITY'=> static::JO_ENTITY_OWNER,
                );
                YNSIRUserRelation::delete(array('SOURCE_ID' => $iSource, 'ENTITY' => static::JO_ENTITY_OWNER));
            }
            if(array_key_exists(static::JO_ENTITY_RECRUITER, $arFields)){
                $arUserRelation[] = array(
                    'SOURCE_ID'=> $iSource,
                    'USER_ID'=> intval($arFields[static::JO_ENTITY_RECRUITER]),
                    'ENTITY'=> static::JO_ENTITY_RECRUITER,
                );
                YNSIRUserRelation::delete(array('SOURCE_ID' => $iSource, 'ENTITY' => static::JO_ENTITY_RECRUITER));
            }
            if(array_key_exists(static::JO_ENTITY_SUBORDINATE, $arFields) && is_array($arFields[static::JO_ENTITY_SUBORDINATE])){
                foreach ($arFields[static::JO_ENTITY_SUBORDINATE] as $iIdSubo) {
                    $arUserRelation[] = array(
                        'SOURCE_ID'=> $iSource,
                        'USER_ID'=> intval($iIdSubo),
                        'ENTITY'=> static::JO_ENTITY_SUBORDINATE,
                    );
                }
                YNSIRUserRelation::delete(array('SOURCE_ID' => $iSource, 'ENTITY' => static::JO_ENTITY_SUBORDINATE));
            }
            if(!empty($arUserRelation)){
                foreach ($arUserRelation as $arUR) {
                    $iId = YNSIRUserRelation::Add($arUR);
                    if($iId <= 0){
                        $bResult = false;
                    }
                }
            }
        }
        return $bResult;
    }

    public static function Delete($id){
        global $DB;
        $query = "DELETE FROM " . static::TABLE_NAME . " WHERE ID=".$id;
        $ID = $DB->Query($query);
        $permissionEntityType = YNSIR_JOB_ORDER;
        $DB->Query("DELETE FROM b_ynsir_entity_perms WHERE ENTITY='" . $permissionEntityType . "' AND ENTITY_ID = $id", false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
        //Delete Associate Candidate
        $DB->Query("DELETE FROM b_ynsir_entity_perms WHERE ENTITY='" . YNSIR_CANDIDATE ."' AND TYPE = '" .YNSIREntityPerms::PERMS_ENTITY_TYPE_ASSOCIATE . "' AND SOURCE = $id", false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
        return $ID;
    }

    public static function DeletebyArray($arID){
        global $DB;
        if(empty($arID)) return;
        $permissionEntityType = YNSIR_JOB_ORDER;
        $strQuery = '('.implode(",", $arID).')';
        $query = "DELETE FROM " . static::TABLE_NAME . " WHERE ID in" . $strQuery;
        $ID = $DB->Query($query);
        $sId = implode(', ', $arID);
        $DB->Query("DELETE FROM b_ynsir_entity_perms WHERE ENTITY='" . $permissionEntityType . "' AND ENTITY_ID IN ({$sId})", false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
        //Delete Associate Candidate
        $DB->Query("DELETE FROM b_ynsir_entity_perms WHERE ENTITY='" . YNSIR_CANDIDATE ."' AND TYPE = '" .YNSIREntityPerms::PERMS_ENTITY_TYPE_ASSOCIATE . "' AND SOURCE IN ({$sId})", false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
        return $ID;
    }

    public static function DeActivebyArray($arID){
        global $DB;
        if(empty($arID)) return;
        $permissionEntityType = YNSIR_PERM_ENTITY_ORDER;
        $strQuery = '('.implode(",", $arID).')';
        $arFieldsUpdate = array('ACTIVE' => '0');
        $rows = $DB->Update(static::TABLE_NAME, $arFieldsUpdate, " WHERE ID in" . $strQuery);
        if($rows > 0) {
            $sId = implode(', ', $arID);
            //remove Permission
            $DB->Query("DELETE FROM b_ynsir_entity_perms WHERE ENTITY='" . $permissionEntityType . "' AND ENTITY_ID IN ({$sId})", false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
            $DB->Query("DELETE FROM b_ynsir_entity_perms WHERE ENTITY='" . YNSIR_CANDIDATE ."' AND TYPE = '" .YNSIREntityPerms::PERMS_ENTITY_TYPE_ASSOCIATE . "' AND SOURCE IN ({$sId})", false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
            //remove Associate
            $obRes = YNSIRAssociateJob::GetList(array(), array('ORDER_JOB_ID'=>$arID), false, false, array('ID'));
            while ($arAssociate = $obRes->Fetch()) {
                $IDs[] = $arAssociate['ID'];
            }
            unset($obRes);
            foreach ($IDs as $ID) {
                $DB->StartTransaction();
                if (YNSIRAssociateJob::Delete($ID)) {
                    $DB->Commit();
                } else {
                    $DB->Rollback();
                }
            }

        }
        return $rows;
    }

    public static function getById($iId = 0, $bConvertDescription = true){
        
        if(!CModule::IncludeModule("blog"))
            return false;

        $arResult = array();
        $iId = intval($iId);
        if($iId > 0){
            $obRes = static::GetList(array(), array('ID' => $iId, 'CHECK_PERMISSIONS' => 'N'), false, false, array());
            $arDataTemp = array();
            $arSubordinate = array();
            $arDataInterview = array();
            $p = new blogTextParser();
            while ($arData = $obRes->Fetch()) {
                if(!isset($arDataTemp[$iId])){
                    if($bConvertDescription == true){
                        $arData['DESCRIPTION'] = $p->convert($arData['DESCRIPTION']);
                    }
                    $arDataTemp[$iId] = $arData;
                }
                $arDataInterview[$arData[static::JO_ENTITY_INTERVIEW]] = $arData[static::JO_ENTITY_INTERVIEW];
                if($arData[static::JO_ENTITY_SUBORDINATE] > 0){
                    $arSubordinate[$arData[static::JO_ENTITY_SUBORDINATE]] = $arData[static::JO_ENTITY_SUBORDINATE];
                }
                //$arSubordinate[$arData[static::JO_ENTITY_SUBORDINATE]] = $arData[static::JO_ENTITY_SUBORDINATE];
            }
            if(!empty($arDataTemp)){
                // remove string -> convert to array
                unset($arDataTemp[$iId][static::JO_ENTITY_INTERVIEW]);
                unset($arDataTemp[$iId][static::JO_ENTITY_SUBORDINATE]);
                $arDataTemp[$iId][static::JO_ENTITY_INTERVIEW] = $arDataInterview;
                $arDataTemp[$iId][static::JO_ENTITY_SUBORDINATE] = $arSubordinate;
                $arResult = $arDataTemp[$iId];
            }
        }
        return $arResult;
    }

    public static function GetList($arOrder = array("ID" => "DESC"), $arFilter, $arGroupBy = false,$arNavStartParams = false, $arOptions)
    {
        $arField = self::GetJobOrderFields(true);
        self::NormalizeFilter($arFilter);
        if(!key_exists('ACTIVE',$arFilter))
            $arFilter['ACTIVE'] = 1;

        $lb = new YNSIRSQLHelper(
            static::DB_TYPE,
            static::TABLE_NAME,
            static::TABLE_ALIAS,
            $arField,
            '',
            '',
            array('YNSIRJobOrder', '__BuildPermSql'),
            array('YNSIRJobOrder', '__AfterPrepareSql')
        );
        return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, array_keys($arField), $arOptions);
    }

    public static function GetListJobOrder($arOrder = array("ID" => "DESC"), $arFilter, $arGroupBy = false,$arNavStartParams = false, $arOptions)
    {
        $arField = self::GetJobOrderFields(false);
        self::NormalizeFilter($arFilter);
        if(!key_exists('ACTIVE',$arFilter))
            $arFilter['ACTIVE'] = 1;
        $lb = new YNSIRSQLHelper(
            static::DB_TYPE,
            static::TABLE_NAME,
            static::TABLE_ALIAS,
            $arField,
            '',
            '',
            array('YNSIRJobOrder', '__BuildPermSql'),
            array('YNSIRJobOrder', '__AfterPrepareSql')
        );
        return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, array_keys($arField), $arOptions);
    }

    public static function NormalizeFilter(&$arFilter) {
        global $USER;
        $isAdmin = $USER->IsAdmin();
        $userId = YNSIRSecurityHelper::GetCurrentUserID();
        if(!$isAdmin) {
            $arFilter['__INNER_FILTER_ID_STATUS_PERMISSION_NEW_OR'] = array(
                'LOGIC' => 'OR',
                '!STATUS' => JOStatus::JOSTATUS_NEW,
                '__INNER_FILTER_ID_STATUS_PERMISSION_NEW_AND' => array(
                    'LOGIC' => 'AND',
                    'STATUS' => JOStatus::JOSTATUS_NEW,
                    'CREATED_BY' => $userId
                )
            );
        }

    }

    public static function listStatusCanUpdate($bPermsApprove = false, $sCurrentStatus = JOStatus::JOSTATUS_NEW, $arStatus = array())
    {
        $arResult = array();
        $sCurrentStatus = is_null($sCurrentStatus) ? JOStatus::JOSTATUS_NEW : $sCurrentStatus;
        if(empty($arStatus)){
            $arStatus = YNSIRGeneral::getListJobStatus();
        }
        if(array_key_exists ($sCurrentStatus, $arStatus)){
            $arResult = $arStatus;
            if($bPermsApprove == false){
                if($sCurrentStatus == JOStatus::JOSTATUS_NEW || $sCurrentStatus == JOStatus::JOSTATUS_WAITING){
                    $arResult = array(
                        JOStatus::JOSTATUS_NEW => $arStatus[JOStatus::JOSTATUS_NEW],
                        JOStatus::JOSTATUS_WAITING => $arStatus[JOStatus::JOSTATUS_WAITING],
                    );
                }
            }
        }
        return $arResult;
    }

    /*
     * Update permission
     * Author nhatth2
     * Date: 18-09-2017
     */
    public static function CheckCreatePermission($userPermissions = null)
    {
        return YNSIRAuthorizationHelper::CheckCreatePermission(self::$TYPE_NAME, $userPermissions);
    }
    public static function CheckCreatePermissionSec($userPermissions = null,$SECTION)
    {
        return YNSIRAuthorizationHelper::CheckCreatePermissionSec(self::$TYPE_NAME, $userPermissions,$SECTION);
    }

    public static function CheckUpdatePermission($ID, $userPermissions = null)
    {
        return YNSIRAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions);
    }
    public static function CheckUpdatePermissionSec($ID,$SECTION, $userPermissions = null)
    {
        return YNSIRAuthorizationHelper::CheckUpdatePermissionSec($SECTION,self::$TYPE_NAME, $ID, $userPermissions);
    }
    public static function CheckReadPermission($ID = 0,$userPermissions = null, $SECTION = '', $categoryID = -1, array $options = null)
    {
        return YNSIRAuthorizationHelper::CheckReadPermission(self::$TYPE_NAME, $ID, $userPermissions,null,$SECTION);
    }
    static public function __BuildPermSql($sAliasPrefix = self::TABLE_ALIAS, $mPermType = 'READ', $arOptions = array())
    {
        return YNSIRPerms_::BuildSql(YNSIR_JOB_ORDER, $sAliasPrefix, $mPermType, $arOptions);
    }
    public static function IsAccessEnabled(YNSIRPerms_ $userPermissions = null)
    {
        return self::CheckReadPermission(0, $userPermissions);
    }

    static public function BuildEntityAttr($userID, $arAttr = array())
    {
        $userID = (int)$userID;
        $arResult = array("U{$userID}");
        if (isset($arAttr['OPENED']) && $arAttr['OPENED'] == 'Y') {
            $arResult[] = 'O';
        }
        $arUserAttr = YNSIRPerms_::BuildUserEntityAttr($userID);
        return array_merge($arResult, $arUserAttr['INTRANET']);
    }
    public static function PrepareEntityAttrs(&$arEntityAttr, $entityPermType)
    {
        // Ensure that entity accessable for user restricted by YNSIR_PERM_OPEN
        if ($entityPermType === YNSIR_PERM_OPEN && !in_array('O', $arEntityAttr, true)) {
            $arEntityAttr[] = 'O';
        }
    }
    public static function __AfterPrepareSql($sender, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
    {
        $sqlData = array('FROM' => array(), 'WHERE' => array());
        if (isset($arFilter['SEARCH_CONTENT']) && $arFilter['SEARCH_CONTENT'] !== '') {
            $tableAlias = $sender->GetTableAlias();
            $queryWhere = new CSQLWhere();
            $queryWhere->SetFields(
                array(
                    'SEARCH_CONTENT' => array(
                        'FIELD_NAME' => "{$tableAlias}.SEARCH_CONTENT",
                        'FIELD_TYPE' => 'string',
                        'JOIN' => false
                    )
                )
            );
            $query = $queryWhere->GetQuery(
                Bitrix\YNSIR\Search\YNSIRSearchEnvironment::prepareEntityFilter(
                    YNSIROwnerType::Order,
                    array('SEARCH_CONTENT' => $arFilter['SEARCH_CONTENT'])
                )
            );
            if ($query !== '') {
                $sqlData['WHERE'][] = $query;
            }
        }

        $result = array();
        if (!empty($sqlData['FROM'])) {
            $result['FROM'] = implode(' ', $sqlData['FROM']);
        }
        if (!empty($sqlData['WHERE'])) {
            $result['WHERE'] = implode(' AND ', $sqlData['WHERE']);
        }

        return !empty($result) ? $result : false;
    }
    public static function Exists($ID)
    {
        $ID = intval($ID);
        if($ID <= 0)
        {
            return false;
        }

        $dbRes = self::GetList(
            array(),
            array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
            false,
            false,
            array('ID')
        );

        return is_array($dbRes->Fetch());
    }

    public static function changeStatusWaiting($jo_id)
    {
        $arFilter['ID'] = $jo_id;
        $rs = YNSIRJobOrder::GetListJobOrder(array("ID" => "DESC"), $arFilter);
        $arStatus = YNSIRGeneral::getListJobStatus();
        if ($arOrder = $rs->Fetch()) {
            if($arOrder['STATUS'] == JOStatus::JOSTATUS_NEW) {
                $arDataNew['STATUS'] = JOStatus::JOSTATUS_WAITING;
                $arDataOld['STATUS'] = JOStatus::JOSTATUS_NEW;
                $bUpdate = YNSIRJobOrder::Update($jo_id, $arDataNew, $arDataOld,true,array(),false);
                return array('SUCCESS'=>1,'MESS'=>'');
            }
            return array('SUCCESS'=>0,'MESS'=> GetMessage('YNSIR_CANT_CHANGE_JO_STATUS',array('#OLD_STATUS#'=>$arStatus[$arOrder['STATUS']],'#NEW_STATUS#'=>$arStatus[JOStatus::JOSTATUS_WAITING])));
        }
        return array('SUCCESS'=>0,'MESS'=>GetMessage('YNSIR_NOT_FOUND_JO'));
    }
}
?>