<?php
/**
 * INTERVIEW
 */
class YNSIRUserRelation
{
    const DB_TYPE = 'MYSQL';
    const TABLE_NAME = 'b_ynsir_user_relation';
    const TABLE_ALIAS = 'BYUR';

    static function GetFields()
    {
        return array(
            "ID" => array('FIELD' => 'BYUR.ID', 'TYPE' => 'int'),
            "SOURCE_ID" => array('FIELD' => 'BYUR.SOURCE_ID', 'TYPE' => 'int'),
            "USER_ID" => array('FIELD' => 'BYUR.USER_ID', 'TYPE' => 'int'),
            "ENTITY" => array('FIELD' => 'BYUR.ENTITY', 'TYPE' => 'string'),
            "NOTE" => array('FIELD' => 'BYUR.NOTE', 'TYPE' => 'string'),
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

    public static function Add($arFields = array()){
        global $DB;
        $iResult = 0;
        if(!empty($arFields)){
            $arCFields = static::GetFields();
            $arDataInsert = array();
            foreach ($arCFields as $sIdCField => $arConfig) {
                if($sIdCField == 'ID') continue;
                $arDataInsert[$sIdCField] = "'" . $DB->ForSql($arFields[$sIdCField]) . "'";
            }
            $iResult = $DB->Insert(static::TABLE_NAME, $arDataInsert);
        }
        return $iResult;
    }

    public static function DeletebyArray($arId){
        global $DB;
        $iResult = 0;
        if(!empty($arId)){
            $sWhere = '(' . implode(",", $arId) . ')';
            $sQuery = "DELETE FROM " . static::TABLE_NAME . " WHERE ID in" . $sWhere;
            $iResult = $DB->Query($sQuery);
        }
        return $iResult;
    }

    public static function delete($arFilter = array()){
        global $DB;
        $bResult = false;
        if(!empty($arFilter)){
            $arCFields = static::GetFields();
            $sWhere = '';
            foreach ($arFilter as $sKey => $itemFilter) {
                if(array_key_exists($sKey, $arCFields)){
                    if(is_array($itemFilter)){
                        $sSubFilter = '';
                        foreach ($itemFilter as $itemValue) {
                           $sSubFilter .= "'" . $DB->ForSql($itemValue) . "', ";
                        }
                        $sSubFilter = rtrim($sSubFilter, ', ');
                        if(strlen($sSubFilter) > 0){
                            $sWhere .= $sKey . " IN (" . $sSubFilter . ") AND ";
                        }
                    }
                    else {
                        $sWhere .= $sKey . " = '" . $itemFilter . "' AND ";
                    }
                }
            }
            $sWhere = rtrim($sWhere, 'AND ');
            if(strlen($sWhere) > 0){
                $sQuery = "DELETE FROM " . static::TABLE_NAME . " WHERE " . $sWhere;
                $iResult = $DB->Query($sQuery);
            }
        }
        return $bResult;
    }
}
?>