<?php
/**
* INTERVIEW
*/
class YNSIRInterview
{
	const DB_TYPE = 'MYSQL';
	const TABLE_NAME = 'b_ynsir_interview_round';
	const TABLE_ALIAS = 'BYIR';

	static function GetFields()
    {
        return array(
            "ID" => array('FIELD' => 'BYIR.ID', 'TYPE' => 'int'),
            "CREATED_BY" => array('FIELD' => 'BYIR.CREATED_BY', 'TYPE' => 'int'),
            "MODIFIED_BY" => array('FIELD' => 'BYIR.MODIFIED_BY', 'TYPE' => 'int'),
            "DATE_CREATE" => array('FIELD' => 'BYIR.DATE_CREATE', 'TYPE' => 'datetime'),
            "DATE_MODIFY" => array('FIELD' => 'BYIR.DATE_MODIFY', 'TYPE' => 'datetime'),
            "JOB_ORDER" => array('FIELD' => 'BYIR.JOB_ORDER', 'TYPE' => 'int'),
            "ROUND_INDEX" => array('FIELD' => 'BYIR.ROUND_INDEX', 'TYPE' => 'int'),
            "NOTE" => array('FIELD' => 'BYIR.NOTE', 'TYPE' => 'string'),
            "SEARCH_CONTENT" => array('FIELD' => 'BYIR.SEARCH_CONTENT', 'TYPE' => 'string'),
            // JOIN
            // => NOTE : alias : BYUR
            YNSIRJobOrder::JO_ENTITY_PARTICIPANT => array(
                'FIELD' => 'BYUR.USER_ID',
                'TYPE' => 'int',
                'FROM'=> 'LEFT JOIN b_ynsir_user_relation BYUR ON BYIR.ID = BYUR.SOURCE_ID AND BYUR.ENTITY=\'' . YNSIRJobOrder::JO_ENTITY_PARTICIPANT . '\'',
            ),
        );
    }

    public static function GetList($arOrder = array("ID" => "DESC"), $arFilter, $arGroupBy = false,$arNavStartParams = false, $arOptions)
    {
        $arField = self::GetFields();
        $lb = new YNSIRSQLHelper(
            static::DB_TYPE,
            static::TABLE_NAME,
            static::TABLE_ALIAS,
            $arField
        );
        return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, array_keys($arField), $arOptions);
    }

    public static function getListDetail($arOrder = array("ID" => "DESC"), $arFilter, $arGroupBy = false,$arNavStartParams = false, $arOptions){
    	$arResult = array();
    	$obRes = static::GetList($arOrder = array("ID" => "DESC"), $arFilter, $arGroupBy = false,$arNavStartParams = false, $arOptions);
    	$arParticipant = array();
    	$sFieldNameParticipant = YNSIRJobOrder::JO_ENTITY_PARTICIPANT;
    	while ($arData = $obRes->Fetch()) {
    		$sTempParticipant = $arData[$sFieldNameParticipant];
    		unset($arData[$sFieldNameParticipant]);
    		$arData[$sFieldNameParticipant][$sTempParticipant] = $sTempParticipant;
    		if(!isset($arResult[$arData['JOB_ORDER']][$arData['ID']])){
    			$arResult[$arData['JOB_ORDER']][$arData['ID']] = $arData;
    		}
    		else {
    			$arResult[$arData['JOB_ORDER']][$arData['ID']][$sFieldNameParticipant][$sTempParticipant] = $sTempParticipant;
    		}
    	}
    	return $arResult;
    }

    public static function Add($arFields = array()){
        global $DB, $USER;
        $iResult = 0;
        if(!empty($arFields)){
            $iUserId = $USER->GetID();
            $arDataInsert = array();
            foreach ($arFields as $sKeyField => $itemValue) {
                if($sKeyField == YNSIRJobOrder::JO_ENTITY_PARTICIPANT){
                    continue;
                }
                $arDataInsert[$sKeyField] = "'" . $DB->ForSql($itemValue) . "'";
            }
            $arDataInsert['DATE_CREATE'] = $arDataInsert['DATE_MODIFY'] = $DB->CurrentTimeFunction();
            $arDataInsert['CREATED_BY'] = $arDataInsert['MODIFIED_BY'] = $iUserId;
            // search content
            $arDataInsert['SEARCH_CONTENT'] = "'" . $DB->ForSql(str_rot13($arFields['NOTE'])) . "'";
            // insert new interview round
            $iResult = $DB->Insert(static::TABLE_NAME, $arDataInsert);
            // insert user relation
            if($iResult > 0){
                foreach ($arFields[YNSIRJobOrder::JO_ENTITY_PARTICIPANT] as $iIdParticipant) {
                    YNSIRUserRelation::Add(array(
                        'SOURCE_ID'=> $iResult,
                        'USER_ID'=> intval($iIdParticipant),
                        'ENTITY'=> YNSIRJobOrder::JO_ENTITY_PARTICIPANT,
                    ));
                }
            }
        }
        return $iResult;
    }

    public static function Update($iId = 0, $arFields = array()){
        global $DB, $USER;
        $iResult = 0;
        $iId = intval($iId);
        if(!empty($arFields) && $iId > 0){
            $iUserId = $USER->GetID();
            $arDataUpdate = array();
            foreach ($arFields as $sKeyField => $itemValue) {
                if($sKeyField == YNSIRJobOrder::JO_ENTITY_PARTICIPANT || $sKeyField == 'ID'){
                    continue;
                }
                $arDataUpdate[$sKeyField] = "'" . $DB->ForSql($itemValue) . "'";
            }
            $arDataUpdate['DATE_MODIFY'] = $DB->CurrentTimeFunction();
            $arDataUpdate['MODIFIED_BY'] = $iUserId;

            $iResult = $DB->Update(static::TABLE_NAME, $arDataUpdate, "WHERE ID='".intval($iId)."'");

            if($iResult > 0){
                // delete user ralation
                YNSIRUserRelation::delete(array('SOURCE_ID' => $iId, 'ENTITY' => YNSIRJobOrder::JO_ENTITY_PARTICIPANT));
                foreach ($arFields[YNSIRJobOrder::JO_ENTITY_PARTICIPANT] as $iIdParticipant) {
                    YNSIRUserRelation::Add(array(
                        'SOURCE_ID'=> $iId,
                        'USER_ID'=> intval($iIdParticipant),
                        'ENTITY'=> YNSIRJobOrder::JO_ENTITY_PARTICIPANT,
                    ));
                }
            }
        }
        return $iResult;
    }

    public static function delete($iId = 0){
        global $DB;
        $iId = intval($iId);
        if($iId > 0){
            $sQuery = "DELETE FROM " . static::TABLE_NAME . " WHERE ID = " . $iId;
            $ID = $DB->Query($sQuery);
            // delete user ralation
            YNSIRUserRelation::delete(array('SOURCE_ID' => $iId, 'ENTITY' => YNSIRJobOrder::JO_ENTITY_PARTICIPANT));
        }
    }
    public static function isExist($iId) {
        if(intval($iId) <= 0) return false;
        $rsRound = static::GetList(array(),array('ID' => $iId));
        if($arRs = $rsRound->getNext()) {
            return true;
        } else {
            return false;
        }
    }
}
?>