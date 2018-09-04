<?php
use Bitrix\Tasks\Manager;

class YNSIRFeedback
{

    private static $TABLE_LIST = 'b_ynsir_feedback';
    const DB_TYPE = 'MYSQL';
    const TABLE_ALIAS = 'YFB';
    private static $FIELD_TYPE_LIST = null;

    static function GetTypeListFields()
    {
        if (!isset(self::$FIELD_TYPE_LIST)) {
            self::$FIELD_TYPE_LIST = array(
                "ID" => array('FIELD' => 'YFB.ID', 'TYPE' => 'int'),
                "TITLE" => array('FIELD' => 'YFB.TITLE', 'TYPE' => 'string'),

                "CANDIDATE_ID" => array('FIELD' => 'YFB.CANDIDATE_ID', 'TYPE' => 'int'),
                "JOB_ORDER_ID" => array('FIELD' => 'YFB.JOB_ORDER_ID', 'TYPE' => 'int'),
                "ROUND_ID" => array('FIELD' => 'YFB.ROUND_ID', 'TYPE' => 'int'),
                "DESCRIPTION" => array('FIELD' => 'YFB.DESCRIPTION', 'TYPE' => 'string'),

                "MODIFIED_DATE" => array('FIELD' => 'YFB.MODIFIED_DATE', 'TYPE' => 'datetime'),
                "CREATED_DATE" => array('FIELD' => 'YFB.CREATED_DATE', 'TYPE' => 'datetime'),
                "CREATED_BY" => array('FIELD' => 'YFB.CREATED_BY', 'TYPE' => 'int'),
                "MODIFIED_BY" => array('FIELD' => 'YFB.MODIFIED_BY', 'TYPE' => 'int'),
            );
        }
        return self::$FIELD_TYPE_LIST;
    }

    public static function getRepareFeedbackData($params){
        global $USER,$APPLICATION;
        CModule::IncludeModule('tasks');
        $sFormatName = CSite::GetNameFormat(false);
        $arResult['status'] = true;
        $recruiter = 0;
        if(isset($params['candidate_id']) && isset($params['job_order_id']) && isset($params['round_id'])){
            //get candidte
            $candidate_id = intval($params['candidate_id']);
            if($candidate_id > 0){
                $resdb = YNSIRCandidate::GetListCandidate(array(),array('ID'=>$candidate_id));

                if($rs = $resdb->Fetch()){
                    $arResult['CANDIDATE_ID'] = $candidate_id;
                    $arResult['CANDIDATE_NAME'] = CUser::FormatName(
                        $sFormatName,
                        array(
                            "NAME" => $rs['FIRST_NAME'],
                            "LAST_NAME" => $rs['LAST_NAME'],
                        )
                    );
                }else{
                    $arResult['status'] ='not_data';
                    return $arResult;
                }
            }else{
                $arResult['status'] ='not_data';
                return $arResult;
            }
            //get Job Order
            $job_order_id = intval($params['job_order_id']);
            if($job_order_id > 0){
                $arResult['CONFIG']['ROUND'] = YNSIRInterview::getListDetail(array(), array('JOB_ORDER' => $job_order_id), false, false, array());

                $rs = YNSIRJobOrder::getById($job_order_id);

                if(!empty($rs)){
                    $recruiter = $rs['RECRUITER'];
                    $arResult['JOB_ORDER_ID'] = $job_order_id;
                    $arResult['JOB_ORDER_NAME'] = $rs['TITLE'];
                }else{
                    $arResult['status'] ='not_data';
                    return $arResult;
                }
            }else{
                $arResult['status'] ='not_data';
                return $arResult;
            }
            //get round
            $round_id = intval($params['round_id']);
            if($round_id > 0){
                $arResult['CONFIG']['ROUND'];
                $arResult['ROUND_ID'] = $round_id;
                if(!empty($arResult['CONFIG']['ROUND'][$job_order_id][$round_id])){

                    $arResult['ROUND_NAME'] = GetMessage('YNSIR_ROUND_LABEL', array('#ROUND_INDEX#' => $arResult['CONFIG']['ROUND'][$job_order_id][$round_id]['ROUND_INDEX']));
                }else{
                    $arResult['status'] ='not_data';
                    return $arResult;
                }
            }else{
                $arResult['status'] ='not_data';
                return $arResult;
            }
            //check permission
            //get paticipant
            $user_id = $USER->GetID();
            if($recruiter != $user_id) {
                $arFilter = array(
                    'OWNER_ID' => $params['candidate_id'],
                    'OWNER_TYPE_ID' => YNSIROwnerType::Candidate,
                    'REFERENCE_ID' => $job_order_id,
                    'REFERENCE_TYPE_ID' => YNSIROwnerType::Order,
                    'ROUND_ID' => $round_id,
                    'PROVIDER_TYPE_ID' => \Bitrix\YNSIR\Activity\Provider\Task::getTypeId(array()),
                );
                $dbActivityTask = CAllYNSIRActivity::GetList(
                    array(),
                    $arFilter,
                    false,
                    false
                );
                //If existed
                if ($arEntityAct = $dbActivityTask->Fetch()) {
                    //updateTask Task
                    $error = '';
                    $arTask = Bitrix\Tasks\Manager\Task::get(1, $arEntityAct['ASSOCIATED_ENTITY_ID']);
                    $arAccomplice = $arTask['DATA']['ACCOMPLICES'];
                }
                if(!in_array($user_id,$arAccomplice)){
                    $arResult['status'] ='not_data';
                    return $arResult;
                }
            }

        }else{
            $arResult['status'] ='not_data';
            return $arResult;
        }
        return $arResult;
    }
    public static function Add($arFields)
    {

        global $DB, $USER;
        $err_mess = "<br>Function: Add<br>Line: ";


        //add new keyskill
        $arInsert = array(
            'TITLE' => "'" . $DB->ForSql($arFields['TITLE']) . "'",
            'CANDIDATE_ID' => "'" . $DB->ForSql($arFields['CANDIDATE_ID']) . "'",
            'JOB_ORDER_ID' => "'" . $DB->ForSql($arFields['JOB_ORDER_ID']) . "'",
            'ROUND_ID' => "'" . $DB->ForSql($arFields['ROUND_ID']) . "'",
            'DESCRIPTION' => "'" . $DB->ForSql($arFields['DESCRIPTION']) . "'",
        );

        if (is_object($USER)) {
            if (!isset($arInsert["CREATED_BY"]) || intval($arInsert["CREATED_BY"]) <= 0)
                $arInsert["CREATED_BY"] = intval($USER->GetID());
            if (!isset($arFields["MODIFIED_BY"]) || intval($arFields["MODIFIED_BY"]) <= 0)
                $arInsert["MODIFIED_BY"] = intval($USER->GetID());
        }

        $arInsert["MODIFIED_DATE"] = $arInsert["CREATED_DATE"] = $DB->CurrentTimeFunction();


        $ID = $DB->Insert(self::$TABLE_LIST, $arInsert, $err_mess . __LINE__);

        return $ID;
    }

    public static function Update($id, $arFields)
    {
        global $DB, $USER;
        if ($id > 0 && !empty($arFields)) {
            $err_mess = "<br>Function: Update<br>Line: ";
            $arFieldsUpdate = array();

            foreach ($arFields as $key => $value) {
                $arFieldsUpdate[$key] = "'" . $DB->ForSql($value) . "'";
            }
            if (is_object($USER)) {
                if (!isset($arFields["MODIFIED_BY"]) || intval($arFields["MODIFIED_BY"]) <= 0)
                    $arFieldsUpdate["MODIFIED_BY"] = intval($USER->GetID());
            }
            $ID = $DB->Update(self::$TABLE_LIST, $arFieldsUpdate, "WHERE ID='" . intval($id) . "'", $err_mess . __LINE__);
        }
        return $ID;
    }

    public static function Delete($id, $entity = '')
    {
        global $DB;
        $query = "DELETE FROM `b_ynsir_feedback` WHERE ID=" . $id;
        $ID = $DB->Query($query);

        return $ID;
    }


    public static function GetList($arOrder = array("ID" => "DESC"), $arFilter, $arGroupBy = false, $arNavStartParams = false, $arOptions, $useConfigSort = true)
    {

        $arField = self::GetTypeListFields();
        $arJoin = array(
            'JOB_ORDER' => array('FIELD' => 'YJO.TITLE', 'TYPE' => 'string', 'FROM' => "
                    INNER JOIN b_ynsir_job_order YJO ON YJO.ID = YFB.JOB_ORDER_ID
                    ")
        );
        $arField = array_merge($arField, $arJoin);
        $arJoin = array(
            'FIRST_NAME' => array('FIELD' => 'YC.FIRST_NAME', 'TYPE' => 'string', 'FROM' => "
                    INNER JOIN b_ynsir_candidate YC ON YC.ID = YFB.CANDIDATE_ID
                    ")
        );
        $arField = array_merge($arField, $arJoin);
        $arJoin = array(
            'LAST_NAME' => array('FIELD' => 'YC.LAST_NAME', 'TYPE' => 'string', 'FROM' => "
                    INNER JOIN b_ynsir_candidate YC ON YC.ID = YFB.CANDIDATE_ID
                    ")
        );
        $arField = array_merge($arField, $arJoin);
        $lb = new YNSIRSQLHelper(
            static::DB_TYPE,
            static::$TABLE_LIST,
            static::TABLE_ALIAS,
            $arField
        );

        return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, array_keys($arField), $arOptions);
    }
}