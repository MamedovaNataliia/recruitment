<?
use EbolaRecruiting\CandidateMultipleField;
use Bitrix\Main\Loader;

class YNSIRCandidate
{
    const TABLE_NAME = 'b_ynsir_candidate';
    const DB_TYPE = 'MYSQL';
    const TABLE_ALIAS = 'YC';
    protected static $TYPE_NAME = YNSIR_CANDIDATE;
    const ACTIVITY_CACHE_TIME = 86400;
    const ACTIVITY_CACHE_URL = '/ynsirecruitment/candidate/';
    const FREFIX_CACHE = 'RECRUITMENT_CANDIDATE_';
    private static $FIELDS = null;
    private static $FIELD_CANDIDATE = null;
    private static $ERRORS = array();


    static function GetCandidateFields($arOptions = null)
    {
        global $USER;
        if (!isset(self::$FIELD_CANDIDATE)) {
            self::$FIELD_CANDIDATE = array(
                "ID"               => array('FIELD' => 'YC.ID', 'TYPE' => 'string'),
                "MODIFIED_DATE"    => array('FIELD' => 'YC.MODIFIED_DATE', 'TYPE' => 'datetime'),
                "CREATED_DATE"     => array('FIELD' => 'YC.CREATED_DATE', 'TYPE' => 'datetime'),
                "USER_ID"          => array('FIELD' => 'YC.USER_ID', 'TYPE' => 'string'),
                "CREATED_BY"       => array('FIELD' => 'YC.CREATED_BY', 'TYPE' => 'string'),
                "MODIFIED_BY"      => array('FIELD' => 'YC.MODIFIED_BY', 'TYPE' => 'string'),
                "DOB"              => array('FIELD' => 'YC.DOB', 'TYPE' => 'date'),
                "SALT_NAME"        => array('FIELD' => 'YC.SALT_NAME', 'TYPE' => 'string'),
                "FIRST_NAME"       => array('FIELD' => 'YC.FIRST_NAME', 'TYPE' => 'string'),
                "LAST_NAME"        => array('FIELD' => 'YC.LAST_NAME', 'TYPE' => 'string'),
                "GENDER"           => array('FIELD' => 'YC.GENDER', 'TYPE' => 'string'),
//                "MARITAL_STATUS" => array('FIELD' => 'YC.MARITAL_STATUS', 'TYPE' => 'string'),
//                "PHONE" => array('FIELD' => 'YC.PHONE', 'TYPE' => 'string'),
                "WEBSITE"          => array('FIELD' => 'YC.WEBSITE', 'TYPE' => 'string'),
                "EXPERIENCE"       => array('FIELD' => 'YC.EXPERIENCE', 'TYPE' => 'string'),
                //    "HIGHEST_OBTAINED_DEGREE" => array('FIELD' => 'YC.HIGHEST_OBTAINED_DEGREE', 'TYPE' => 'string'),
//                "CURRENT_JOB_TITLE" => array('FIELD' => 'YC.CURRENT_JOB_TITLE', 'TYPE' => 'string'),
                "CURRENT_EMPLOYER" => array('FIELD' => 'YC.CURRENT_EMPLOYER', 'TYPE' => 'string'),
                "EXPECTED_SALARY"  => array('FIELD' => 'YC.EXPECTED_SALARY', 'TYPE' => 'string'),
                "CURRENT_SALARY"   => array('FIELD' => 'YC.CURRENT_SALARY', 'TYPE' => 'string'),
                "SKILL_SET"        => array('FIELD' => 'YC.SKILL_SET', 'TYPE' => 'string'),
                "ADDITIONAL_INFO"  => array('FIELD' => 'YC.ADDITIONAL_INFO', 'TYPE' => 'string'),
                "SKYPE_ID"         => array('FIELD' => 'YC.SKYPE_ID', 'TYPE' => 'string'),
                "TWITTER"          => array('FIELD' => 'YC.TWITTER', 'TYPE' => 'string'),
                "CANDIDATE_STATUS" => array('FIELD' => 'YC.CANDIDATE_STATUS', 'TYPE' => 'string'),
                "SOURCE_BY"        => array('FIELD' => 'YC.SOURCE_BY', 'TYPE' => 'string'),
                "CANDIDATE_OWNER"  => array('FIELD' => 'YC.CANDIDATE_OWNER', 'TYPE' => 'string'),
                "OMOBILE"          => array('FIELD' => 'YC.OMOBILE', 'TYPE' => 'string'),
                "EMAIL_OPT_OUT"    => array('FIELD' => 'YC.EMAIL_OPT_OUT', 'TYPE' => 'string'),
                //update by nhatth2
                "FACEBOOK"         => array('FIELD' => 'YC.FACEBOOK', 'TYPE' => 'string'),
                "LINKEDIN"         => array('FIELD' => 'YC.LINKEDIN', 'TYPE' => 'string'),
            );
            $additionalFields = isset($arOptions['ADDITIONAL_FIELDS'])
                ? $arOptions['ADDITIONAL_FIELDS'] : null;

            if (is_array($additionalFields)) {
                if (in_array('ACTIVITY', $additionalFields, true)) {
                    $commonActivityJoin = YNSIRActivity::PrepareJoin(0, YNSIROwnerType::Candidate, self::TABLE_ALIAS,
                        'AC', 'UAC', 'ACUSR');

                    self::$FIELD_CANDIDATE['C_ACTIVITY_ID'] = array(
                        'FIELD' => 'UAC.ACTIVITY_ID',
                        'TYPE'  => 'int',
                        'FROM'  => $commonActivityJoin
                    );
                    self::$FIELD_CANDIDATE['C_ACTIVITY_TIME'] = array(
                        'FIELD' => 'UAC.ACTIVITY_TIME',
                        'TYPE'  => 'datetime',
                        'FROM'  => $commonActivityJoin
                    );
                    self::$FIELD_CANDIDATE['C_ACTIVITY_SUBJECT'] = array(
                        'FIELD' => 'AC.SUBJECT',
                        'TYPE'  => 'string',
                        'FROM'  => $commonActivityJoin
                    );
                    self::$FIELD_CANDIDATE['C_ACTIVITY_RESP_ID'] = array(
                        'FIELD' => 'AC.RESPONSIBLE_ID',
                        'TYPE'  => 'int',
                        'FROM'  => $commonActivityJoin
                    );
                    self::$FIELD_CANDIDATE['C_ACTIVITY_RESP_LOGIN'] = array(
                        'FIELD' => 'ACUSR.LOGIN',
                        'TYPE'  => 'string',
                        'FROM'  => $commonActivityJoin
                    );
                    self::$FIELD_CANDIDATE['C_ACTIVITY_RESP_NAME'] = array(
                        'FIELD' => 'ACUSR.NAME',
                        'TYPE'  => 'string',
                        'FROM'  => $commonActivityJoin
                    );
                    self::$FIELD_CANDIDATE['C_ACTIVITY_RESP_LAST_NAME'] = array(
                        'FIELD' => 'ACUSR.LAST_NAME',
                        'TYPE'  => 'string',
                        'FROM'  => $commonActivityJoin
                    );
                    self::$FIELD_CANDIDATE['C_ACTIVITY_RESP_SECOND_NAME'] = array(
                        'FIELD' => 'ACUSR.SECOND_NAME',
                        'TYPE'  => 'string',
                        'FROM'  => $commonActivityJoin
                    );

                    $userID = $USER->GetbyID();
                    if ($userID > 0) {
                        $activityJoin = YNSIRActivity::PrepareJoin($userID, YNSIROwnerType::Candidate,
                            self::TABLE_ALIAS, 'A', 'UA', '');

                        self::$FIELD_CANDIDATE['ACTIVITY_ID'] = array(
                            'FIELD' => 'UA.ACTIVITY_ID',
                            'TYPE'  => 'int',
                            'FROM'  => $activityJoin
                        );
                        self::$FIELD_CANDIDATE['ACTIVITY_TIME'] = array(
                            'FIELD' => 'UA.ACTIVITY_TIME',
                            'TYPE'  => 'datetime',
                            'FROM'  => $activityJoin
                        );
                        self::$FIELD_CANDIDATE['ACTIVITY_SUBJECT'] = array(
                            'FIELD' => 'A.SUBJECT',
                            'TYPE'  => 'string',
                            'FROM'  => $activityJoin
                        );
                    }
                }
            }

        }
        return self::$FIELD_CANDIDATE;
    }

    public static function Add($arFields = array(), $arFieldsMulti = array())
    {
        global $DB;
        try {
            $bLoader = Loader::includeModule('ebola.recruiting');
            
            $arRightFields = self::GetCandidateFields();
            $permissionEntityType = YNSIR_PERM_ENTITY_CANDIDATE;
            $iUserId = YNSIRSecurityHelper::GetCurrentUserID();
            $iResult = 0;
            //Permission Add
            $arEntityAttr = self::BuildEntityAttr($iUserId, array());
            $userPerms = YNSIRPerms_::GetUserPermissions($iUserId);

            //ASSIGN by ID
            $assignedByID = (int)$arFields['CANDIDATE_OWNER'];
            $arEntityAttr = self::BuildEntityAttr($assignedByID, array());
            $userPerms = $assignedByID != YNSIRPerms_::GetCurrentUserID() ? YNSIRPerms_::GetUserPermissions($assignedByID) : $userPerms;
            $sEntityPerm = $userPerms->GetPermType($permissionEntityType, 'ADD', $arEntityAttr);

            self::PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

            foreach ($arFields as $IDX => $field) {
                if ($arRightFields[$IDX]) {
                    if (strlen($field) > 0) {
                        $arFieldsInsert[$IDX] = "'" . $DB->ForSql($field) . "'";
                    }
                }

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
				
                $candidateId = $DB->Insert(static::TABLE_NAME, $arFieldsInsert);
				
                YNSIRPerms_::UpdateEntityAttr($permissionEntityType, $candidateId, $arEntityAttr);

                if ($candidateId > 0 && $bLoader) {
                    foreach ($arFieldsMulti as $k => $v) {
                        $arrField = [
                            'CONTENT' => $v['CONTENT'],
                            'TYPE'    => $v['TYPE']
                        ];
                        CandidateMultipleField::addMultipleFields($candidateId, $arrField);
                    }
                }
            }
        } catch (\Exception $exception) {
			AddMessage2Log( $exception->getMessage());
        }
        return $candidateId;
    }

    public static function PrepareEntityAttrs(&$arEntityAttr, $entityPermType)
    {
        // Ensure that entity accessable for user restricted by BX_CRM_PERM_OPEN
        if ($entityPermType === BX_CRM_PERM_OPEN && !in_array('O', $arEntityAttr, true)) {
            $arEntityAttr[] = 'O';
        }
    }


    static public function BuildEntityAttr($userID, $arAttr = array())
    {
        $userID = (int)$userID;
        $arResult = array("U{$userID}");
        if (isset($arAttr['OPENED']) && $arAttr['OPENED'] == 'Y') {
            $arResult[] = 'O';
        }

        $stageID = isset($arAttr['STAGE_ID']) ? $arAttr['STAGE_ID'] : '';
        if ($stageID !== '') {
            $arResult[] = "STAGE_ID{$stageID}";
        }

        $arUserAttr = YNSIRPerms_::BuildUserEntityAttr($userID);
        return array_merge($arResult, $arUserAttr['INTRANET']);
    }

    public static function Delete($arId = array())
    {
        global $DB;
        $permissionEntityType = YNSIR_PERM_ENTITY_CANDIDATE;
        if (!empty($arId)) {
            $sId = implode(', ', $arId);
            $DB->Query("DELETE FROM " . static::TABLE_NAME . " WHERE ID IN ({$sId})");
            $DB->Query("DELETE FROM b_ynsir_file WHERE CANDIDATE_ID IN ({$sId})");
            $DB->Query("DELETE FROM b_ynsir_candidate_field_multiple WHERE CANDIDATE_ID IN ({$sId})");
            $DB->Query("DELETE FROM b_ynsir_entity_perms WHERE ENTITY='" . $permissionEntityType . "' AND ENTITY_ID IN ({$sId})",
                false, 'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__);

            //Associate
            //remove Associate
            $obRes = YNSIRAssociateJob::GetList(array(), array('CANDIDATE_ID' => $arId), false, false, array('ID'));
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
    }

    public static function Update($iID = 0, $arFields = array(), $bCompare = true, $arFieldsMulti)
    {
        if ($iID <= 0) {
            return false;
        }
        global $DB, $USER;
        $iUserId = $USER::GetId();
        $permissionEntityType = YNSIR_PERM_ENTITY_CANDIDATE;
        $candidateId = 0;
        $arFilterTmp = array('ID' => $iID);
        $obRes = self::GetListCandidate(array(), $arFilterTmp);
        if (!($arRow = $obRes->Fetch())) {
            return false;
        }


        if (!isset($arFields['ID'])) {
            $arFields['ID'] = $iID;
        }
        foreach ($arRow as &$VALUE) {
            if (!isset($VALUE) || $VALUE == null) {
                $VALUE = '';
            }
        }
        unset($VALUE);
        $sonetEventData = array();
        //TODO Compare Common fields
        if ($bCompare) {
            $compareOptions = array();
            $arEvents = self::CompareFields($arRow, $arFields);
            foreach ($arEvents as $arEvent) {
                $arEvent['ENTITY_TYPE'] = 'CANDIDATE';
                $arEvent['ENTITY_ID'] = $iID;
                $arEvent['EVENT_TYPE'] = 1;

                if (!isset($arEvent['USER_ID'])) {
                    if ($iUserId > 0) {
                        $arEvent['USER_ID'] = $iUserId;
                    } else {
                        if (isset($arFields['MODIFIED_BY']) && $arFields['MODIFIED_BY'] > 0) {
                            $arEvent['USER_ID'] = $arFields['MODIFIED_BY'];
                        }
                    }
                }

                $YNSIREvent = new YNSIREvent();
                $eventID = $YNSIREvent->Add($arEvent, false);
            }
            unset($arEvent);
            unset($arEvents);
            unset($YNSIREvent);
        }

        //PERMISSION CANDIDATE_OWNER
        $assignedByID = (int)$arFields['CANDIDATE_OWNER'];
        $arEntityAttr = self::BuildEntityAttr($assignedByID, array());
        $userPerms = YNSIRPerms_::GetUserPermissions($assignedByID);
        $sEntityPerm = $userPerms->GetPermType($permissionEntityType, 'WRITE', $arEntityAttr);

        foreach ($arFields as $IDX => $field) {
            if (strlen($field) > 0) {
                $arFields[$IDX] = "'" . $DB->ForSql($field) . "'";
            }
        }

        self::PrepareEntityAttrs($arEntityAttr, $sEntityPerm);


        if (isset($arFields['MODIFIED_DATE'])) {
            unset($arFields['MODIFIED_DATE']);
        }

        if (isset($arFields['CREATED_DATE'])) {
            unset($arFields['CREATED_DATE']);
        }


        $arFields['MODIFIED_DATE'] = $DB->CurrentTimeFunction();
        //TODO: get OLD multiple VALUES
//        $rsList = YNSIRTypelist::GetList(array('ID' => "ASC"), array(), false);
//        while ($element_list = $rsList->GetNext()) {
//            $TYPELIST[$element_list['ENTITY']][$element_list['ID']] = $element_list['NAME_' . strtoupper(LANGUAGE_ID)];
//        }
//        unset($rsList);
//      GET FROM CACHE

        $TYPELIST = YNSIRConfig::GetListTypeList();
        foreach ($TYPELIST as $KEY_TYPE => $arItems) {
            foreach ($arItems as $item_key => $item) {
                $TYPELIST[$KEY_TYPE][$item_key] = $item['NAME_' . strtoupper(LANGUAGE_ID)];
            }
            unset($item);
        }
        unset($KEY_TYPE);
        unset($arItems);

        $dbMultiField = YNSIRCandidate::GetListMultiField(array(), array('CANDIDATE_ID' => $iID));
        while ($multiField = $dbMultiField->GetNext()) {
            //Get NAME for Content if Field In List
            $originContent = $multiField['CONTENT'];
            if (strlen($TYPELIST[$multiField['TYPE']][$multiField['CONTENT']]) > 0) {
                $multiField['CONTENT'] = $TYPELIST[$multiField['TYPE']][$multiField['CONTENT']];
            }

            $arOldMultiField[$multiField['TYPE']][$originContent] = $multiField;
        }
        unset($dbMultiField);
        unset($multiField);
        //end get OLD multiple values

        //TODO: normalize arFieldMulti New for tracking

        $arNewMultiField = self::normalizeMutible($arFieldsMulti, $TYPELIST);

        //End normalize arFieldMulti New for tracking

        if (!empty($arFields) && $iID > 0) {
            $candidateId = self::UpdateDB(static::TABLE_NAME, $arFields, "WHERE ID='" . $iID . "'");
            if ($candidateId > 0) {
                YNSIRPerms_::UpdateEntityAttr($permissionEntityType, $iID, $arEntityAttr);
                foreach ($arFieldsMulti as $k => $v) {
                    $z = $DB->Query("DELETE FROM b_ynsir_candidate_field_multiple WHERE CANDIDATE_ID = {$iID} AND TYPE = '{$v['TYPE']}' ");
                    $insertField = array();
                    $insertField['CANDIDATE_ID'] = $iID;
                    $insertField['TYPE'] = '"' . $v['TYPE'] . '"';
                    $insertField['ADDITIONAL_VALUE'] = '"' . $v['ADDITIONAL_VALUE'] . '"';
                    $insertField['ADDITIONAL_TYPE'] = '"' . $v['ADDITIONAL_TYPE'] . '"';
                    foreach ($v['CONTENT'] as $key => $value) {
                        if ($value == -1) {
                            continue;
                        }
                        if (strlen($value) > 0) {
                            $insertField['CONTENT'] = '"' . $value . '"';
                            $DB->Insert('b_ynsir_candidate_field_multiple', $insertField);
                        }
                    }
                }
                //TODO Compare Multiple Fields
                if ($bCompare) {
                    $arEvents = self::CompareMultipleFields($arOldMultiField, $arNewMultiField, $TYPELIST);
                    foreach ($arEvents as $arEvent) {
                        $arEvent['ENTITY_TYPE'] = 'CANDIDATE';
                        $arEvent['ENTITY_ID'] = $iID;
                        $arEvent['EVENT_TYPE'] = 1;

                        if (!isset($arEvent['USER_ID'])) {
                            if ($iUserId > 0) {
                                $arEvent['USER_ID'] = $iUserId;
                            } else {
                                if (isset($arFields['MODIFIED_BY']) && $arFields['MODIFIED_BY'] > 0) {
                                    $arEvent['USER_ID'] = $arFields['MODIFIED_BY'];
                                }
                            }
                        }

                        $YNSIREvent = new YNSIREvent();
                        $eventID = $YNSIREvent->Add($arEvent, false);
                    }
                    unset($arEvent);
                    unset($arEvents);
                    unset($YNSIREvent);
                }
            }
        }
        return $candidateId;
    }

    function UpdateDB($table,
                      $arFields,
                      $WHERE = "",
                      $error_position = "",
                      $DEBUG = false,
                      $ignore_errors = false,
                      $additional_check = true
    ) {
        global $DB;
        $rows = 0;
        if (is_array($arFields)) {
            $ar = array();
            foreach ($arFields as $field => $value) {
                if (strlen($value) <= 0) {
                    $ar[] = "`" . $field . "` = NULL";
                } else {
                    $ar[] = "`" . $field . "` = " . $value . "";
                }
            }

            if (!empty($ar)) {
                $strSql = "UPDATE " . $table . " SET " . implode(", ", $ar) . " " . $WHERE;
                if ($DEBUG) {
                    echo "<br>" . htmlspecialcharsEx($strSql) . "<br>";
                }
                $w = $DB->Query($strSql, $ignore_errors, $error_position);
                if (is_object($w)) {
                    $rows = $w->AffectedRowsCount();
                    if ($DEBUG) {
                        echo "affected_rows = " . $rows . "<br>";
                    }

                    if ($rows <= 0 && $additional_check) {
                        $w = $DB->Query("SELECT 'x' FROM " . $table . " " . $WHERE, $ignore_errors, $error_position);
                        if (is_object($w)) {
                            if ($w->Fetch()) {
                                $rows = $w->SelectedRowsCount();
                            }
                            if ($DEBUG) {
                                echo "num_rows = " . $rows . "<br>";
                            }
                        }
                    }
                }
            }
        }
        return $rows;
    }

    public static function CompareMultipleFields($arOldValue = array(), $arNewValue = array(), $TYPELIST)
    {

        global $DB;
        $FORMAT_DB_TIME = 'YYYY-MM-DD';
        $FORMAT_DB_BX_SHORT = CSite::GetDateFormat("SHORT");

        if (!is_array($arOldValue)) {
            $arOldValue = array();
        }
        if (!is_array($arNewValue)) {
            $arNewValue = array();
        }
        $arMsg = array();
        $arFieldCompare = YNSIRConfig::getFieldsCandidate();

        //TODO: Check field removed
        $arKeyRemoved = array_diff_key($arOldValue, $arNewValue);
        foreach ($arKeyRemoved as $FIELD_KEY => $arField) {
            //get Content removed
            $RemoveAdditionalValues = '';
            foreach ($arField as $CONTENT_KEY => $arContent) {
                $RemoveAdditionalValues .= $arContent['CONTENT'];
                $RemoveAdditionalValue = strlen($arContent['ADDITIONAL_VALUE']) > 0 ? $arContent['ADDITIONAL_VALUE'] : '';
                if (strlen($RemoveAdditionalValue > 0)) {
                    if ($arContent['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                        $RemoveAdditionalValues .= ' (' . $DB->FormatDate($RemoveAdditionalValue, $FORMAT_DB_TIME,
                                $FORMAT_DB_BX_SHORT) . ')';
                    } else {
                        $RemoveAdditionalValues .= ' (' . $RemoveAdditionalValue . ')';
                    }
                }
                //separate each item
                $RemoveAdditionalValues .= ', ';
            }
            unset($CONTENT_KEY);
            unset($arContent);

            if (!empty($arKeyRemoved)) {
                $arMsg[] = Array(
                    'ENTITY_FIELD' => $FIELD_KEY,
                    'EVENT_NAME'   => GetMessage('YNSIR_FIELD_COMPARE',
                        array('#FIELD#' => $arFieldCompare[$FIELD_KEY])),
                    'EVENT_TEXT_1' => rtrim($RemoveAdditionalValues, ", "),
                    'EVENT_TEXT_2' => GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                );
            }
        }
        unset($FIELD_KEY);
        unset($arField);

        //TODO: Check field added
        $arKeyAdded = array_diff_key($arNewValue, $arOldValue);
        foreach ($arKeyAdded as $FIELD_KEY => $arField) {
            //get Content removed
            $newAddedAdditionalValues = '';
            foreach ($arField as $CONTENT_KEY => $arContent) {
                $newAddedAdditionalValues .= $arContent['CONTENT'];
                $newAddedAdditionalValue = strlen($arContent['ADDITIONAL_VALUE']) > 0 ? $arContent['ADDITIONAL_VALUE'] : '';
                if (strlen($newAddedAdditionalValue > 0)) {
                    if ($arContent['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                        $newAddedAdditionalValues .= ' (' . $DB->FormatDate($newAddedAdditionalValue, $FORMAT_DB_TIME,
                                $FORMAT_DB_BX_SHORT) . ')';
                    } else {
                        $newAddedAdditionalValues .= ' (' . $newAddedAdditionalValue . ')';
                    }
                }
                //separate each item
                $newAddedAdditionalValues .= ', ';
            }
            unset($CONTENT_KEY);
            unset($arContent);

            if (!empty($arKeyAdded)) {
                $arMsg[] = Array(
                    'ENTITY_FIELD' => $FIELD_KEY,
                    'EVENT_NAME'   => GetMessage('YNSIR_FIELD_COMPARE',
                        array('#FIELD#' => $arFieldCompare[$FIELD_KEY])),
                    'EVENT_TEXT_1' => GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                    'EVENT_TEXT_2' => rtrim($newAddedAdditionalValues, ", "),
                );
            }
        }
        unset($FIELD_KEY);
        unset($arField);

        //TODO: Check ADD or REMOVE in each FIELD
        $arKey = array_intersect_key($arNewValue, $arOldValue);
        foreach ($arKey as $FIELD_KEY => $arField) {
            //TODO: Check field in list and single field
            if (count(array_intersect_key($TYPELIST[$FIELD_KEY], $arOldValue[$FIELD_KEY])) > 0
                && count($arField) <= 1
                && count($arOldValue[$FIELD_KEY]) <= 1
            ) {

                $arKeyValueRemoved = array_diff_key($arOldValue[$FIELD_KEY], $arField);

                $arKeyValueAdded = array_diff_key($arField, $arOldValue[$FIELD_KEY]);

                //break if not change CONTENT;
                if (!empty($arKeyValueRemoved) && !empty($arKeyValueAdded)) {

                    $arKeyValueRemoved = array_values($arKeyValueRemoved)[0];
                    $arKeyValueAdded = array_values($arKeyValueAdded)[0];

                    //NEW VALUE:Check if type is date
                    $newAdditionalValue = strlen($arKeyValueAdded['ADDITIONAL_VALUE']) > 0 ? $arKeyValueAdded['ADDITIONAL_VALUE'] : '';
                    if (strlen($newAdditionalValue > 0)) {
                        if ($arKeyValueAdded['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                            $newAdditionalValue = ' (' . $DB->FormatDate($newAdditionalValue, $FORMAT_DB_TIME,
                                    $FORMAT_DB_BX_SHORT) . ')';
                        } else {
                            $newAdditionalValue = ' (' . $newAdditionalValue . ')';
                        }
                    }

                    //OLD VALUE: Check if type is date
                    $oldAdditionalValue = strlen($arKeyValueRemoved['ADDITIONAL_VALUE']) > 0 ? $arKeyValueRemoved['ADDITIONAL_VALUE'] : '';
                    if (strlen($oldAdditionalValue > 0)) {
                        if ($arKeyValueRemoved['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                            $oldAdditionalValue = ' (' . $DB->FormatDate($oldAdditionalValue, $FORMAT_DB_TIME,
                                    $FORMAT_DB_BX_SHORT) . ')';
                        } else {
                            $oldAdditionalValue = ' (' . $oldAdditionalValue . ')';
                        }
                    }

                    $arMsg[] = Array(
                        'ENTITY_FIELD' => $FIELD_KEY,
                        'EVENT_NAME'   => GetMessage('YNSIR_FIELD_COMPARE',
                            array('#FIELD#' => $arFieldCompare[$FIELD_KEY])),
                        'EVENT_TEXT_1' => !empty($arKeyValueRemoved['CONTENT']) ? $arKeyValueRemoved['CONTENT'] . $oldAdditionalValue : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                        'EVENT_TEXT_2' => !empty($arKeyValueAdded['CONTENT']) ? $arKeyValueAdded['CONTENT'] . $newAdditionalValue : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                    );
                }
            } else {
                //TODO : Check value content removed
                $arKeyValueRemoved = array_diff_key($arOldValue[$FIELD_KEY], $arField);
                foreach ($arKeyValueRemoved as $KEY_CONTENT => $arContent) {
                    $newRemoveAdditionalValue = strlen($arContent['ADDITIONAL_VALUE']) > 0 ? $arContent['ADDITIONAL_VALUE'] : '';
                    if (strlen($newRemoveAdditionalValue > 0)) {
                        if ($arContent['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                            $newRemoveAdditionalValue = ' (' . $DB->FormatDate($newRemoveAdditionalValue,
                                    $FORMAT_DB_TIME, $FORMAT_DB_BX_SHORT) . ')';
                        } else {
                            $newRemoveAdditionalValue = ' (' . $newRemoveAdditionalValue . ')';
                        }
                    }


                    $arMsg[] = Array(
                        'ENTITY_FIELD' => $FIELD_KEY,
                        'EVENT_NAME'   => GetMessage('YNSIR_ADDITIONAL_FIELD_COMPARE_REMOVE',
                            array('#FIELD#' => $arFieldCompare[$FIELD_KEY])),
                        'EVENT_TEXT_1' => !empty($arContent['CONTENT']) ? $arContent['CONTENT'] . $newRemoveAdditionalValue : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                        'EVENT_TEXT_2' => '',
                    );
                }
                unset($newRemoveAdditionalValue);
                unset($arContent);
                unset($KEY_CONTENT);
                //TODO : Check value content added
                $arKeyValueAdded = array_diff_key($arField, $arOldValue[$FIELD_KEY]);
                foreach ($arKeyValueAdded as $KEY_CONTENT => $arContent) {
                    $newAdditionalValue = strlen($arContent['ADDITIONAL_VALUE']) > 0 ? $arContent['ADDITIONAL_VALUE'] : '';
                    if (strlen($newAdditionalValue > 0)) {
                        if ($arContent['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                            $newAdditionalValue = ' (' . $DB->FormatDate($newAdditionalValue, $FORMAT_DB_TIME,
                                    $FORMAT_DB_BX_SHORT) . ')';
                        } else {
                            $newAdditionalValue = ' (' . $newAdditionalValue . ')';
                        }
                    }


                    $arMsg[] = Array(
                        'ENTITY_FIELD' => $FIELD_KEY,
                        'EVENT_NAME'   => GetMessage('YNSIR_ADDITIONAL_FIELD_COMPARE_ADD',
                            array('#FIELD#' => $arFieldCompare[$FIELD_KEY])),
                        'EVENT_TEXT_1' => !empty($arContent['CONTENT']) ? $arContent['CONTENT'] . $newAdditionalValue : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                        'EVENT_TEXT_2' => '',
                    );
                }
                unset($newAdditionalValue);
                unset($arContent);
                unset($KEY_CONTENT);
            }
            //TODO : Content not change, Check  Additional change
            $arKeyValueNotchangeContent = array_intersect_key($arField, $arOldValue[$FIELD_KEY]);
            foreach ($arKeyValueNotchangeContent as $KEY_CONTENT => $arContent) {
                $arFieldsOrig = $arOldValue[$FIELD_KEY][$KEY_CONTENT]['ADDITIONAL_VALUE'];
                //change Date view
                if (strlen($arFieldsOrig > 0) && $arOldValue[$FIELD_KEY][$KEY_CONTENT]['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                    $arFieldsOrig = $DB->FormatDate($arFieldsOrig, $FORMAT_DB_TIME, $FORMAT_DB_BX_SHORT);
                }

                if (strlen($arContent['ADDITIONAL_VALUE'] > 0) && $arContent['ADDITIONAL_TYPE'] == YNSIRConfig::YNSIR_TYPE_LIST_DATE) {
                    $arContent['ADDITIONAL_VALUE'] = $DB->FormatDate($arContent['ADDITIONAL_VALUE'], $FORMAT_DB_TIME,
                        $FORMAT_DB_BX_SHORT);
                }

                if ($arContent['ADDITIONAL_VALUE'] != $arFieldsOrig) {
                    //Update Additional value
                    $arMsg[] = Array(
                        'ENTITY_FIELD' => $FIELD_KEY,
                        'EVENT_NAME'   => GetMessage('YNSIR_ADDITIONAL_FIELD_COMPARE',
                            array('#FIELD#' => $arFieldCompare[$FIELD_KEY])),
                        'EVENT_TEXT_1' => !empty($arFieldsOrig) ? $arFieldsOrig : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                        'EVENT_TEXT_2' => !empty($arContent['ADDITIONAL_VALUE']) ? $arContent['ADDITIONAL_VALUE'] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                    );
                }
                unset($arFieldsOrig);
            }
            unset($arContent);
            unset($KEY_CONTENT);
        }
        unset($arField);
        // End Check ADD or REMOVE in each FIELD
        return $arMsg;

    }

    public static function CompareFields($arFieldsOrig, $arFieldsModif, $bCheckPerms = true, $arOptions = null)
    {
        global $DB, $USER;
        $sFormatName = CSite::GetNameFormat(false);

        if (!is_array($arOptions)) {
            $arOptions = array();
        }
        $FORMAT_DB_TIME = 'YYYY-MM-DD';
        $FORMAT_DB_BX_SHORT = CSite::GetDateFormat("SHORT");

        $arFieldCompare = YNSIRConfig::getFieldsCandidate();

        //TODO GET CACHE LIST

        $arConfig = YNSIRConfig::GetListTypeList();
        $arConfig['CANDIDATE_STATUS'] = YNSIRGeneral::getListJobStatus('CANDIDATE_STATUS');
        foreach ($arConfig['CANDIDATE_STATUS'] as $k => $element_list) {
            $arListType['CANDIDATE_STATUS'][$k] = array('NAME_' . strtoupper(LANGUAGE_ID) => $element_list);
        }
        //END TODO GET CACHE LIST
        //GET FROM CACHE
        $arMsg = Array();
        foreach ($arFieldsModif as $KEY => $newValue) {
            switch ($KEY) {
                case 'FILE_RESUME':
                case 'FILE_FORMATTED_RESUME':
                case 'FILE_COVER_LETTER':
                case 'FILE_OTHERS':
                default:
                    if ($KEY == "DOB") {
                        //TODO Format short date
                        if (strlen($arFieldsModif[$KEY]) > 0) {
                            $arFieldsModif[$KEY] = $DB->FormatDate($arFieldsModif[$KEY], $FORMAT_DB_TIME,
                                $FORMAT_DB_BX_SHORT);
                        }
                    }
                    if (key_exists($KEY, $arFieldCompare) &&
                        isset($arFieldsOrig[$KEY]) && isset($arFieldsModif[$KEY])
                        && $arFieldsOrig[$KEY] != $arFieldsModif[$KEY]
                    ) {
                        if (key_exists($KEY, $arListType)) {
                            $arMsg[] = Array(
                                'ENTITY_FIELD' => $KEY,
                                'EVENT_NAME'   => GetMessage('YNSIR_FIELD_COMPARE',
                                    array('#FIELD#' => $arFieldCompare[$KEY])),
                                'EVENT_TEXT_1' => !empty($arFieldsOrig[$KEY]) ? $arListType[$KEY][$arFieldsOrig[$KEY]]['NAME_' . strtoupper(LANGUAGE_ID)] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                                'EVENT_TEXT_2' => !empty($arFieldsModif[$KEY]) ? $arListType[$KEY][$arFieldsModif[$KEY]]['NAME_' . strtoupper(LANGUAGE_ID)] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                            );
                        } else {
                            switch ($KEY) {
                                case 'EMAIL_OPT_OUT':
                                    if (intval($arFieldsOrig[$KEY]) == 1) {
                                        $arFieldsOrig[$KEY] = GetMessage("YNSIR_FIELD_OPTION_YES");
                                    } else {
                                        $arFieldsOrig[$KEY] = GetMessage("YNSIR_FIELD_OPTION_NO");
                                    }
                                    if (intval($arFieldsModif[$KEY]) == 1) {
                                        $arFieldsModif[$KEY] = GetMessage("YNSIR_FIELD_OPTION_YES");
                                    } else {
                                        $arFieldsModif[$KEY] = GetMessage("YNSIR_FIELD_OPTION_NO");
                                    }
                                    break;
                                case "GENDER":
                                    if ($arFieldsOrig[$KEY] == 'M') {
                                        $arFieldsOrig[$KEY] = GetMessage("YNSIR_FIELD_COMPARE_MALE");
                                    }
                                    if ($arFieldsOrig[$KEY] == 'F') {
                                        $arFieldsOrig[$KEY] = GetMessage("YNSIR_FIELD_COMPARE_FEMALE");
                                    }
                                    if ($arFieldsModif[$KEY] == 'M') {
                                        $arFieldsModif[$KEY] = GetMessage("YNSIR_FIELD_COMPARE_MALE");
                                    }
                                    if ($arFieldsModif[$KEY] == 'F') {
                                        $arFieldsModif[$KEY] = GetMessage("YNSIR_FIELD_COMPARE_FEMALE");
                                    }
                                    break;
                                case 'CANDIDATE_OWNER':
                                    if (intval($arFieldsOrig[$KEY]) > 0) {
                                        $rsUser = CUser::GetByID(intval($arFieldsOrig[$KEY]));
                                        $arUser = $rsUser->Fetch();
                                        $arFieldsOrig[$KEY] = CUser::FormatName(
                                            $sFormatName,
                                            array(
                                                "NAME"        => $arUser['NAME'],
                                                "LAST_NAME"   => $arUser['LAST_NAME'],
                                                "SECOND_NAME" => $arUser['SECOND_NAME'],
                                            )
                                        );
                                    }
                                    if (intval($arFieldsModif[$KEY]) > 0) {
                                        $rsUser = CUser::GetByID(intval($arFieldsModif[$KEY]));
                                        $arUser = $rsUser->Fetch();
                                        $arFieldsModif[$KEY] = CUser::FormatName(
                                            $sFormatName,
                                            array(
                                                "NAME"        => $arUser['NAME'],
                                                "LAST_NAME"   => $arUser['LAST_NAME'],
                                                "SECOND_NAME" => $arUser['SECOND_NAME'],
                                            )
                                        );
                                    }
                                default:
                                    break;
                            }
                            $arMsg[] = Array(
                                'ENTITY_FIELD' => $KEY,
                                'EVENT_NAME'   => GetMessage('YNSIR_FIELD_COMPARE',
                                    array('#FIELD#' => $arFieldCompare[$KEY])),
                                'EVENT_TEXT_1' => !empty($arFieldsOrig[$KEY]) ? $arFieldsOrig[$KEY] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                                'EVENT_TEXT_2' => !empty($arFieldsModif[$KEY]) ? $arFieldsModif[$KEY] : GetMessage("YNSIR_FIELD_COMPARE_EMPTY"),
                            );
                        }

                    }
                    break;
            }
        }
        return $arMsg;
    }

    public static function GetByID($ID = 0)
    {
        $version = self::GetList(array(), array('ID' => $ID));
        return $version->Fetch();
    }

    public static function GetList($arOrder = array(),
                                   $arFilter = array(),
                                   $arGroupBy = false,
                                   $arNavStartParams = false,
                                   $arSelectFields = array()
    ) {
        $lb = new YNSIRSQLHelper(
            YNSIRCandidate::DB_TYPE,
            YNSIRCandidate::TABLE_NAME,
            YNSIRCandidate::TABLE_ALIAS,
            self::GetCandidateFields()
        );

        return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields);
    }

    public static function GetListCandidate($arOrder = array("ID" => "ASC"),
                                            $arFilter,
                                            $arNavStartParams = false,
                                            $arOptions,
                                            $arSelectFields = array()
    ) {
        $arField = self::GetCandidateFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null);

        $lb = new YNSIRSQLHelper(
            YNSIRCandidate::DB_TYPE,
            YNSIRCandidate::TABLE_NAME,
            YNSIRCandidate::TABLE_ALIAS,
            $arField,
            'YNSIR_CANDIDATE',
            'CANDIDATE',
            array('YNSIRCandidate', 'BuildPermSql')
        );

        $arSelectFields_ = array_merge(array_keys($arField), $arSelectFields);
        return $lb->Prepare($arOrder, $arFilter, false, $arNavStartParams, $arSelectFields_, $arOptions);
    }

    public static function GetListCandidateNonePerms($arOrder = array("ID" => "ASC"),
                                                     $arFilter,
                                                     $arNavStartParams = false,
                                                     $arOptions,
                                                     $arSelectFields = array()
    ) {
        $arField = self::GetCandidateFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null);

        $lb = new YNSIRSQLHelper(
            YNSIRCandidate::DB_TYPE,
            YNSIRCandidate::TABLE_NAME,
            YNSIRCandidate::TABLE_ALIAS,
            $arField
        );

        $arSelectFields_ = array_merge(array_keys($arField), $arSelectFields);
        return $lb->Prepare($arOrder, $arFilter, false, $arNavStartParams, $arSelectFields_, $arOptions);
    }

    public static function GetListCandidateForMultiField($arOrder = array("ID" => "ASC"),
                                                         $arFilter,
                                                         $arNavStartParams = false,
                                                         $arOptions,
                                                         $arSelectFields = array(),
                                                         $groupby = true
    ) {
        $arField = self::GetCandidateFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null);
        $arJoin = array(
            'CMOBILE' => array(
                'FIELD' => 'YCFM.CONTENT',
                'TYPE'  => 'string',
                'FROM'  => "
                    LEFT JOIN b_ynsir_candidate_field_multiple YCFM ON YCFM.CANDIDATE_ID = YC.ID AND YCFM.TYPE = 'CMOBILE'
                    "
            )
        );
        $arField = array_merge($arField, $arJoin);

        $arJoin = array(
            'EMAIL' => array(
                'FIELD' => 'YCFM_EMAIL.CONTENT',
                'TYPE'  => 'string',
                'FROM'  => "
                    LEFT JOIN b_ynsir_candidate_field_multiple YCFM_EMAIL ON YCFM_EMAIL.CANDIDATE_ID = YC.ID AND YCFM_EMAIL.TYPE = 'EMAIL'
                    "
            )
        );
        $arField = array_merge($arField, $arJoin);

        $arJoin = array(
            'PHONE' => array(
                'FIELD' => 'YCFM_PHONE.CONTENT',
                'TYPE'  => 'string',
                'FROM'  => "
                    LEFT JOIN b_ynsir_candidate_field_multiple YCFM_PHONE ON YCFM_PHONE.CANDIDATE_ID = YC.ID AND YCFM_PHONE.TYPE = 'PHONE'
                    "
            )
        );
        $arField = array_merge($arField, $arJoin);
        $arJoin = array(
            'FILE_CONTENT' => array(
                'FIELD' => 'YNF.FILE_CONTENT',
                'TYPE'  => 'string',
                'FROM'  => "
                    LEFT JOIN b_ynsir_file YNF ON YNF.CANDIDATE_ID = YC.ID
                    "
            )
        );
        $arField = array_merge($arField, $arJoin);

        ///
        $arListJoin = array(
//            'CMOBILE',
//            'EMAIL',
//            'PHONE',
            YNSIRConfig::TL_TYPE_OF_EMPLOYMENT,//
            YNSIRConfig::TL_SOURCES,
            YNSIRConfig::TL_UNIVERSITY,
            YNSIRConfig::TL_MAJOR,
            YNSIRConfig::TL_ENGLISH_PROFICIENCY,
            YNSIRConfig::TL_APPLY_POSITION,
            YNSIRConfig::TL_MARITAL_STATUS
        );
        foreach ($arListJoin as $keyJoin) {
            $arJoin = array(
                $keyJoin => array(
                    'FIELD' => 'YCFM_' . $keyJoin . '.CONTENT',
                    'TYPE'  => 'string',
                    'FROM'  => "
                    LEFT JOIN b_ynsir_candidate_field_multiple YCFM_" . $keyJoin . " ON YCFM_" . $keyJoin . ".CANDIDATE_ID = YC.ID AND YCFM_" . $keyJoin . ".TYPE = '" . $keyJoin . "'
                    "
                )
            );
            $arField = array_merge($arField, $arJoin);
        }

        $lb = new YNSIRSQLHelper(
            YNSIRCandidate::DB_TYPE,
            YNSIRCandidate::TABLE_NAME,
            YNSIRCandidate::TABLE_ALIAS,
            $arField,
            '',
            '',
            array('YNSIRCandidate', 'BuildPermSql')

        );
        $arSelectFields_ = array_merge(array_keys(self::GetCandidateFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null)),
            $arSelectFields);

        if (!$groupby) {
            $gb = false;
        } else {
            $gb = array('ID');
        }
        return $lb->Prepare($arOrder, $arFilter, $gb, $arNavStartParams, $arSelectFields_, $arOptions);
    }

    //gianglh
    public static function GetListAllFields($arOrder = array("ID" => "ASC"),
                                            $arFilter,
                                            $arNavStartParams = false,
                                            $arOptions,
                                            $arSelectFields = array(),
                                            $groupby = true
    ) {
        $arField = self::GetCandidateFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null);
        $arJoin = array(
            'CMOBILE' => array(
                'FIELD' => 'YCFM.CONTENT',
                'TYPE'  => 'string',
                'FROM'  => "
                    LEFT JOIN b_ynsir_candidate_field_multiple YCFM ON YCFM.CANDIDATE_ID = YC.ID AND YCFM.TYPE = 'CMOBILE'
                    "
            )
        );
        $arField = array_merge($arField, $arJoin);

        $arJoin = array(
            'EMAIL' => array(
                'FIELD' => 'YCFM_EMAIL.CONTENT',
                'TYPE'  => 'string',
                'FROM'  => "
                    LEFT JOIN b_ynsir_candidate_field_multiple YCFM_EMAIL ON YCFM_EMAIL.CANDIDATE_ID = YC.ID AND YCFM_EMAIL.TYPE = 'EMAIL'
                    "
            )
        );
        $arField = array_merge($arField, $arJoin);

        $arJoin = array(
            'PHONE' => array(
                'FIELD' => 'YCFM_PHONE.CONTENT',
                'TYPE'  => 'string',
                'FROM'  => "
                    LEFT JOIN b_ynsir_candidate_field_multiple YCFM_PHONE ON YCFM_PHONE.CANDIDATE_ID = YC.ID AND YCFM_PHONE.TYPE = 'PHONE'
                    "
            )
        );
        $arField = array_merge($arField, $arJoin);
        $arJoin = array(
            'FILE_CONTENT' => array(
                'FIELD' => 'YNF.FILE_CONTENT',
                'TYPE'  => 'string',
                'FROM'  => "
                    LEFT JOIN b_ynsir_file YNF ON YNF.CANDIDATE_ID = YC.ID
                    "
            )
        );
        $arField = array_merge($arField, $arJoin);

        ///
        $arListJoin = array(
            YNSIRConfig::TL_TYPE_OF_EMPLOYMENT,
            YNSIRConfig::TL_SOURCES,
            YNSIRConfig::TL_UNIVERSITY,
            YNSIRConfig::TL_MAJOR,
            YNSIRConfig::TL_ENGLISH_PROFICIENCY,
            YNSIRConfig::TL_APPLY_POSITION,
            YNSIRConfig::TL_MARITAL_STATUS
        );
        foreach ($arListJoin as $keyJoin) {
            $arJoin = array(
                $keyJoin => array(
                    'FIELD' => 'YCFM_' . $keyJoin . '.CONTENT',
                    'TYPE'  => 'string',
                    'FROM'  => "
                    LEFT JOIN b_ynsir_candidate_field_multiple YCFM_" . $keyJoin . " ON YCFM_" . $keyJoin . ".CANDIDATE_ID = YC.ID AND YCFM_" . $keyJoin . ".TYPE = '" . $keyJoin . "'
                    "
                )
            );
            $arField = array_merge($arField, $arJoin);
        }

        $lb = new YNSIRSQLHelper(
            YNSIRCandidate::DB_TYPE,
            YNSIRCandidate::TABLE_NAME,
            YNSIRCandidate::TABLE_ALIAS,
            $arField,
            '',
            '',
            array('YNSIRCandidate', 'BuildPermSql')

        );
        $arSelectFields_ = array_keys($arField);

        if (!$groupby) {
            $gb = false;
        } else {
            $gb = array('ID');
        }
        return $lb->Prepare($arOrder, $arFilter, $gb, $arNavStartParams, $arSelectFields_, $arOptions);
    }

    public static function GetListMultiField($arOrder = array("ID" => "ASC"), $arFilter)
    {
        $arField = array(
            "ID"               => array('FIELD' => 'YFM.ID', 'TYPE' => 'string'),
            "CANDIDATE_ID"     => array('FIELD' => 'YFM.CANDIDATE_ID', 'TYPE' => 'string'),
            "NAME"             => array('FIELD' => 'YFM.NAME', 'TYPE' => 'string'),
            "CONTENT"          => array('FIELD' => 'YFM.CONTENT', 'TYPE' => 'string'),
            "ADDITIONAL_VALUE" => array('FIELD' => 'YFM.ADDITIONAL_VALUE', 'TYPE' => 'string'),
            "ADDITIONAL_TYPE"  => array('FIELD' => 'YFM.ADDITIONAL_TYPE', 'TYPE' => 'string'),
            "TYPE"             => array('FIELD' => 'YFM.TYPE', 'TYPE' => 'string'),
        );
        $lb = new YNSIRSQLHelper(
            YNSIRCandidate::DB_TYPE,
            'b_ynsir_candidate_field_multiple',
            'YFM',
            $arField
        );
        $arSelectFields_ = array_merge($arField);
        return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, array_keys($arField), $arOptions);
    }

    private static function normalizeMutible($arMutiple = array(), $arTypeList = array())
    {
        $arResult = array();
        foreach ($arMutiple as $arMultiple) {
            foreach ($arMultiple['CONTENT'] as $CONTENT) {
                //TODO: GROUP BY TYPE
                $originContent = $CONTENT;
                if ($CONTENT == -1 || strlen($CONTENT) == 0) {
                    continue;
                }

                //TODO: Get Name Content instead of ID if Content in Type list
                if (strlen($arTypeList[$arMultiple['TYPE']][$CONTENT]) > 0) {
                    $CONTENT = $arTypeList[$arMultiple['TYPE']][$CONTENT];
                }

                $arrayContent = array(
                    'CONTENT'          => $CONTENT,
                    'ADDITIONAL_VALUE' => $arMultiple['ADDITIONAL_VALUE'],
                    'ADDITIONAL_TYPE'  => $arMultiple['ADDITIONAL_TYPE']
                );
                $arResult[$arMultiple['TYPE']][$originContent] = $arrayContent;
                unset($arrayContent);
            }
            unset($CONTENT);
        }
        unset($arMultiple);
        return $arResult;
    }

    public static function CheckCreatePermission($userPermissions = null)
    {
        return YNSIRAuthorizationHelper::CheckCreatePermission(self::$TYPE_NAME, $userPermissions);
    }

    public static function CheckCreatePermissionSec($userPermissions = null, $SECTION)
    {
        return YNSIRAuthorizationHelper::CheckCreatePermissionSec(self::$TYPE_NAME, $userPermissions, $SECTION);
    }

    public static function CheckUpdatePermission($ID, $userPermissions = null)
    {
        return YNSIRAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions);
    }

    public static function CheckUpdatePermissionSec($ID, $SECTION, $userPermissions = null)
    {
        return YNSIRAuthorizationHelper::CheckUpdatePermissionSec($SECTION, self::$TYPE_NAME, $ID, $userPermissions);
    }

    public static function CheckReadPermission($ID = 0,
                                               $userPermissions = null,
                                               $SECTION = '',
                                               $categoryID = -1,
                                               array $options = null
    ) {
        return YNSIRAuthorizationHelper::CheckReadPermission(self::$TYPE_NAME, $ID, $userPermissions, null, $SECTION);
    }

    static public function BuildPermSql($sAliasPrefix = 'YC', $mPermType = 'READ', $arOptions = array())
    {
        return YNSIRPerms_::BuildSql('CANDIDATE', $sAliasPrefix, $mPermType, $arOptions);
    }

    public static function IsAccessEnabled(YNSIRPerms_ $userPermissions = null)
    {
        return self::CheckReadPermission(0, $userPermissions);
    }

}

?>