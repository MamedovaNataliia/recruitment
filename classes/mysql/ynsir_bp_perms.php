<?php

/**
 * YNSIRBpPerms
 */
class YNSIRBpPerms
{
    const DB_TYPE = 'MYSQL';
    const TABLE_NAME = 'b_ynsir_bp_perms';
    const TABLE_ALIAS = 'YNSIBPP';

    static function GetFields()
    {
        return array(
            "ID" => array('FIELD' => 'YNSIBPP.ID', 'TYPE' => 'int'),
            "USER_ID" => array('FIELD' => 'YNSIBPP.USER_ID', 'TYPE' => 'int'),
            "ENTITY" => array('FIELD' => 'YNSIBPP.ENTITY', 'TYPE' => 'string'),
            "DATE_CREATE" => array('FIELD' => 'YNSIBPP.DATE_CREATE', 'TYPE' => 'datetime'),
            "DATE_MODIFY" => array('FIELD' => 'YNSIBPP.DATE_MODIFY', 'TYPE' => 'datetime'),
        );
    }

    public static function GetList($arOrder = array("ID" => "DESC"), $arFilter, $arGroupBy = false, $arNavStartParams = false, $arOptions)
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

    public static function Add($arFields = array())
    {
        global $DB, $USER;
        $iResult = 0;
        if (!empty($arFields)) {
            $iUserId = $USER->GetID();
            $arDataInsert = array();
            foreach ($arFields as $sKeyField => $itemValue) {
                $arDataInsert[$sKeyField] = "'" . $DB->ForSql($itemValue) . "'";
            }
            $arDataInsert['DATE_CREATE'] = $arDataInsert['DATE_MODIFY'] = $DB->CurrentTimeFunction();
            $iResult = $DB->Insert(static::TABLE_NAME, $arDataInsert);
        }
        return $iResult;
    }

    public static function Update($iId = 0, $arFields = array())
    {
        global $DB, $USER;
        $iResult = 0;
        $iId = intval($iId);
        if (!empty($arFields) && $iId > 0) {
            $iUserId = $USER->GetID();
            $arDataUpdate = array();
            foreach ($arFields as $sKeyField => $itemValue) {
                if ($sKeyField == YNSIRJobOrder::JO_ENTITY_PARTICIPANT || $sKeyField == 'ID') {
                    continue;
                }
                $arDataUpdate[$sKeyField] = "'" . $DB->ForSql($itemValue) . "'";
            }

            $iResult = $DB->Update(static::TABLE_NAME, $arDataUpdate, "WHERE ID='" . intval($iId) . "'");
        }
        return $iResult;
    }

    public static function hasBPPerms($data)
    {
        $rs = YNSIRBpPerms::GetList(array(), array('USER_ID' => $data['USER_ID'], 'ELEMENT_ID' => $data['ID'], 'ENTITY' => YNSIR_JOB_ORDER));
        if($rs->Fetch()) {
            return true;
        }
        return false;
    }

    public static function delete($data)
    {
        global $DB;
        $iId = intval($iId);
        if (!empty($data)) {
            $sQuery = "DELETE FROM " . static::TABLE_NAME . " WHERE USER_ID = " . $data['USER_ID'] . " AND ELEMENT_ID = " . $data['ELEMENT_ID'] . " AND ENTITY = \"" . $data['ENTITY'] . '"';
            $ID = $DB->Query($sQuery);
        }
    }
}

?>