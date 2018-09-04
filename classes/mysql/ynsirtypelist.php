<?php

class YNSIRTypelist
{

    private static $TABLE_LIST = 'b_hrm_type_list';
    const DB_TYPE = 'MYSQL';
    const TABLE_ALIAS = 'YTL';
    private static $FIELD_TYPE_LIST = null;

    static function GetTypeListFields()
    {
        $createdByJoin = 'LEFT JOIN b_user U ON YTL.CREATED_BY = U.ID';
        $modifiedByJoin = 'LEFT JOIN b_user UM ON YTL.MODIFIED_BY = UM.ID';
        if (!isset(self::$FIELD_TYPE_LIST)) {
            self::$FIELD_TYPE_LIST = array(
                "ID" => array('FIELD' => 'YTL.ID', 'TYPE' => 'int'),
                "NAME_VN" => array('FIELD' => 'YTL.NAME_VN', 'TYPE' => 'string'),
                "NAME_EN" => array('FIELD' => 'YTL.NAME_EN', 'TYPE' => 'string'),
                "ENTITY" => array('FIELD' => 'YTL.ENTITY', 'TYPE' => 'string'),
                "SORT" => array('FIELD' => 'YTL.SORT', 'TYPE' => 'int'),
                "CODE" => array('FIELD' => 'YTL.CODE', 'TYPE' => 'string'),
                "ADDITIONAL_INFO" => array('FIELD' => 'YTL.ADDITIONAL_INFO', 'TYPE' => 'int'),
                "ADDITIONAL_INFO_LABEL_EN" => array('FIELD' => 'YTL.ADDITIONAL_INFO_LABEL_EN', 'TYPE' => 'string'),
                "ADDITIONAL_INFO_LABEL_VN" => array('FIELD' => 'YTL.ADDITIONAL_INFO_LABEL_VN', 'TYPE' => 'string'),
                "CATEGORY" => array('FIELD' => 'YTL.CATEGORY', 'TYPE' => 'int'),
                "MODIFIED_DATE" => array('FIELD' => 'YTL.MODIFIED_DATE', 'TYPE' => 'datetime'),
                "CREATED_DATE" => array('FIELD' => 'YTL.CREATED_DATE', 'TYPE' => 'datetime'),

                "CREATED_BY" => array('FIELD' => 'YTL.CREATED_BY', 'TYPE' => 'int'),
                'CREATED_BY_LOGIN' => array('FIELD' => 'U.LOGIN', 'TYPE' => 'string', 'FROM' => $createdByJoin),
                'CREATED_BY_NAME' => array('FIELD' => 'U.NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
                'CREATED_BY_LAST_NAME' => array('FIELD' => 'U.LAST_NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
                'CREATED_BY_SECOND_NAME' => array('FIELD' => 'U.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
                'CREATED_BY_PERSONAL_PHOTO' => array('FIELD' => 'U.PERSONAL_PHOTO', 'TYPE' => 'int', 'FROM' => $createdByJoin),

                "MODIFIED_BY" => array('FIELD' => 'YTL.MODIFIED_BY', 'TYPE' => 'int'),
                'MODIFIED_BY_LOGIN' => array('FIELD' => 'UM.LOGIN', 'TYPE' => 'string', 'FROM' => $modifiedByJoin),
                'MODIFIED_BY_NAME' => array('FIELD' => 'UM.NAME', 'TYPE' => 'string', 'FROM' => $modifiedByJoin),
                'MODIFIED_BY_LAST_NAME' => array('FIELD' => 'UM.LAST_NAME', 'TYPE' => 'string', 'FROM' => $modifiedByJoin),
                'MODIFIED_BY_SECOND_NAME' => array('FIELD' => 'UM.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $modifiedByJoin),
                'MODIFIED_BY_PERSONAL_PHOTO' => array('FIELD' => 'UM.PERSONAL_PHOTO', 'TYPE' => 'int', 'FROM' => $modifiedByJoin),
            );
        }
        return self::$FIELD_TYPE_LIST;
    }

    /**
     * insert new object, return new id
     * throw error if having errors
     * @param $aParams
     * @return integer
     */
    public static function Add($arFields)
    {

        global $DB, $USER;
        $err_mess = "<br>Function: Add<br>Line: ";


        //add new keyskill
        $arInsert = array(
            'NAME_VN' => "'" . $DB->ForSql($arFields['NAME_VN']) . "'",
            'NAME_EN' => "'" . $DB->ForSql($arFields['NAME_EN']) . "'",
            'ENTITY' => "'" . $DB->ForSql($arFields['ENTITY']) . "'",
            'SORT' => "'" . $DB->ForSql($arFields['SORT']) . "'",
            'CODE' => "'" . $DB->ForSql($arFields['CODE']) . "'",
            'ADDITIONAL_INFO' => "'" . $DB->ForSql($arFields['ADDITIONAL_INFO']) . "'",
            'ADDITIONAL_INFO_LABEL_EN' => "'" . $DB->ForSql($arFields['ADDITIONAL_INFO_LABEL_EN']) . "'",
            'ADDITIONAL_INFO_LABEL_VN' => "'" . $DB->ForSql($arFields['ADDITIONAL_INFO_LABEL_VN']) . "'",
            'CATEGORY' => "'" . $DB->ForSql($arFields['CATEGORY']) . "'",
        );

        if (is_object($USER)) {
            if (!isset($arInsert["CREATED_BY"]) || intval($arInsert["CREATED_BY"]) <= 0)
                $arInsert["CREATED_BY"] = intval($USER->GetID());
            if (!isset($arFields["MODIFIED_BY"]) || intval($arFields["MODIFIED_BY"]) <= 0)
                $arInsert["MODIFIED_BY"] = intval($USER->GetID());
        }

        $arInsert["MODIFIED_DATE"] = $arInsert["CREATED_DATE"] = $DB->CurrentTimeFunction();

        if (is_set($arFields['IN_SUGGEST'])) {
            $arInsert['IN_SUGGEST'] = "'" . $DB->ForSql($arFields['IN_SUGGEST']) . "'";
        }
        $ID = $DB->Insert(self::$TABLE_LIST, $arInsert, $err_mess . __LINE__);

        return $ID;
    }

    /**
     * update an existed object, return boolean
     * throw error if having problems
     * @param $id
     * @param $aParams
     * @return $id
     * add by nhatth 23/2/2017
     */

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
        $query = "DELETE FROM `b_hrm_type_list` WHERE ID=" . $id;
        $ID = $DB->Query($query);

        $deleteRef = "DELETE FROM `b_ynsir_candidate_field_multiple` WHERE  CONTENT = " . $id;
        YNSIRJobOrderTemplate::deleteByCategory($id);
        $DB->Query($deleteRef);
        return $ID;
    }

    public static function DeletebyArray($arID, $entity = '')
    {
        global $DB;
        if (empty($arID)) return;
        foreach ($arID as $id) {
            $ID = self::Delete($id, $entity);
        }
        return $ID;
    }

    /**
     * search list objects and return
     * @param $arParams
     * @param int $iPage
     * @param int $iLimit
     * @return CDBResult
     * add by nhatth 23/2/2017
     */

    public static function GetList($arOrder = array("ID" => "DESC"), $arFilter, $arGroupBy = false, $arNavStartParams = false, $arOptions, $useConfigSort = true)
    {
        if ($useConfigSort) {
//            $arSortDefault = Unserialize(COption::GetOptionString("ynsirecruitment", "ynsir_list_sort"));
//            $arOrder = array_merge($arSortDefault, $arOrder);
            $arOrder = array('SORT'=>'ASC');
        }
        $arField = self::GetTypeListFields();
        $lb = new YNSIRSQLHelper(
            static::DB_TYPE,
            static::$TABLE_LIST,
            static::TABLE_ALIAS,
            $arField
        );

        return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, array_keys($arField), $arOptions);
    }

    public static function getSlug($str)
    {
        $str = htmlspecialchars_decode($str);
        $unicodes = array(
            "a" => "á|à|ạ|ả|ã|ă|ắ|ằ|ặ|ẳ|ẵ|â|ấ|ầ|ậ|ẩ|ẫ|Á|À|Ạ|Ả|Ã|Ă|Ắ|Ằ|Ặ|Ẳ|Ẵ|Â|Ấ|Ầ|Ậ|Ẩ|Ẫ",
            "o" => "ó|ò|ọ|ỏ|õ|ô|ố|ồ|ộ|ổ|ỗ|ơ|ớ|ờ|ợ|ở|ỡ|Ó|Ò|Ọ|Ỏ|Õ|Ô|Ố|Ồ|Ộ|Ổ|Ỗ|Ơ|Ớ|Ờ|Ợ|Ở|Ỡ",
            "e" => "é|è|ẹ|ẻ|ẽ|ê|ế|ề|ệ|ể|ễ|É|È|Ẹ|Ẻ|Ẽ|Ê|Ế|Ề|Ệ|Ể|Ễ",
            "u" => "ú|ù|ụ|ủ|ũ|ư|ứ|ừ|ự|ử|ữ|Ú|Ù|Ụ|Ủ|Ũ|Ư|Ứ|Ừ|Ự|Ử|Ữ",
            "i" => "í|ì|ị|ỉ|ĩ|Í|Ì|Ị|Ỉ|Ĩ",
            "y" => "ý|ỳ|ỵ|ỷ|ỹ|Ý|Ỳ|Ỵ|Ỷ|Ỹ",
            "d" => "đ|Đ",
        );
        foreach ($unicodes as $ascii => $unicode) {
            $str = preg_replace("/({$unicode})/miU", $ascii, $str);
        }
        $str = preg_replace('/[^a-zA-Z0-9\-]+/miU', '-', $str);

        return strtolower(trim($str, '-'));
    }
}