<?php

/*
 * CRM Mail template
 */

class YNSIRMailTemplate
{
    // FIELDS -->
    const TABLE_NAME = 'b_ynsir_mail_template';
    const DB_TYPE = 'MYSQL';
    const CACHE_NAME = 'YNSIR_MAIL_TEMPLATE_CACHE';
    const TABLE_ALIAS = 'T';
    private static $FIELDS = null;
    private static $ERRORS = array();

    // <-- FIELDS
    protected static function GetFields()
    {
        if (!isset(self::$FIELDS)) {
            $ownerJoin = 'LEFT JOIN b_user U1 ON T.OWNER_ID = U1.ID';

            self::$FIELDS = array(
                'ID' => array('FIELD' => 'T.ID', 'TYPE' => 'int'),
                'OWNER_ID' => array('FIELD' => 'T.OWNER_ID', 'TYPE' => 'int'),
                'OWNER_LOGIN' => array('FIELD' => 'U1.LOGIN', 'TYPE' => 'string', 'FROM' => $ownerJoin),
                'OWNER_NAME' => array('FIELD' => 'U1.NAME', 'TYPE' => 'string', 'FROM' => $ownerJoin),
                'OWNER_LAST_NAME' => array('FIELD' => 'U1.LAST_NAME', 'TYPE' => 'string', 'FROM' => $ownerJoin),
                'OWNER_SECOND_NAME' => array('FIELD' => 'U1.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $ownerJoin),
                'ENTITY_TYPE_ID' => array('FIELD' => 'T.ENTITY_TYPE_ID', 'TYPE' => 'int'),
                'SCOPE' => array('FIELD' => 'T.SCOPE', 'TYPE' => 'int'),
                'IS_ACTIVE' => array('FIELD' => 'T.IS_ACTIVE', 'TYPE' => 'char'),
                'TITLE' => array('FIELD' => 'T.TITLE', 'TYPE' => 'string'),
                'EMAIL_FROM' => array('FIELD' => 'T.EMAIL_FROM', 'TYPE' => 'string'),
                'SUBJECT' => array('FIELD' => 'T.SUBJECT', 'TYPE' => 'string'),
                'BODY' => array('FIELD' => 'T.BODY', 'TYPE' => 'string'),
                'SING_REQUIRED' => array('FIELD' => 'T.SING_REQUIRED', 'TYPE' => 'char'),
                'SORT' => array('FIELD' => 'T.SORT', 'TYPE' => 'int'),
                'CREATED' => array('FIELD' => 'T.CREATED', 'TYPE' => 'datetime'),
                'LAST_UPDATED' => array('FIELD' => 'T.LAST_UPDATED', 'TYPE' => 'datetime'),
                'AUTHOR_ID' => array('FIELD' => 'T.AUTHOR_ID', 'TYPE' => 'int'),
                'EDITOR_ID' => array('FIELD' => 'T.EDITOR_ID', 'TYPE' => 'int')
            );
        }

        return self::$FIELDS;
    }

    // CRUD -->
    public static function Add(&$arFields, $options = null)
    {
        global $DB,$USER;

        self::ClearErrors();

        if (!is_array($options)) {
            $options = array();
        }

        if (!self::CheckFields('ADD', $arFields, 0)) {
             return false;
        }

        if (isset($arFields['ID'])) {
            unset($arFields['ID']);
        }

        if (!isset($arFields['SORT'])) {
            $arFields['SORT'] = 100;
        }

        if (!isset($arFields['SCOPE']) || !YNSIRMailTemplateScope::IsDefined($arFields['SCOPE'])) {
            $arFields['SCOPE'] = YNSIRMailTemplateScope::Personal;
        }

        if (!isset($arFields['IS_ACTIVE'])) {
            $arFields['IS_ACTIVE'] = 'N';
        }

        if (!isset($arFields['TITLE'])) {
            $arFields['TITLE'] = '';
        }

        if (!isset($arFields['EMAIL_FROM'])) {
            $arFields['EMAIL_FROM'] = '';
        }

        if (!isset($arFields['SUBJECT'])) {
            $arFields['SUBJECT'] = '';
        }

        if (!isset($arFields['BODY'])) {
            $arFields['BODY'] = '';
        }

        if (!isset($arFields['SING_REQUIRED'])) {
            $arFields['SING_REQUIRED'] = 'N';
        }

        if (isset($arFields['CREATED'])) {
            unset($arFields['CREATED']);
        }

        if (isset($arFields['LAST_UPDATED'])) {
            unset($arFields['LAST_UPDATED']);
        }

        $arFields['~CREATED'] = $arFields['~LAST_UPDATED'] = $DB->CurrentTimeFunction();

        $currentUserID = isset($options['CURRENT_USER_ID']) ? intval($options['CURRENT_USER_ID']) : $USER->GetID();

        if (!isset($arFields['AUTHOR_ID'])) {
            $arFields['AUTHOR_ID'] = $currentUserID;
        }

        $arFields['EDITOR_ID'] = $arFields['AUTHOR_ID'];

        $ID = $DB->Add(YNSIRMailTemplate::TABLE_NAME, $arFields, array('BODY'));
        if ($ID === false) {
            self::RegisterError(array('text' => 'DB connection was lost.'));
            return false;
        }

        $arFields['ID'] = $ID = intval($ID);
        return $ID;
    }

    public static function getEntity($entity)
    {
        if ($entity == 'OWNER_TYPE') {
            $rs = array(
                YNSIROwnerType::Candidate => 'Candidate',
                YNSIROwnerType::Order => 'Job Order'
            );
        } else {
            $rs = array(
                array(
                    'typeId' => YNSIROwnerType::Candidate,
                    'typeName' => YNSIR_CANDIDATE,//GetMessage('YNSIR_EMAIL_TEMPLATE_CANDIDATE'),
                    'fields' =>
                        array(
                            array('id' => 'ID', 'name' => GetMessage('YNSIR_EMAIL_TEMPLATE_ID')),
                            array('id' => 'FULL_NAME', 'name' => GetMessage('YNSIR_EMAIL_TEMPLATE_FULL_NAME')),
                            array('id' => 'FIRST_NAME', 'name' => GetMessage('YNSIR_EMAIL_TEMPLATE_FIRST_NAME')),
                            array('id' => 'LAST_NAME', 'name' => GetMessage('YNSIR_EMAIL_TEMPLATE_LAST_NAME')),
                            array('id' => 'CURRENT_EMPLOYER', 'name' => GetMessage('YNSIR_EMAIL_TEMPLATE_CURRENT_EMPLOYER')),
                            array('id' => 'APPLY_POSITION', 'name' => GetMessage('YNSIR_EMAIL_TEMPLATE_APPLY_POSITION')),
                            array('id' => 'CANDIDATE_STATUS', 'name' => GetMessage('YNSIR_EMAIL_TEMPLATE_CANDIDATE_STATUS')),
                        )
                ),
                array(
                    'typeId' => YNSIROwnerType::Order,
                    'typeName' => YNSIR_JOB_ORDER,//GetMessage('YNSIR_EMAIL_TEMPLATE_JOP_ORDER'),
                    'fields' =>
                        array(
                            array('id' => 'ID', 'name' => GetMessage('YNSIR_EMAIL_TEMPLATE_ID')),
                            array('id' => 'TITLE', 'name' => GetMessage('YNSIR_EMAIL_TEMPLATE_TITLE')),
                            array('id' => 'ROUND_STATUS', 'name' => GetMessage('YNSIR_EMAIL_TEMPLATE_ROUND_STATUS')),
                        )

                )
            );
        }
        return $rs;
    }

    public static function Update($ID, &$arFields, $options = null)
    {
        global $DB, $USER;

        self::ClearErrors();

        if (!is_array($options)) {
            $options = array();
        }

        if (!self::CheckFields('UPDATE', $arFields, $ID)) {
            return false;
        }

        if (isset($arFields['SCOPE']) && !YNSIRMailTemplateScope::IsDefined($arFields['SCOPE'])) {
            $arFields['SCOPE'] = YNSIRMailTemplateScope::Personal;
        }

        if (isset($arFields['CREATED'])) {
            unset($arFields['CREATED']);
        }

        if (isset($arFields['LAST_UPDATED'])) {
            unset($arFields['LAST_UPDATED']);
        }

        $arFields['~LAST_UPDATED'] = $DB->CurrentTimeFunction();

        if (isset($arFields['AUTHOR_ID'])) {
            unset($arFields['AUTHOR_ID']);
        }

        $currentUserID = isset($options['CURRENT_USER_ID']) ? intval($options['CURRENT_USER_ID']) : $USER->GetID();

        if (!isset($arFields['EDITOR_ID'])) {
            $arFields['EDITOR_ID'] = $currentUserID;
        }

        $arRecordBindings = array();
        if (isset($arFields['BODY'])) {
            $arRecordBindings['BODY'] = $arFields['BODY'];
        }

        $tableName = YNSIRMailTemplate::TABLE_NAME;
        $sql = 'UPDATE ' . $tableName . ' SET ' . $DB->PrepareUpdate($tableName, $arFields) . ' WHERE ID = ' . $ID;
        if (!empty($arRecordBindings)) {
            $DB->QueryBind($sql, $arRecordBindings, false);
        } else {
            $DB->Query($sql, false, 'File: ' . __FILE__ . '<br>Line: ' . __LINE__);
        }
        return true;
    }

    public static function DeActivebyArray($arID, $options = null){

        foreach ($arID as $id){
            $stt = self::Delete($id);
        }
        return true;
    }
    public static function Delete($ID, $options = null)
    {
        global $DB;
         $result = $DB->Query('DELETE FROM '.YNSIRMailTemplate::TABLE_NAME.' WHERE ID = '.$ID, true) !== false;

//        $result = $DB->Query('UPDATE ' . YNSIRMailTemplate::TABLE_NAME . ' SET IS_ACTIVE="N" WHERE ID = ' . $ID, true) !== false;

        return $result;
    }

    // <-- CRUD
    // Service -->
    protected static function RegisterError($error)
    {
        self::$ERRORS[] = $error;
    }

    protected static function ClearErrors()
    {
        if (!empty(self::$ERRORS)) {
            self::$ERRORS = array();
        }
    }

    public static function CheckFields($action, &$arFields, $ID)
    {
        self::ClearErrors();

        //global $DB;
        if (!(is_array($arFields) && count($arFields) > 0)) {
            self::RegisterError(array('text' => 'Fields is not specified.'));
            return false;
        }

        if ($action == 'ADD') {
            if (!(isset($arFields['OWNER_ID']) && $arFields['OWNER_ID'] > 0)) {
                self::RegisterError(
                    new YNSIRMailTemplateError(
                        YNSIRMailTemplateError::FieldNotSpecified, array('FIEILD_ID' => 'OWNER_ID')
                    )
                );
            }

            if (!(isset($arFields['ENTITY_TYPE_ID']) && $arFields['ENTITY_TYPE_ID'] > 0)) {
                self::RegisterError(
                    new YNSIRMailTemplateError(
                        YNSIRMailTemplateError::FieldNotSpecified, array('FIEILD_ID' => 'ENTITY_TYPE_ID')
                    )
                );
            }

            if (!(isset($arFields['TITLE']) && $arFields['TITLE'] !== '')) {
                self::RegisterError(
                    new YNSIRMailTemplateError(
                        YNSIRMailTemplateError::FieldNotSpecified, array('FIEILD_ID' => 'TITLE')
                    )
                );
            }
        } else//if($action == 'UPDATE')
        {
            if (!self::Exists($ID)) {
                self::RegisterError(
                    new YNSIRMailTemplateError(
                        YNSIRMailTemplateError::NotExists, array('ID' => $ID)
                    )
                );
            }

            if (isset($arFields['TITLE']) && $arFields['TITLE'] === '') {
                self::RegisterError(
                    new YNSIRMailTemplateError(
                        YNSIRMailTemplateError::FieldNotSpecified, array('FIEILD_ID' => 'TITLE')
                    )
                );
            }
        }
        return self::GetErrorCount() == 0;
    }

    public static function GetErrorCount()
    {
        return count(self::$ERRORS);
    }

    public static function GetErrors()
    {
        return self::$ERRORS;
    }

    public static function GetErrorMessages()
    {
        $result = array();
        foreach (self::$ERRORS as &$error) {
            $result[] = $error->GetText();
        }
        unset($error);
        return $result;
    }
    // <-- Service
    // Contract -->
//    public static function GetList($arOrder = array("ID" => "DESC"), $arFilter, $arGroupBy = false,$arNavStartParams = false, $arOptions)

    public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arOptions)
    {
        $lb = new YNSIRSQLHelper(
            YNSIRMailTemplate::DB_TYPE,
            YNSIRMailTemplate::TABLE_NAME,
            self::TABLE_ALIAS,
            self::GetFields(),
            '',
            ''
        );
        $arSelectFields = array_keys(self::GetFields());
        return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields,$arOptions);


//        return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, array_keys($arField), $arOptions);
    }

    public static function GetByID($ID)
    {
        $ID = intval($ID);

        if ($ID <= 0) {
            return null;
        }

        $dbRes = self::GetList(array(), array('ID' => $ID));

        $res = $dbRes->Fetch();
        return $res;
    }

    public static function Exists($ID)
    {
        $ID = intval($ID);
        if ($ID <= 0) {
            return false;
        }

        $dbRes = self::GetList(array(), array('ID' => $ID), false, false, array('ID'));
        return is_array($dbRes->Fetch());
    }

    public static function GetLastUsedTemplateID($entityTypeID, $userID = 0)
    {
        global $USER;
        $entityTypeID = intval($entityTypeID);
        if (!YNSIROwnerType::IsDefined($entityTypeID)) {
            return 0;
        }

        $entityTypeName = strtolower(YNSIROwnerType::ResolveName($entityTypeID));
        $userID = intval($userID);
        if ($userID <= 0) {
            $userID = $USER->GetID();
        }

        return intval(CUserOptions::GetOption('ynsirecruitment', "last_used_email_template_{$entityTypeName}", 0, $userID));
    }

    public static function SetLastUsedTemplateID($templateID, $entityTypeID, $userID = 0)
    {
        $templateID = intval($templateID);
        $entityTypeID = intval($entityTypeID);
        $entityTypeName = strtolower(YNSIROwnerType::ResolveName($entityTypeID));
        $userID = intval($userID);
        if ($userID <= 0) {
            $userID = CCrmSecurityHelper::GetCurrentUserID();
        }

        $key = "last_used_email_template_{$entityTypeName}";
        if ($templateID <= 0) {
            CUserOptions::DeleteOption('ynsirecruitment', $key, false, $userID);
        }

        if ($templateID !== intval(CUserOptions::GetOption('ynsirecruitment', $key, 0, $userID))) {
            CUserOptions::SetOption('ynsirecruitment', $key, $templateID, false, $userID);
        }
    }

    // <-- Contract

    public static function ConvertHtmlToBbCode($html)
    {
        $eventID = AddEventHandler('main', 'TextParserBeforeTags', Array('CAllCrmMailTemplate', '__ConvertHtmlToBbCode'));

        $parser = new CTextParser();
        $parser->allow = array(
            'HTML' => 'N', 'ANCHOR' => 'Y', 'BIU' => 'Y',
            'IMG' => 'Y', 'QUOTE' => 'Y', 'CODE' => 'Y',
            'FONT' => 'Y', 'LIST' => 'Y', 'SMILES' => 'Y',
            'NL2BR' => 'Y', 'VIDEO' => 'Y', 'TABLE' => 'Y',
            'CUT_ANCHOR' => 'Y', 'ALIGN' => 'Y'
        );

        $html = $parser->convertText($html);
        $html = htmlspecialcharsback($html);
        $html = preg_replace("/\<br\s*\/*\>/i" . BX_UTF_PCRE_MODIFIER, "\n", $html);
        $html = preg_replace("/&nbsp;/i" . BX_UTF_PCRE_MODIFIER, ' ', $html);
        $html = preg_replace("/\<[^>]+>/" . BX_UTF_PCRE_MODIFIER, '', $html);
        $html = htmlspecialcharsbx($html);

        RemoveEventHandler('main', 'TextParserBeforeTags', $eventID);
        return $html;
    }

    public static function __ConvertHtmlToBbCode(&$text, &$parser)
    {
        $text = preg_replace(array("/\</" . BX_UTF_PCRE_MODIFIER, "/\>/" . BX_UTF_PCRE_MODIFIER), array('<', '>'), $text);
        $text = preg_replace("/\<br\s*\/*\>/i" . BX_UTF_PCRE_MODIFIER, "", $text);
        $text = preg_replace("/\<(\w+)[^>]*\>(.+?)\<\/\\1[^>]*\>/is" . BX_UTF_PCRE_MODIFIER, "\\2", $text);
        $text = preg_replace("/\<*\/li\>/i" . BX_UTF_PCRE_MODIFIER, "", $text);
        $text = str_replace(array("<", ">"), array("<", ">"), $text);
        $parser->allow = array();
        return true;
    }
}

class YNSIRMailTemplateError
{
    // CODES -->
    const None = 0;
    const NotExists = 1;
    const FieldNotSpecified = 2;
    // <-- CODES
    private $code = self::None;
    private $params = array();

    function __construct($code, $params = null)
    {
        $this->code = intval($code);
        if (is_array($params)) {
            $this->params = $params;
        }
    }

    public function GetCode()
    {
        return $this->code;
    }

    private function GetParam($name, $default)
    {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }

    public function GetText()
    {
        IncludeModuleLangFile(__FILE__);
        switch ($this->code) {
            case self::NotExists:
                return GetMessage('YNSIR_MAIL_TEMPLATE_ERROR_NOT_EXISTS', array('#ID#' => $this->GetParam('ID', 0)));
            case self::FieldNotSpecified:
                return GetMessage('YNSIR_MAIL_TEMPLATE_ERROR_FIELD_NOT_SPECIFIED', array('#FIELD#' => GetMessage('YNSIR_MAIL_TEMPLATE_FIELD_' . $this->GetParam('FIEILD_ID', ''))));
            default:
                return '';
        }
    }
}

class YNSIRMailTemplateScope
{
    const Undefined = 0;
    const Personal = 1;
    const Common = 2;
    private static $ALL_DESCRIPTIONS = array();

    public static function IsDefined($scope)
    {
        $scope = intval($scope);
        return $scope >= self::Undefined && $scope <= self::Common;
    }

    public static function GetAllDescriptions()
    {
        if (!self::$ALL_DESCRIPTIONS[LANGUAGE_ID]) {
            IncludeModuleLangFile(__FILE__);
            self::$ALL_DESCRIPTIONS[LANGUAGE_ID] = array(
                //self::Undefined => '',
                self::Personal => GetMessage('YNSIR_MAIL_TEMPLATE_SCOPE_PERSONAL'),
                self::Common => GetMessage('YNSIR_MAIL_TEMPLATE_SCOPE_COMMON')
            );
        }

        return self::$ALL_DESCRIPTIONS[LANGUAGE_ID];
    }

    public static function GetDescription($scope)
    {
        $scope = intval($scope);
        $all = self::GetAllDescriptions();
        return isset($all[$scope]) ? $all[$scope] : '';
    }
}
