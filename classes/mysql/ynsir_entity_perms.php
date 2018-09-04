<?
class YNSIREntityPerms
{
    const TABLE_NAME = 'b_ynsir_entity_perms';
    const DB_TYPE = 'MYSQL';
    const TABLE_ALIAS = 'BYEP';
    const PERMS_ENTITY_TYPE_ASSOCIATE = ENTITY_TYPE_ASSOCIATE;

    protected static $TYPE_NAME_ORDER = YNSIR_JOB_ORDER;
    protected static $TYPE_NAME_CANDIDATE = YNSIR_CANDIDATE;

    static function GetFields()
    {
        $arFields = array(
            "ID" => array('FIELD' => 'BYEP.ID', 'TYPE' => 'int'),
            "ENTITY" => array('FIELD' => 'BYEP.ENTITY', 'TYPE' => 'string'),
            "ENTITY_ID" => array('FIELD' => 'BYEP.ENTITY_ID', 'TYPE' => 'int'),
            "ATTR" => array('FIELD' => 'BYEP.ATTR', 'TYPE' => 'string'),
            "TYPE" => array('FIELD' => 'BYEP.TYPE', 'TYPE' => 'string'),
            "SOURCE" => array('FIELD' => 'BYEP.SOURCE', 'TYPE' => 'string'),
        );

        return $arFields;
    }

    public static function Update($iId = 0, $arFields = array(), $arOldData = array(),$bCompare = true)
    {
        return false;
    }


    public static function Delete($id){
       return false;
    }
    public static function DeletebyArray($arID){
       return false;
    }

    public static function getById($iId = 0, $bConvertDescription = true){
        return false;
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
}
?>