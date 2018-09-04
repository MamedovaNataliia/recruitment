<?
class YNSIRAssociateJob
{
    const TABLE_NAME = 'b_ynsir_associate_job';
    const DB_TYPE = 'MYSQL';
    const TABLE_ALIAS = 'YAJ';
    private static $FIELDS = null;


    static function GetFields()
    {
//        global $USER;
        if (!isset(self::$FIELDS)) {
            $createdByJoin = 'LEFT JOIN b_user U ON YAJ.CREATED_BY = U.ID';
            $modifiedByJoin = 'LEFT JOIN b_user UM ON YAJ.MODIFIED_BY = UM.ID';

            $CandidateByJoin = 'LEFT JOIN b_ynsir_candidate YC ON YAJ.CANDIDATE_ID = YC.ID';
            $OrderByJoin = 'LEFT JOIN b_ynsir_job_order YOD ON YAJ.ORDER_JOB_ID = YOD.ID AND YOD.ACTIVE = 1';

            self::$FIELDS = array(
                "ID" => array('FIELD' => 'YAJ.ID', 'TYPE' => 'int'),

                "CANDIDATE_ID"          => array('FIELD' => 'YAJ.CANDIDATE_ID', 'TYPE' => 'int'),
                "CANDIDATE_STATUS"      => array('FIELD' => 'YC.CANDIDATE_STATUS', 'TYPE' => 'string', 'FROM'=> $CandidateByJoin),
                "CANDIDATE_FIRST_NAME"  => array('FIELD' => 'YC.FIRST_NAME', 'TYPE' => 'string', 'FROM'=> $CandidateByJoin),
                "CANDIDATE_LAST_NAME"   => array('FIELD' => 'YC.LAST_NAME', 'TYPE' => 'string', 'FROM'=> $CandidateByJoin),

                "ORDER_JOB_ID"        => array('FIELD' => 'YAJ.ORDER_JOB_ID', 'TYPE' => 'int'),
                "ORDER_JOB_TITLE"     => array('FIELD' => 'YOD.TITLE', 'TYPE' => 'string', 'FROM'=> $OrderByJoin),
                "STATUS_ID"           => array('FIELD' => 'YAJ.STATUS_ID', 'TYPE' => 'string'),
                "STATUS_ROUND_ID"     => array('FIELD' => 'YAJ.STATUS_ROUND_ID', 'TYPE' => 'int'),

                "MODIFIED_DATE"             => array('FIELD' => 'YAJ.MODIFIED_DATE', 'TYPE' => 'datetime'),
                "CREATED_DATE"              => array('FIELD' => 'YAJ.CREATED_DATE', 'TYPE' => 'datetime'),

                "CREATED_BY"                => array('FIELD' => 'YAJ.CREATED_BY', 'TYPE' => 'int'),
                'CREATED_BY_LOGIN'          => array('FIELD' => 'U.LOGIN', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
                'CREATED_BY_NAME'           => array('FIELD' => 'U.NAME', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
                'CREATED_BY_LAST_NAME'      => array('FIELD' => 'U.LAST_NAME', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
                'CREATED_BY_SECOND_NAME'    => array('FIELD' => 'U.SECOND_NAME', 'TYPE' => 'string', 'FROM'=> $createdByJoin),
                'CREATED_BY_PERSONAL_PHOTO' => array('FIELD' => 'U.PERSONAL_PHOTO', 'TYPE' => 'int', 'FROM'=> $createdByJoin),

                "MODIFIED_BY"               => array('FIELD' => 'YAJ.MODIFIED_BY', 'TYPE' => 'int'),
                'MODIFIED_BY_LOGIN'         => array('FIELD' => 'UM.LOGIN', 'TYPE' => 'string', 'FROM'=> $modifiedByJoin),
                'MODIFIED_BY_NAME'          => array('FIELD' => 'UM.NAME', 'TYPE' => 'string', 'FROM'=> $modifiedByJoin),
                'MODIFIED_BY_LAST_NAME'     => array('FIELD' => 'UM.LAST_NAME', 'TYPE' => 'string', 'FROM'=> $modifiedByJoin),
                'MODIFIED_BY_SECOND_NAME'   => array('FIELD' => 'UM.SECOND_NAME', 'TYPE' => 'string', 'FROM'=> $modifiedByJoin),
                'MODIFIED_BY_PERSONAL_PHOTO'=> array('FIELD' => 'UM.PERSONAL_PHOTO', 'TYPE' => 'int', 'FROM'=> $modifiedByJoin),
            );
        }
        return self::$FIELDS;
    }

    public static function Add($arFields = array(),$saveEvent = true,$notify = true)
    {
        global $DB;
        $ID = 0;
        $iUserId = YNSIRSecurityHelper::GetCurrentUserID();
        $sFormatName = CSite::GetNameFormat(false);

        if(!isset($arFields['CREATED_BY']) || intval($arFields['CREATED_BY']) < 0) {
            $arFields['CREATED_BY'] = $iUserId;
        }

        if(!isset($arFields['MODIFIED_BY']) || intval($arFields['MODIFIED_BY']) < 0) {
            $arFields['MODIFIED_BY'] = $iUserId;
        }

        foreach ($arFields as $IDX => $field) {
            if(strlen($field) > 0)
                $arFieldsInsert[$IDX] = "'" . $DB->ForSql($field) . "'";

        }
        if (isset($arFieldsInsert['MODIFIED_DATE'])) {
            unset($arFieldsInsert['MODIFIED_DATE']);
        }

        if (isset($arFieldsInsert['CREATED_DATE'])) {
            unset($arFieldsInsert['CREATED_DATE']);
        }

        $arFieldsInsert['MODIFIED_DATE'] = $DB->CurrentTimeFunction();
        $arFieldsInsert['CREATED_DATE'] = $DB->CurrentTimeFunction();

        if (!empty($arFieldsInsert)) {
            $ID = $DB->Insert(static::TABLE_NAME, $arFieldsInsert);
            //Assign permission Current JOb -> Candidate
            if($ID > 0) {
                $ardupPermEntity = array();
                $dupPermEntityRs = YNSIREntityPerms::GetList(array(),
                    array('ENTITY' => YNSIR_JOB_ORDER,
                        'ENTITY_ID' => $arFields['ORDER_JOB_ID'],
                        'TYPE' => array(NULL,''),
                        'SOURCE' => array(NULL,'')));
                while($permEntity = $dupPermEntityRs->Fetch()) {
                    YNSIRPerms_::AddEntityAttr(YNSIR_CANDIDATE, $arFields['CANDIDATE_ID'], array($permEntity['ATTR']), YNSIREntityPerms::PERMS_ENTITY_TYPE_ASSOCIATE, $arFields['ORDER_JOB_ID']);
                }
                unset($ardupPermEntity);
                unset($permEntity);
            }

            //TODO: Save event
            if($ID > 0 && $saveEvent) {
                $arEvent = array(
                    'EVENT_TYPE'  => YNSIREvent::TYPE_ASSOCIATE,
                    'EVENT_TEXT_2' => '',
                    'FILES' => array()
                );

                if (!isset($arEvent['USER_ID'])) {
                    if ($iUserId > 0) {
                        $arEvent['USER_ID'] = $iUserId;
                    } else if (isset($arFields['MODIFIED_BY']) && $arFields['MODIFIED_BY'] > 0) {
                        $arEvent['USER_ID'] = $arFields['MODIFIED_BY'];
                    }
                }
                $YNSIREvent = new YNSIREvent();
                //Order event
                $obRes = YNSIRCandidate::GetList(array(), array('ID'=>intval($arFields['CANDIDATE_ID'])), false, false, array('ID','FIRST_NAME','LAST_NAME'));
                if ($arCandidate = $obRes->Fetch()) {

                    $arCandidate['FULL_NAME'] = CUser::FormatName(
                        $sFormatName,
                        array(
                            "NAME" => $arCandidate['FIRST_NAME'],
                            "LAST_NAME" => $arCandidate['LAST_NAME'],
                        )
                    );
                    $arEventOrder = array_merge($arEvent,array(
                        'EVENT_NAME' => GetMessage('YNSIR_ADD_ASSOCIATE_CANDIDATE'),
                        'ENTITY_TYPE' => YNSIR_JOB_ORDER,
                        'ENTITY_ID'  => intval($arFields['ORDER_JOB_ID']),
                        'EVENT_TEXT_1' => $arCandidate['FULL_NAME'],
                    ));
                    $eventID = $YNSIREvent->Add($arEventOrder, false);
                }
                unset($obRes);
                unset($arCandidate);
                unset($arEventOrder);
                //Candidate event
                $obRes = YNSIRJobOrder::GetList(array(), array('ID'=>intval($arFields['ORDER_JOB_ID']),'CHECK_PERMISSIONS' => 'N'), false, false);
                if ($arJoborder = $obRes->Fetch()) {

                    $arEventCandidate = array_merge($arEvent, array(
                        'EVENT_NAME' => GetMessage('YNSIR_ADD_ASSOCIATE_OBORDER'),
                        'ENTITY_TYPE' => YNSIR_CANDIDATE,
                        'ENTITY_ID' => intval($arFields['CANDIDATE_ID']),
                        'EVENT_TEXT_1' => $arJoborder['TITLE'],
                    ));
                    $eventID = $YNSIREvent->Add($arEventCandidate, false);
                }
                unset($obRes);
                unset($arJoborder);
                unset($arEventCandidate);
            }
            //end TODO: Save event

            //update by nhatth2
//            Notification
            if($ID > 0 && $notify) {
                $arUserNotifi['HRM'] = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_hr_manager_config'));
                $arUserNotifi['RM'] = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_recruitment_manager_config'));
                if(!is_array($arUserNotifi['HRM'])) $arUserNotifi['HRM'] = array();
                if(!is_array($arUserNotifi['RM'])) $arUserNotifi['RM'] = array();
                //get Job Owner
                $obRes = YNSIRJobOrder::GetList(array(), array('ID'=>intval($arFields['ORDER_JOB_ID'])), false, false);
                if ($arJoborder = $obRes->Fetch()) {
                    $arUserNotifi['JOB_OWNER'] = array(intval($arJoborder[YNSIRJobOrder::JO_ENTITY_OWNER]));
                }
                $arUserNotifi = array_merge($arUserNotifi['HRM'],$arUserNotifi['RM'],$arUserNotifi['JOB_OWNER']);
                $arUserNotifi = array_unique($arUserNotifi);
                $rsUserCreated = CUser::GetByID($iUserId);
                $arUserCreated = $rsUserCreated->Fetch();

                $UserCreatedURL = CComponentEngine::MakePathFromTemplate(
                    '/company/personal/user/#user_id#/',
                    array('user_id' => $iUserId)
                );
                $jobURL = CComponentEngine::MakePathFromTemplate(
                                    '/recruitment/job-order/detail/#job_id#/',
                                    array('job_id' => $arJoborder['ID'])
                                );

                $strUSER_NAME = CUser::FormatName(
                    $sFormatName,
                    array(
                        'LOGIN' => $arUserCreated['LOGIN'],
                        'NAME' => $arUserCreated['NAME'],
                        'LAST_NAME' => $arUserCreated['LAST_NAME'],
                        'SECOND_NAME' => $arUserCreated['SECOND_NAME']
                    ),
                    true, false
                );

                $strUSER_NAME = '<a href="'.$UserCreatedURL.'" title="'.$strUSER_NAME.'">'.$strUSER_NAME.'</a>';
                $JOB_TITLE = '<a href="'.$jobURL.'" title="'.$arJoborder['TITLE'].'">'.$arJoborder['TITLE'].'</a>';

                foreach($arUserNotifi as $iAddressID) {
                    $tag = "YNSIRECRUITMENT|ASSOCIATED|".intval($iAddressID)."|".intval($ID);
                    if(intval($iAddressID) <=0 ) continue;
                    YNSIRNotifier::Notify(
                        intval($iAddressID),
                        GetMessage('YNSIR_NOTIFY_ASSOCIATED', array(
                            '#USER_NAME#' => $strUSER_NAME,
                            '#JOB_TITLE#' => $JOB_TITLE
                        )),
                        GetMessage('YNSIR_NOTIFY_ASSOCIATED', array(
                            '#USER_NAME#' => $strUSER_NAME,
                            '#JOB_TITLE#' => $JOB_TITLE
                        )),
                        YNSIRNotifierSchemeType::Associate,
                        $tag
                    );
                }


            }
            //end update by nhatth
        }
        return $ID;
    }

    public static function Delete($ID = array())
    {
        global $DB;
        $iUserId = YNSIRSecurityHelper::GetCurrentUserID();
        $sFormatName = CSite::GetNameFormat(false);
        $arFields = self::GetByID($ID);
        if (is_array($arFields)) {
            $DB->Query("DELETE FROM " . static::TABLE_NAME . " WHERE ID = {$ID}");

            //Unset assign permission to Candidate
            YNSIRPerms_::DeleteEntity(YNSIR_CANDIDATE, $arFields['CANDIDATE_ID'], YNSIREntityPerms::PERMS_ENTITY_TYPE_ASSOCIATE, $arFields['ORDER_JOB_ID']);

                $arEvent = array(
                    'EVENT_TYPE'  => YNSIREvent::TYPE_ASSOCIATE,
                    'EVENT_TEXT_2' => '',
                    'FILES' => array()
                );

                if (!isset($arEvent['USER_ID'])) {
                    if ($iUserId > 0) {
                        $arEvent['USER_ID'] = $iUserId;
                    } else if (isset($arFields['MODIFIED_BY']) && $arFields['MODIFIED_BY'] > 0) {
                        $arEvent['USER_ID'] = $arFields['MODIFIED_BY'];
                    }
                }
                $YNSIREvent = new YNSIREvent();
                //Order event
                $obRes = YNSIRCandidate::GetList(array(), array('ID'=>intval($arFields['CANDIDATE_ID'])), false, false, array('ID','FIRST_NAME','LAST_NAME'));
                if ($arCandidate = $obRes->Fetch()) {

                    $arCandidate['FULL_NAME'] = CUser::FormatName(
                        $sFormatName,
                        array(
                            "NAME" => $arCandidate['FIRST_NAME'],
                            "LAST_NAME" => $arCandidate['LAST_NAME'],
                        )
                    );
                    $arEventOrder = array_merge($arEvent,array(
                        'EVENT_NAME' => GetMessage('YNSIR_UN_ASSOCIATE_CANDIDATE'),
                        'ENTITY_TYPE' => YNSIR_JOB_ORDER,
                        'ENTITY_ID'  => intval($arFields['ORDER_JOB_ID']),
                        'EVENT_TEXT_1' => $arCandidate['FULL_NAME'],
                    ));
                    $eventID = $YNSIREvent->Add($arEventOrder, false);
                }
                unset($obRes);
                unset($arCandidate);
                unset($arEventOrder);
                //Candidate event
                $obRes = YNSIRJobOrder::GetList(array(), array('ID'=>intval($arFields['ORDER_JOB_ID']),'CHECK_PERMISSIONS' => 'N'), false, false);
                if ($arJoborder = $obRes->Fetch()) {

                    $arEventCandidate = array_merge($arEvent, array(
                        'EVENT_NAME' => GetMessage('YNSIR_UN_ASSOCIATE_JOBORDER'),
                        'ENTITY_TYPE' => YNSIR_CANDIDATE,
                        'ENTITY_ID' => intval($arFields['CANDIDATE_ID']),
                        'EVENT_TEXT_1' => $arJoborder['TITLE'],
                    ));
                    $eventID = $YNSIREvent->Add($arEventCandidate, false);
                }
                unset($obRes);
                unset($arJoborder);
                unset($arEventCandidate);
            return true;
        }
        return false;
    }

    public static function checkCandiateLock($ID){
        $arResult = array();
        $arLock = YNSIRConfig::getCandiateStatusLock();
        $arCandidateRes = YNSIRCandidate::GetByID($ID);
        if(!empty($arCandidateRes)) {
            if (in_array($arCandidateRes['CANDIDATE_STATUS'],$arLock)) {
                return array(
                    'IS_LOCK'=>'Y',
                    'STATUS_ID'=> $arCandidateRes['CANDIDATE_STATUS'],
                    'CANDIDATE_ID'=> $arCandidateRes['CANDIDATE_ID'],
                    'ORDER_JOB_ID'=> '',
                    'ORDER_JOB_TITLE'=> '',
                );
            }
        }
        unset($rsCandidateRes);
        unset($arCandidateRes);
        $dbResultAssociate = YNSIRAssociateJob::GetList(
            array('MODIFIED_DATE' => 'DESC'),
            array('CANDIDATE_ID' => $ID),
            false,
            array(),
            array('STATUS_ID','CANDIDATE_ID', 'ORDER_JOB_ID', 'ORDER_JOB_TITLE','CANDIDATE_STATUS'));
        $is_first = false;
        while ($arCandidateRes = $dbResultAssociate->GetNext()) {

            if (!$is_first) {
                $arResult = array(
                    'IS_LOCK'=>'N',
                    'STATUS_ID'=> $arCandidateRes['STATUS_ID'],
                    'CANDIDATE_ID'=> $arCandidateRes['CANDIDATE_ID'],
                    'ORDER_JOB_ID'=> $arCandidateRes['ORDER_JOB_ID'],
                    'ORDER_JOB_TITLE'=> $arCandidateRes['ORDER_JOB_TITLE'],
                );
                $is_first = true;
            }

            if (in_array($arCandidateRes['STATUS_ID'],$arLock)) {
                $arResult = array(
                    'IS_LOCK'=>'Y',
                    'STATUS_ID'=> $arCandidateRes['STATUS_ID'],
                    'CANDIDATE_ID'=> $arCandidateRes['CANDIDATE_ID'],
                    'ORDER_JOB_ID'=> $arCandidateRes['ORDER_JOB_ID'],
                    'ORDER_JOB_TITLE'=> $arCandidateRes['ORDER_JOB_TITLE'],
                    );
                break;
            } elseif (in_array($arCandidateRes['CANDIDATE_STATUS'],$arLock)) {
                $arResult = array(
                    'IS_LOCK'=>'Y',
                    'STATUS_ID'=> $arCandidateRes['CANDIDATE_STATUS'],
                    'CANDIDATE_ID'=> $arCandidateRes['CANDIDATE_ID'],
                    'ORDER_JOB_ID'=> '',
                    'ORDER_JOB_TITLE'=> '',
                );
                break;
            }
        }

        return $arResult;
    }

    public static function Update($ID = 0, $arFields = array(), $bCompare = true, $bUpdateSearch = true,$options = array())
    {

        global $DB;
        $LAST_ERROR = '';

        $ID = (int)$ID;
        if (!is_array($options)) {
            $options = array();
        }

        $arFilterTmp = array('ID' => $ID);

        $obRes = self::GetList(array(), $arFilterTmp);
        if (!($arRow = $obRes->Fetch()))
            return false;

        if (!isset($arFields['ID'])) {
            $arFields['ID'] = $ID;
        }
        foreach ($arRow as &$VALUE) {
            if (!isset($VALUE) || $VALUE == null) {
                $VALUE = '';
            }
        }
        unset($VALUE);

        $iUserId = YNSIRSecurityHelper::GetCurrentUserID();
        $arEvents = array();
        if ($bCompare) {
            $arEvents = self::CompareFields($arRow, $arFields);
        }
        foreach ($arFields as $IDX => $field) {
            if (strlen($field) > 0)
                $arFieldsInsert[$IDX] = "'" . $DB->ForSql($field) . "'";

        }
        if (isset($arFields['CREATED_DATE'])) {
            unset($arFields['CREATED_DATE']);
        }

        if (isset($arFields['MODIFIED_DATE'])) {
            unset($arFields['MODIFIED_DATE']);
        }

        $arFields['~MODIFIED_DATE'] = $DB->CurrentTimeFunction();

        if (!isset($arFields['MODIFIED_BY']) || $arFields['MODIFIED_BY'] <= 0) {
            $arFields['MODIFIED_BY'] = $iUserId;
        }
        $sUpdate = $DB->PrepareUpdate('b_ynsir_associate_job', $arFields);

        if (strlen($sUpdate) > 0) {
            $DB->Query("UPDATE b_ynsir_associate_job SET {$sUpdate} WHERE ID = {$ID}", false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);
            //Update Event Candidate

            foreach ($arEvents as $arEvent) {
                $arEvent['ENTITY_TYPE'] = 'CANDIDATE';
                $arEvent['ENTITY_ID'] = intval($arRow['CANDIDATE_ID']);
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
            $bResult = true;
        }
        return $bResult;
    }

    public static function CompareFields($arFieldsOrig, $arFieldsModif, $bCheckPerms = true, $arOptions = null)
    {
        global $DB, $USER;

        if (!is_array($arOptions)) {
            $arOptions = array();
        }
        $arFieldCompare = YNSIRConfig::getFieldAssociate();

        $arMsg = Array();

        foreach ($arFieldsModif as $KEY => $newValue) {
            if (key_exists($KEY, $arFieldCompare) &&
                isset($arFieldsOrig[$KEY]) && isset($arFieldsModif[$KEY])
                && $arFieldsOrig[$KEY] != $arFieldsModif[$KEY]
            ) {
                switch ($KEY) {
                    case 'STATUS_ID':
                        $typeListAssociate = YNSIRGeneral::getListJobStatus('CANDIDATE_STATUS');
                        $arFieldsOrig[$KEY] =  !empty($arFieldsOrig[$KEY]) ? $typeListAssociate[$arFieldsOrig[$KEY]] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY");
                        $arFieldsModif[$KEY] = !empty($arFieldsModif[$KEY]) ? $typeListAssociate[$arFieldsModif[$KEY]] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY");
                        break;
                    case "STATUS_ROUND_ID":
                        $arFilterRoundId = array();
                        if(intval($arFieldsOrig[$KEY]) > 0) $arFilterRoundId[] = intval($arFieldsOrig[$KEY]);
                        if(intval($arFieldsModif[$KEY]) > 0) $arFilterRoundId[] = intval($arFieldsModif[$KEY]);
                        $obRes = YNSIRInterview::GetList(array(), array('ID' => $arFilterRoundId), false, false, array());
                        while ($arInterview = $obRes->Fetch()) {
                            $arRound[$arInterview['ID']] = $arInterview;
                        }
                        unset($arInterview);
                        unset($obRes);
                        $arFieldsOrig[$KEY] =  !empty($arRound[$arFieldsOrig[$KEY]]) ? GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $arRound[$arFieldsOrig[$KEY]]['ROUND_INDEX'])) : GetMessage("YNSIR_FIELD_COMPARE_EMPTY");
                        $arFieldsModif[$KEY] = !empty($arRound[$arFieldsModif[$KEY]]) ? GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $arRound[$arFieldsModif[$KEY]]['ROUND_INDEX']))  : GetMessage("YNSIR_FIELD_COMPARE_EMPTY");
                        break;
                    default:
                        break;
                }
                $arMsg[] = Array(
                    'ENTITY_FIELD' => $KEY,
                    'EVENT_NAME' => $arFieldsOrig['ORDER_JOB_TITLE'].': '.GetMessage('YNSIR_FIELD_COMPARE',
                        array('#FIELD#' => $arFieldCompare[$KEY])),
                    'EVENT_TEXT_1' => !empty($arFieldsOrig[$KEY]) ? $arFieldsOrig[$KEY] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                    'EVENT_TEXT_2' => !empty($arFieldsModif[$KEY]) ? $arFieldsModif[$KEY] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                );
            }

        }
        return $arMsg;
    }

    public static function GetByID($ID = 0)
    {
        $version = self::GetList(array(), array('ID' => $ID));
        return $version->Fetch();
    }

    public static function GetList($arOrder = array('ID'=>'ASC'), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
    {
        $arField = self::GetFields();

        $lb = new YNSIRSQLHelper(
            YNSIRAssociateJob::DB_TYPE,
            YNSIRAssociateJob::TABLE_NAME,
            YNSIRAssociateJob::TABLE_ALIAS,
            $arField,
            '',
            '',
            array('YNSIRAssociateJob', 'BuildPermSql')
        );

        $arSelectFields_ = array_merge(array_keys($arField), $arSelectFields);
        return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields_, $arOptions);
    }

    public static function PrepareJoin($userID, $ownerTypeID, $ownerAlias, $alias = '', $userAlias = '', $respAlias = '')
    {
        $userID = intval($userID);
        $ownerTypeID = intval($ownerTypeID);
        $ownerAlias = strval($ownerAlias);
        if ($ownerAlias === '') {
            $ownerAlias = 'L';
        }

        $alias = strval($alias);
        if ($alias === '') {
            $alias = 'A';
        }

        $userAlias = strval($userAlias);
        if ($userAlias === '') {
            $userAlias = 'UA';
        }

        $respAlias = strval($respAlias);

        // Zero user is intended for nearest activity in general.
        $userTableName = YNSIRActivity::USER_ACTIVITY_TABLE_NAME;
        $activityTableName = YNSIRActivity::TABLE_NAME;
        if ($respAlias !== '') {
            return "LEFT JOIN {$userTableName} {$userAlias} ON {$userAlias}.USER_ID = {$userID} AND {$userAlias}.OWNER_ID = {$ownerAlias}.ID AND {$userAlias}.OWNER_TYPE_ID = {$ownerTypeID} LEFT JOIN {$activityTableName} {$alias} ON {$alias}.ID = {$userAlias}.ACTIVITY_ID LEFT JOIN b_user {$respAlias} ON {$alias}.RESPONSIBLE_ID = {$respAlias}.ID";
        } else {
            return "LEFT JOIN {$userTableName} {$userAlias} ON {$userAlias}.USER_ID = {$userID} AND {$userAlias}.OWNER_ID = {$ownerAlias}.ID AND {$userAlias}.OWNER_TYPE_ID = {$ownerTypeID} LEFT JOIN {$activityTableName} {$alias} ON {$alias}.ID = {$userAlias}.ACTIVITY_ID";
        }
    }

    public static function OnUpdateStatusOssociate($ID,$arData,$iUserId) {
        $sFormatName = CSite::GetNameFormat(false);
        if(key_exists('STATUS_ID',$arData)) {
            $arFilterChangeStatus = array('ID' => $ID);
            $obRes = YNSIRAssociateJob::GetList(array(), $arFilterChangeStatus, false, false, array('ID'));
            if (!$arAssociate = $obRes->Fetch()) {
                return;
            }
            $arUserNotifi['HRM'] = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_hr_manager_config'));
            $arUserNotifi['RM'] = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_recruitment_manager_config'));

            if(!is_array($arUserNotifi['HRM'])) $arUserNotifi['HRM'] = array();
            if(!is_array($arUserNotifi['RM'])) $arUserNotifi['RM'] = array();
            //get Job Owner
            $obRes = YNSIRJobOrder::getList(array(), array('ID'=>intval($arAssociate['ORDER_JOB_ID']),'CHECK_PERMISSIONS' => 'N'), false, false);
            if ($arJoborder = $obRes->Fetch()) {
                $arUserNotifi['JOB_OWNER'] = array(intval($arJoborder[YNSIRJobOrder::JO_ENTITY_OWNER]));
            }
            //get Candidate
            $obCaRes = YNSIRCandidate::GetList(array(), array('ID'=>intval($arAssociate['CANDIDATE_ID']),'CHECK_PERMISSIONS' => 'N'), false, false);
            if (!$arCandidate = $obCaRes->Fetch()) {
                return;
            }

            $arUserNotifi = array_merge($arUserNotifi['HRM'],$arUserNotifi['RM'],$arUserNotifi['JOB_OWNER']);
            $arUserNotifi = array_unique($arUserNotifi);
            $rsUserCreated = CUser::GetByID($iUserId);
            $arUserCreated = $rsUserCreated->Fetch();

            $UserCreatedURL = CComponentEngine::MakePathFromTemplate(
                '/company/personal/user/#user_id#/',
                array('user_id' => $iUserId)
            );
            $jobURL = CComponentEngine::MakePathFromTemplate(
                '/recruitment/job-order/detail/#job_id#/',
                array('job_id' => $arJoborder['ID'])
            );
            $candidateURL = CComponentEngine::MakePathFromTemplate(
                '/recruitment/candidate/detail/#candidate_id#/',
                array('candidate_id' => $arCandidate['ID'])
            );

            $strUSER_NAME = CUser::FormatName(
                $sFormatName,
                array(
                    'LOGIN' => $arUserCreated['LOGIN'],
                    'NAME' => $arUserCreated['NAME'],
                    'LAST_NAME' => $arUserCreated['LAST_NAME'],
                    'SECOND_NAME' => $arUserCreated['SECOND_NAME']
                ),
                true, false
            );
            $strCandidate = CUser::FormatName(
                $sFormatName,
                array(
                    "NAME" => $arCandidate['FIRST_NAME'],
                    "LAST_NAME" => $arCandidate['LAST_NAME'],
                )
            );

            $strUSER_NAME = '<a href="'.$UserCreatedURL.'" title="'.$strUSER_NAME.'">'.$strUSER_NAME.'</a>';
            $JOB_TITLE = '<a href="'.$jobURL.'" title="'.$arJoborder['TITLE'].'">'.$arJoborder['TITLE'].'</a>';
            $CANDIDATE_NAME = '<a href="'.$candidateURL.'" title="'.$strCandidate.'">'.$strCandidate.'</a>';
            $strMessage = '';
            $arCANDIDATE_STATUS = Unserialize(COption::GetOptionString("ynsirecruitment", "ynsir_candidate_status"));
            if(!isset($arCANDIDATE_STATUS['ACCEPT_OFFER_STATUS']) || !isset($arCANDIDATE_STATUS['REJECT_OFFER_STATUS'])) {
                return;
            }
            switch ($arData['STATUS_ID']) {
                case $arCANDIDATE_STATUS['ACCEPT_OFFER_STATUS']:
                    //Accept Job offer
                    $strMessage = GetMessage('YNSIR_NOTIFY_STATUS_ACCEPT_OFFER', array(
                        '#CANDIDATE_NAME#' => $CANDIDATE_NAME,
                        '#JOB_TITLE#' => $JOB_TITLE
                    ));
                    break;
                case $arCANDIDATE_STATUS['REJECT_OFFER_STATUS']:
                    $strMessage = GetMessage('YNSIR_NOTIFY_STATUS_REJECT_OFFER', array(
                        '#CANDIDATE_NAME#' => $CANDIDATE_NAME,
                        '#JOB_TITLE#' => $JOB_TITLE
                    ));
                    //Reject Job offer
                    break;
                default:
                    return;
            }

            foreach($arUserNotifi as $iAddressID) {
                $tag = "YNSIRECRUITMENT|CHANGE_STATUS|".intval($iAddressID)."|".intval($ID)."|".$arData['STATUS_ID'];
                if(intval($iAddressID) <=0 ) continue;
                YNSIRNotifier::Notify(
                    intval($iAddressID),
                    $strMessage,
                    $strMessage,
                    YNSIRNotifierSchemeType::UpdateCandidateStatus,
                    $tag
                );
            }
        }
    }
}
?>