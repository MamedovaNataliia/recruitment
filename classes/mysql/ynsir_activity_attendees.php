<?
class YNSIRActivityAttendees
{
    const TABLE_NAME = 'b_ynsir_usr_act_attendees';
    const DB_TYPE = 'MYSQL';
    const TABLE_ALIAS = 'YAA';
    private static $FIELDS = null;
    private static $FIELD_CANDIDATE = null;
    private static $ERRORS = array();


    static function GetCandidateFields($arOptions = null)
    {
        global $USER;
        if (!isset(self::$FIELD_CANDIDATE)) {
            self::$FIELD_CANDIDATE = array(
                "ID" => array('FIELD' => 'YAA.ID', 'TYPE' => 'string'),
                "USER_ID" => array('FIELD' => 'YAA.USER_ID', 'TYPE' => 'string'),
                "ACTIVITY_ID" => array('FIELD' => 'YAA.ACTIVITY_ID', 'TYPE' => 'string'),
                "CREATED_AT" => array('FIELD' => 'YAA.CREATED_DATE', 'TYPE' => 'datetime'),
            );
//            $additionalFields = isset($arOptions['ADDITIONAL_FIELDS'])
//                ? $arOptions['ADDITIONAL_FIELDS'] : null;

//            if(is_array($additionalFields))
//            {
//                if(in_array('ACTIVITY', $additionalFields, true))
//                {
//                    $commonActivityJoin = YNSIRActivity::PrepareJoin(0, YNSIROwnerType::Candidate, self::TABLE_ALIAS, 'AC', 'UAC', 'ACUSR');
//
//                    self::$FIELD_CANDIDATE['C_ACTIVITY_ID'] = array('FIELD' => 'UAC.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
//                    self::$FIELD_CANDIDATE['C_ACTIVITY_TIME'] = array('FIELD' => 'UAC.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $commonActivityJoin);
//                    self::$FIELD_CANDIDATE['C_ACTIVITY_SUBJECT'] = array('FIELD' => 'AC.SUBJECT', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
//                    self::$FIELD_CANDIDATE['C_ACTIVITY_RESP_ID'] = array('FIELD' => 'AC.RESPONSIBLE_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
//                    self::$FIELD_CANDIDATE['C_ACTIVITY_RESP_LOGIN'] = array('FIELD' => 'ACUSR.LOGIN', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
//                    self::$FIELD_CANDIDATE['C_ACTIVITY_RESP_NAME'] = array('FIELD' => 'ACUSR.NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
//                    self::$FIELD_CANDIDATE['C_ACTIVITY_RESP_LAST_NAME'] = array('FIELD' => 'ACUSR.LAST_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
//                    self::$FIELD_CANDIDATE['C_ACTIVITY_RESP_SECOND_NAME'] = array('FIELD' => 'ACUSR.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
//
//                    $userID = $USER->GetbyID();
//                    if($userID > 0)
//                    {
//                        $activityJoin = YNSIRActivity::PrepareJoin($userID, YNSIROwnerType::Candidate, self::TABLE_ALIAS, 'A', 'UA', '');
//
//                        self::$FIELD_CANDIDATE['ACTIVITY_ID'] = array('FIELD' => 'UA.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $activityJoin);
//                        self::$FIELD_CANDIDATE['ACTIVITY_TIME'] = array('FIELD' => 'UA.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $activityJoin);
//                        self::$FIELD_CANDIDATE['ACTIVITY_SUBJECT'] = array('FIELD' => 'A.SUBJECT', 'TYPE' => 'string', 'FROM' => $activityJoin);
//                    }
//                }
//            }

        }
        return self::$FIELD_CANDIDATE;
    }

    public static function Add($arFields = array())
    {
        global $DB;
        $err_mess = "<br>Function: Add<br>Line: ";
        $ID = 0;

        if (!empty($arFields)) {
            //add new order
            foreach ($arFields['ATTENDEES_CODES'] as $arUser) {
                $arInsert[] = array(
                    'USER_ID' => "'" . $DB->ForSql($arUser) . "'",
                    'ACTIVITY_ID' => "'" . $DB->ForSql($arFields['ACTIVITY_ID']) . "'",
                    'CREATED_AT' => $DB->CurrentTimeFunction(),
                );
            }
            if(!empty($arInsert)) {
                $arValue = Array();
                foreach ($arInsert as $key => $arValue_) {
                    $arValue[] = '(' . implode(",", $arValue_) . ')';
                }

                $strSql = "INSERT INTO " . self::TABLE_NAME . "(USER_ID,ACTIVITY_ID,CREATED_AT) VALUE " . implode(",", $arValue);

                $res = $DB->Query($strSql, false, $err_mess . __LINE__);

                if (strlen($DB->GetErrorMessage())) {
                    throw new Exception($DB->GetErrorMessage());
                    return false;
                }

                if ($res === false)
                    return false;

                if (strlen($DB->GetErrorMessage())) {
                    throw new Exception($DB->GetErrorMessage());
                    return false;
                }
                $ID = $DB->LastID();
            } else {
                $ID = 1;
            }
        } else {
            $ID = 1;
        }
        return $ID;

    }

    public static function DeleteByActID($Id) {
        global $DB;
        $query = "DELETE FROM " . self::TABLE_NAME . " WHERE ACTIVITY_ID={$Id}" ;
        $res = $DB->Query($query);
        return $res;
    }
}

?>