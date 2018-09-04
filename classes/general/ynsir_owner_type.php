<?php

class YNSIROwnerType
{
    const Undefined = 0;
    const Candidate = 1;    // refresh FirstOwnerType and LastOwnerType constants
    const Order = 2;
    const Activity = 3;
    const Company = -1;
    const  Contact = -1;
    const  Lead = -1;
    const  Deal = -1;
    const  Quote = -1;

    const FirstOwnerType = 1;
    const LastOwnerType = 3;

    //Special quasi-type
    const System = 1024;

    const CandidateName = YNSIR_CANDIDATE;
    const OrderName = YNSIR_JOB_ORDER;
    const ActivityName = 'ACTIVITY';
    const SystemName = 'SYSTEM';

    private static $ALL_DESCRIPTIONS = array();
    private static $ALL_CATEGORY_CAPTION = array();
    private static $CAPTIONS = array();
    private static $RESPONSIBLES = array();
    private static $INFOS = array();
    private static $INFO_STUB = null;
    private static $COMPANY_TYPE = null;
    private static $COMPANY_INDUSTRY = null;


    public static function IsDefined($typeID)
    {
        if (!is_int($typeID)) {
            $typeID = (int)$typeID;
        }

        return $typeID === self::System
            || ($typeID >= self::FirstOwnerType && $typeID <= self::LastOwnerType);
    }

    public static function ResolveID($name)
    {
        $name = strtoupper(trim(strval($name)));
        if ($name == '') {
            return self::Undefined;
        }

        switch ($name) {
            case YNSIROwnerTypeAbbr::Candidate:
            case self::CandidateName:
                return self::Candidate;

            case YNSIROwnerTypeAbbr::Order:
            case self::OrderName:
                return self::Order;

            case self::ActivityName:
                return self::Activity;

            case YNSIROwnerTypeAbbr::System:
            case self::SystemName:
                return self::System;

            default:
                return self::Undefined;
        }
    }

    public static function ResolveName($typeID)
    {
        if (!is_numeric($typeID)) {
            return '';
        }

        $typeID = intval($typeID);
        if ($typeID <= 0) {
            return '';
        }

        switch ($typeID) {
            case self::Candidate:
                return self::CandidateName;

            case self::Order:
                return self::OrderName;

            case self::Activity:
                return self::ActivityName;

            case self::System:
                return self::SystemName;

            case self::Undefined:
            default:
                return '';
        }
    }

    public static function GetAllNames()
    {
        return array(
			self::CandidateName, self::OrderName,self::ActivityName,
        );
    }

    public static function GetNames($types)
    {
        $result = array();
        if (is_array($types)) {
            foreach ($types as $typeID) {
                $typeID = intval($typeID);
                $name = self::ResolveName($typeID);
                if ($name !== '') {
                    $result[] = $name;
                }
            }
        }
        return $result;
    }

    public static function GetDescriptions($types)
    {
        $result = array();
        if (is_array($types)) {
            foreach ($types as $typeID) {
                $typeID = intval($typeID);
                $descr = self::GetDescription($typeID);
                if ($descr !== '') {
                    $result[$typeID] = $descr;
                }
            }
        }
        return $result;
    }

    public static function GetAll()
    {
        return array(
			self::Candidate, self::Order, self::Activity,
        );
    }

    public static function GetAllDescriptions()
    {
        if (!self::$ALL_DESCRIPTIONS[LANGUAGE_ID]) {
            IncludeModuleLangFile(__FILE__);
            self::$ALL_DESCRIPTIONS[LANGUAGE_ID] = array(
                self::Candidate => 'Candidate',//GetMessage('YNSIR_OWNER_TYPE_LEAD'),
                self::Order => 'Order',//GetMessage('YNSIR_OWNER_TYPE_DEAL'),
                self::Activity => 'Activity',//GetMessage('YNSIR_OWNER_TYPE_ACTIVITY'),
                self::System => 'System',//GetMessage('YNSIR_OWNER_TYPE_SYSTEM'),
            );
        }

        return self::$ALL_DESCRIPTIONS[LANGUAGE_ID];
    }

    public static function GetAllCategoryCaptions()
    {
        if (!self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID]) {
            IncludeModuleLangFile(__FILE__);
            self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID] = array(
                self::Candidate => 'Candidate',//GetMessage('YNSIR_OWNER_TYPE_LEAD_CATEGORY'),
                self::Order => 'Order',//GetMessage('YNSIR_OWNER_TYPE_DEAL_CATEGORY'),
				self::Activity => 'Activity'//GetMessage('YNSIR_OWNER_TYPE_DEAL_CATEGORY'),
            );
        }
        return self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID];
    }

    public static function GetDescription($typeID)
    {
        $typeID = intval($typeID);
        $all = self::GetAllDescriptions();
        return isset($all[$typeID]) ? $all[$typeID] : '';
    }

    public static function GetListUrl($typeID, $bCheckPermissions = false)
    {
        if (!is_int($typeID)) {
            $typeID = (int)$typeID;
        }

        switch ($typeID) {
            case self::Candidate: {
                /*
                if ($bCheckPermissions && !CCrmLead::CheckReadPermission())
                {
                    return '';
                }

                return CComponentEngine::MakePathFromTemplate(
                    Bitrix\Main\Config\Option::get('crm', 'path_to_lead_list', '/crm/lead/list/', false),
                    array()
                );
                */
            }
            case self::Order: {
                /*
                if ($bCheckPermissions && !CCrmContact::CheckReadPermission())
                {
                    return '';
                }

                return CComponentEngine::MakePathFromTemplate(
                    Bitrix\Main\Config\Option::get('crm', 'path_to_contact_list', '/crm/contact/list/', false),
                    array()
                );
                */
            }
        case self::Activity:
            {
                /*
                if ($bCheckPermissions && !CCrmContact::CheckReadPermission())
                {
                    return '';
                }

                return CComponentEngine::MakePathFromTemplate(
                    Bitrix\Main\Config\Option::get('crm', 'path_to_contact_list', '/crm/contact/list/', false),
                    array()
                );
                */
            }
		}
        return '';
    }
    public static function GetShowUrl($typeID, $ID, $bCheckPermissions = false)
    {
        $typeID = intval($typeID);
        $ID = intval($ID);

        if ($ID <= 0) {
            return '';
        }

        switch ($typeID) {
            case self::Candidate: {
                if ($bCheckPermissions && !YNSIRCandidate::CheckReadPermission($ID))
                {
                    return '';
                }

                return CComponentEngine::MakePathFromTemplate(
                   '/recruitment/candidate/detail/#candidate_id#/',
                    array('candidate_id' => $ID)
                );
            }
            case self::Order: {
                if ($bCheckPermissions && !YNSIRJobOrder::CheckReadPermission($ID))
                {
                    return '';
                }

                return CComponentEngine::MakePathFromTemplate(
                    '/recruitment/job-order/detail/#job_order_id#/',
                   array('job_order_id' => $ID)
               );
            }
            case self::Activity:
            {
                /*
                if ($bCheckPermissions && !CCrmLead::CheckReadPermission($ID))
                {
                    return '';
                }

                return CComponentEngine::MakePathFromTemplate(
                    COption::GetOptionString('crm', 'path_to_lead_show'),
                    array('lead_id' => $ID)
                );
                */
            }
            default:
                return '';
        }
    }
    public static function GetEditUrl($typeID, $ID, $bCheckPermissions = false, array $options = null)
    {
        $typeID = intval($typeID);
        $ID = intval($ID);

        if ($ID <= 0) {
            $ID = 0;
        }

        switch ($typeID) {
            case self::Candidate: {
                /*
                if ($bCheckPermissions && !($ID > 0 ? CCrmLead::CheckUpdatePermission($ID) : CCrmLead::CheckCreatePermission()))
                {
                    return '';
                }

                return CComponentEngine::MakePathFromTemplate(
                    COption::GetOptionString('crm', 'path_to_lead_edit'),
                    array('lead_id' => $ID)
                );
                */
            }
            case self::Order: {
                /*
                if ($bCheckPermissions && !($ID > 0 ? CCrmContact::CheckUpdatePermission($ID) : CCrmContact::CheckCreatePermission()))
                {
                    return '';
                }

                return CComponentEngine::MakePathFromTemplate(
                    COption::GetOptionString('crm', 'path_to_contact_edit'),
                    array('contact_id' => $ID)
                );
                */
            }
            case self::Activity:
            {
                /*
                if ($bCheckPermissions && !($ID > 0 ? CCrmLead::CheckUpdatePermission($ID) : CCrmLead::CheckCreatePermission()))
                {
                    return '';
                }

                return CComponentEngine::MakePathFromTemplate(
                    COption::GetOptionString('crm', 'path_to_lead_edit'),
                    array('lead_id' => $ID)
                );
                */
            }
            default:
                return '';
        }
    }
    public static function GetCaption($typeID, $ID, $checkRights = true, array $options = null)
    {
        $sFormatName = CSite::GetNameFormat(false);
        $typeID = (int)$typeID;
        $ID = (int)$ID;

        if ($ID <= 0) {
            return '';
        }

        $key = "{$typeID}_{$ID}";

        if (isset(self::$CAPTIONS[$key])) {
            return self::$CAPTIONS[$key];
        }

        if ($options === null) {
            $options = array();
        }

        switch ($typeID) {
            case self::Candidate: {
                $arRes = isset($options['FIELDS']) ? $options['FIELDS'] : null;
                if(!is_array($arRes))
                {
                    $dbRes = YNSIRCandidate::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false);
                    $arRes = $dbRes ? $dbRes->Fetch() : null;
                }

                if(!$arRes)
                {
                    return (self::$CAPTIONS[$key] = '');
                }
                else
                {
                    $caption = isset($arRes['TITLE']) ? $arRes['TITLE'] : '';
                    if($caption === '')
                    {
                        $caption = CUser::FormatName(
                            $sFormatName,
                            array(
                                "NAME" => $arRes['FIRST_NAME'],
                                "LAST_NAME" => $arRes['LAST_NAME'],
                            )
                        );
                    }
                    return (self::$CAPTIONS[$key] = $caption);
                }
            }
            case self::Order: {
                /*
                $arRes = isset($options['FIELDS']) ? $options['FIELDS'] : null;
                if(!is_array($arRes))
                {
                    $dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME'));
                    $arRes = $dbRes ? $dbRes->Fetch() : null;
                }

                if(!$arRes)
                {
                    return (self::$CAPTIONS[$key] = '');
                }
                else
                {
                    return (self::$CAPTIONS[$key] =
                        CCrmContact::PrepareFormattedName(
                            array(
                                'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
                                'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
                                'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
                                'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
                            )
                        )
                    );
                }
                */
            }
        }

        return '';
    }

    public static function TryGetEntityInfo($typeID, $ID, &$info, $checkPermissions = true)
    {
        $typeID = intval($typeID);
        $ID = intval($ID);

        if (self::$INFO_STUB === null) {
            self::$INFO_STUB = array('TITLE' => '', 'LEGEND' => '', 'IMAGE_FILE_ID' => 0, 'RESPONSIBLE_ID' => 0, 'SHOW_URL' => '');
        }

        if ($ID <= 0) {
            $info = self::$INFO_STUB;
            return false;
        }

        $key = "{$typeID}_{$ID}";

        if ($checkPermissions && !CCrmAuthorizationHelper::CheckReadPermission($typeID, $ID)) {
            $info = self::$INFO_STUB;
            return false;
        }

        if (isset(self::$INFOS[$key])) {
            if (is_array(self::$INFOS[$key])) {
                $info = self::$INFOS[$key];
                return true;
            } else {
                $info = self::$INFO_STUB;
                return false;
            }
        }

        switch ($typeID) {
            case self::Candidate: {
                /*
                $dbRes = CCrmLead::GetListEx(
                    array(),
                    array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
                    false,
                    false,
                    array('ID', 'HONORIFIC', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'ASSIGNED_BY_ID')
                );
                $arRes = $dbRes ? $dbRes->Fetch() : null;
                if(!is_array($arRes))
                {
                    self::$INFOS[$key] = false;
                    $info = self::$INFO_STUB;
                    return false;
                }

                self::$INFOS[$key] = array(
                    'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
                    'LEGEND' => CCrmLead::PrepareFormattedName($arRes),
                    'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
                    'IMAGE_FILE_ID' => 0,
                    'SHOW_URL' =>
                        CComponentEngine::MakePathFromTemplate(
                            COption::GetOptionString('crm', 'path_to_lead_show'),
                            array('lead_id' => $ID)
                        )
                );

                $info = self::$INFOS[$key];
                return true;
               */
            }
            case self::Order: {
                /*
                $dbRes = CCrmContact::GetListEx(
                    array(),
                    array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
                    false,
                    false,
                    array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE', 'PHOTO', 'ASSIGNED_BY_ID')
                );

                $arRes = $dbRes ? $dbRes->Fetch() : null;
                if(!is_array($arRes))
                {
                    self::$INFOS[$key] = false;
                    $info = self::$INFO_STUB;
                    return false;
                }

                self::$INFOS[$key] = array(
                    'TITLE' => CCrmContact::PrepareFormattedName($arRes),
                    'LEGEND' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
                    'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
                    'IMAGE_FILE_ID' => isset($arRes['PHOTO']) ? intval($arRes['PHOTO']) : 0,
                    'SHOW_URL' =>
                        CComponentEngine::MakePathFromTemplate(
                            COption::GetOptionString('crm', 'path_to_contact_show'),
                            array('contact_id' => $ID)
                        )
                );

                $info = self::$INFOS[$key];
                return true;
                */
            }
        }

        $info = self::$INFO_STUB;
        return false;
    }

    public static function PrepareEntityInfoBatch($typeID, array &$entityInfos, $checkPermissions = true, $options = null)
    {
        if (!is_array($options)) {
            $options = array();
        }

        $IDs = array_keys($entityInfos);
        $dbRes = null;
        switch ($typeID) {
            case self::Candidate: {
                /*
                $dbRes = CCrmLead::GetListEx(
                    array(),
                    array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
                    false,
                    false,
                    array('ID', 'HONORIFIC', 'TITLE', 'COMPANY_TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'ASSIGNED_BY_ID')
                );
                */
                break;
            }
            case self::Order: {
                /*
                $dbRes = CCrmContact::GetListEx(
                    array(),
                    array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
                    false,
                    false,
                    array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE', 'PHOTO', 'ASSIGNED_BY_ID')
                );
                */
                break;
            }
        }

        if (!is_object($dbRes)) {
            return;
        }

        $enableResponsible = isset($options['ENABLE_RESPONSIBLE']) && $options['ENABLE_RESPONSIBLE'] === true;
        $userIDs = null;
        while ($arRes = $dbRes->Fetch()) {
            $ID = intval($arRes['ID']);
            if (!isset($entityInfos[$ID])) {
                continue;
            }

            $info = self::PrepareEntityInfo($typeID, $ID, $arRes, $options);
            if (!is_array($info) || empty($info)) {
                continue;
            }

            if ($enableResponsible) {
                $responsibleID = $info['RESPONSIBLE_ID'];
                if ($responsibleID > 0) {
                    if ($userIDs === null) {
                        $userIDs = array($responsibleID);
                    } elseif (!in_array($responsibleID, $userIDs, true)) {
                        $userIDs[] = $responsibleID;
                    }
                }
            }

            $entityInfos[$ID] = array_merge($entityInfos[$ID], $info);
        }

        if ($enableResponsible && is_array($userIDs) && !empty($userIDs)) {
            $enablePhoto = isset($options['ENABLE_RESPONSIBLE_PHOTO']) ? $options['ENABLE_RESPONSIBLE_PHOTO'] : true;
            $userSelect = array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'TITLE', 'EMAIL', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE');
            if ($enablePhoto) {
                $userSelect[] = 'PERSONAL_PHOTO';
            }

            $dbUsers = CUser::GetList(
                ($by = 'id'), ($sort = 'asc'),
                array('ID' => implode('|', $userIDs)),
                array('FIELDS' => $userSelect)
            );

            $photoSize = null;
            if ($enablePhoto) {
                $photoSize = isset($options['PHOTO_SIZE']) ? $options['PHOTO_SIZE'] : array();
                if (!isset($photoSize['WIDTH']) || !isset($photoSize['HEIGHT'])) {
                    if (isset($photoSize['WIDTH'])) {
                        $photoSize['HEIGHT'] = $photoSize['WIDTH'];
                    } elseif (isset($photoSize['HEIGHT'])) {
                        $photoSize['WIDTH'] = $photoSize['HEIGHT'];
                    } else {
                        $photoSize['WIDTH'] = $photoSize['HEIGHT'] = 50;
                    }
                }
            }

            $userInfos = array();
            while ($user = $dbUsers->Fetch()) {
                $userID = intval($user['ID']);
                $personalPhone = isset($user['PERSONAL_PHONE']) ? $user['PERSONAL_PHONE'] : '';
                $personalMobile = isset($user['PERSONAL_MOBILE']) ? $user['PERSONAL_MOBILE'] : '';
                $workPhone = isset($user['WORK_PHONE']) ? $user['WORK_PHONE'] : '';
                $userPhone = $workPhone !== '' ? $workPhone : ($personalMobile !== '' ? $personalMobile : $personalPhone);

                $userInfo = array(
                    'FORMATTED_NAME' => CUser::FormatName(
                        CSite::GetNameFormat(false),
                        $user,
                        true,
                        false
                    ),
                    'EMAIL' => isset($user['EMAIL']) ? $user['EMAIL'] : '',
                    'PHONE' => $userPhone
                );

                if ($enablePhoto) {
                    $photoID = isset($user['PERSONAL_PHOTO']) ? intval($user['PERSONAL_PHOTO']) : 0;
                    if ($photoID > 0) {
                        $photoUrl = CFile::ResizeImageGet(
                            $photoID,
                            array('width' => $photoSize['WIDTH'], 'height' => $photoSize['HEIGHT']),
                            BX_RESIZE_IMAGE_EXACT
                        );
                        $userInfo['PHOTO_URL'] = $photoUrl['src'];
                    }
                }

                $userInfos[$userID] = &$userInfo;
                unset($userInfo);
            }

            if (!empty($userInfos)) {
                foreach ($entityInfos as &$info) {
                    $responsibleID = $info['RESPONSIBLE_ID'];
                    if ($responsibleID > 0 && isset($userInfos[$responsibleID])) {
                        $userInfo = $userInfos[$responsibleID];
                        $info['RESPONSIBLE_FULL_NAME'] = $userInfo['FORMATTED_NAME'];

                        if (isset($userInfo['PHOTO_URL'])) {
                            $info['RESPONSIBLE_PHOTO_URL'] = $userInfo['PHOTO_URL'];
                        }

                        if (isset($userInfo['EMAIL'])) {
                            $info['RESPONSIBLE_EMAIL'] = $userInfo['EMAIL'];
                        }

                        if (isset($userInfo['PHONE'])) {
                            $info['RESPONSIBLE_PHONE'] = $userInfo['PHONE'];
                        }
                    }
                }
                unset($info);
            }
        }
    }

    private static function PrepareEntityInfo($typeID, $ID, &$arRes, $options = null)
    {
        $enableEditUrl = is_array($options) && isset($options['ENABLE_EDIT_URL']) && $options['ENABLE_EDIT_URL'] === true;
        switch ($typeID) {
            case self::Candidate: {
                /*
                $treatAsContact = false;
                $treatAsCompany = false;

                if(is_array($options))
                {
                    $treatAsContact = isset($options['TREAT_AS_CONTACT']) && $options['TREAT_AS_CONTACT'];
                    $treatAsCompany = isset($options['TREAT_AS_COMPANY']) && $options['TREAT_AS_COMPANY'];
                }

                if($treatAsContact)
                {
                    $result = array(
                        'TITLE' => CCrmLead::PrepareFormattedName($arRes),
                        'LEGEND' => isset($arRes['TITLE']) ? $arRes['TITLE'] : ''
                    );
                }
                elseif($treatAsCompany)
                {
                    $result = array(
                        'TITLE' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
                        'LEGEND' => isset($arRes['TITLE']) ? $arRes['TITLE'] : ''
                    );
                }
                else
                {
                    $result = array(
                        'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
                        'LEGEND' => CCrmLead::PrepareFormattedName($arRes)
                    );
                }

                $result['RESPONSIBLE_ID'] = isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0;
                $result['IMAGE_FILE_ID'] = 0;
                $result['SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
                    COption::GetOptionString('crm', 'path_to_lead_show'),
                    array('lead_id' => $ID)
                );

                if($enableEditUrl)
                {
                    $result['EDIT_URL'] =
                        CComponentEngine::MakePathFromTemplate(
                            COption::GetOptionString('crm', 'path_to_lead_edit'),
                            array('lead_id' => $ID)
                        );
                }
                return $result;
                */
            }
            case self::Order: {
                /*
                $result = array(
                    'TITLE' => CCrmContact::PrepareFormattedName($arRes),
                    'LEGEND' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
                    'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
                    'IMAGE_FILE_ID' => isset($arRes['PHOTO']) ? intval($arRes['PHOTO']) : 0,
                    'SHOW_URL' =>
                        CComponentEngine::MakePathFromTemplate(
                            COption::GetOptionString('crm', 'path_to_contact_show'),
                            array('contact_id' => $ID)
                        )
                );
                if($enableEditUrl)
                {
                    $result['EDIT_URL'] =
                        CComponentEngine::MakePathFromTemplate(
                            COption::GetOptionString('crm', 'path_to_contact_edit'),
                            array('contact_id' => $ID)
                        );
                }
                return $result;
                */
            }
        }
        return null;
    }

    public static function ResolveUserFieldEntityID($typeID)
    {
        $typeID = intval($typeID);
        if ($typeID <= 0) {
            return '';
        }

        switch ($typeID) {
//			case self::Lead:
//				return CAllCrmLead::$sUFEntityID;
//			case self::Deal:
//				return CAllCrmDeal::$sUFEntityID;
//			default:
//				return '';
        }
    }

    public static function ResolveIDByUFEntityID($entityTypeID)
    {
//		if($entityTypeID === '')
//		{
//			return '';
//		}
//
//		$requisite = new \Bitrix\Crm\EntityRequisite();
//		$requisiteUfId = $requisite->getUfId();
//		unset($requisite);
//
//		switch($entityTypeID)
//		{
//			case CAllCrmLead::$sUFEntityID:
//				return self::Lead;
//			case CAllCrmDeal::$sUFEntityID:
//				return self::Deal;
//			case CAllCrmContact::$sUFEntityID:
//				return self::Contact;
//			case CAllCrmCompany::$sUFEntityID:
//				return self::Company;
//			case CAllCrmInvoice::$sUFEntityID:
//				return self::Invoice;
//			case CAllCrmQuote::$sUFEntityID:
//				return self::Quote;
//			case $requisiteUfId:
//				return self::Requisite;
//			default:
//				return self::Undefined;
//		}
    }

    private static function GetFields($typeID, $ID, $options = array())
    {
        $typeID = intval($typeID);
        $ID = intval($ID);
        $options = is_array($options) ? $options : array();

        $select = isset($options['SELECT']) ? $options['SELECT'] : array();
        switch ($typeID) {
            case self::Candidate: {
//				$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID), false, false, $select);
                return $dbRes ? $dbRes->Fetch() : null;
            }
            case self::Order: {
//				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID), false, false, $select);
                return $dbRes ? $dbRes->Fetch() : null;
            }
        }

        return null;
    }

    public static function GetFieldsInfo($typeID)
    {
        $typeID = intval($typeID);

        switch ($typeID) {
            case self::Candidate: {
//				return CCrmLead::GetFieldsInfo();
            }
            case self::Order: {
//				return CCrmContact::GetFieldsInfo();
            }
        }

        return null;
    }

    public static function GetFieldIntValue($typeID, $ID, $fieldName)
    {
        $fields = self::GetFields($typeID, $ID, array('SELECT' => array($fieldName)));
        return is_array($fields) && isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
    }

    public static function GetResponsibleID($typeID, $ID, $checkRights = true)
    {
        $typeID = intval($typeID);
        $ID = intval($ID);

        if (!(self::IsDefined($typeID) && $ID > 0)) {
            return 0;
        }

        $key = "{$typeID}_{$ID}";
        if (isset(self::$RESPONSIBLES[$key])) {
            return self::$RESPONSIBLES[$key];
        }

        $result = 0;
        switch ($typeID) {
            case self::Candidate: {
				$dbRes = YNSIRCandidate::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('CREATED_BY'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['CREATED_BY']) : 0;
                break;
            }
            case self::Order: {
				$dbRes = YNSIRJobOrder::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('CREATED_BY'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['CREATED_BY']) : 0;
                break;
            }

        }

        self::$RESPONSIBLES[$key] = $result;
        return $result;
    }

    public static function IsOpened($typeID, $ID, $checkRights = true)
    {
        $typeID = intval($typeID);
        $ID = intval($ID);

        switch ($typeID) {
            case self::Candidate: {
				$dbRes = YNSIRJobOrder::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
                $arRes = $dbRes ? $dbRes->Fetch() : null;
                return $arRes? true:false;
            }
            case self::Order: {
				$dbRes = YNSIRJobOrder::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
                $arRes = $dbRes ? $dbRes->Fetch() : null;
                return $arRes? true:false;
            }
        }

        return false;
    }

    /**
     * Check permission for READ operation.
     * @param int $entityTypeID Entity Type ID.
     * @param int $entityID Entity ID.
     * @return bool
     */
    public function CheckReadPermission($entityTypeID, $entityID)
    {
        /** @var \CCrmPerms $permissions */
//		$permissions = \CCrmPerms::GetCurrentUserPermissions();
//
//		if($entityTypeID === \YNSIROwnerType::Company)
//		{
//			return \CCrmCompany::CheckReadPermission($entityID, $permissions);
//		}
//		elseif($entityTypeID === \YNSIROwnerType::Contact)
//		{
//			return \CCrmContact::CheckReadPermission($entityID, $permissions);
//		}
//		elseif($entityTypeID === \YNSIROwnerType::Deal)
//		{
//			return \CCrmDeal::CheckReadPermission($entityID, $permissions);
//		}
//		elseif($entityTypeID === \YNSIROwnerType::Invoice)
//		{
//			return \CCrmInvoice::CheckReadPermission($entityID, $permissions);
//		}
//		elseif($entityTypeID === \YNSIROwnerType::Quote)
//		{
//			return \CCrmQuote::CheckReadPermission($entityID, $permissions);
//		}
//
//		return \CCrmAuthorizationHelper::CheckReadPermission($entityTypeID, $entityID, $permissions);
        return true;
    }

    public static function TryGetOwnerInfos($typeID, $ID, &$owners, $options = array())
    {
        $typeID = intval($typeID);
        $ID = intval($ID);

        if (!is_array($options)) {
            $options = array();
        }

        $entityTypeIDKey = isset($options['ENTITY_TYPE_ID_KEY']) ? $options['ENTITY_TYPE_ID_KEY'] : '';
        if ($entityTypeIDKey === '') {
            $entityTypeIDKey = 'ENTITY_TYPE_ID';
        }

        $entityIDKey = isset($options['ENTITY_ID_KEY']) ? $options['ENTITY_ID_KEY'] : '';
        if ($entityIDKey === '') {
            $entityIDKey = 'ENTITY_ID';
        }

        $additionalData = isset($options['ADDITIONAL_DATA']) && is_array($options['ADDITIONAL_DATA']) ? $options['ADDITIONAL_DATA'] : null;
        $enableMapping = isset($options['ENABLE_MAPPING']) ? (bool)$options['ENABLE_MAPPING'] : false;

        switch ($typeID) {
            case self::Candidate: {
                /*
                $dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID), false, false, array('COMPANY_ID'));
                $arRes = $dbRes ? $dbRes->Fetch() : null;

                if(!is_array($arRes))
                {
                    return false;
                }

                $companyID = isset($arRes['COMPANY_ID']) ? intval($arRes['COMPANY_ID']) : 0;
                if($companyID <= 0)
                {
                    return false;
                }

                $info = array(
                    $entityTypeIDKey => self::Company,
                    $entityIDKey => $companyID
                );

                if($additionalData !== null)
                {
                    $info = array_merge($info, $additionalData);
                }

                if($enableMapping)
                {
                    $owners[self::Company.'_'.$companyID] = &$info;
                }
                else
                {
                    $owners[] = &$info;
                }
                unset($info);
                */
                return true;
            }
            //break;
            case self::Order: {
                /*
                $dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID), false, false, array('CONTACT_ID', 'COMPANY_ID'));
                $arRes = $dbRes ? $dbRes->Fetch() : null;

                if(!is_array($arRes))
                {
                    return false;
                }

                $contactID = isset($arRes['CONTACT_ID']) ? intval($arRes['CONTACT_ID']) : 0;
                $companyID = isset($arRes['COMPANY_ID']) ? intval($arRes['COMPANY_ID']) : 0;
                if($contactID <= 0 && $companyID <= 0)
                {
                    return false;
                }

                if($contactID > 0)
                {
                    $info = array(
                        $entityTypeIDKey => self::Contact,
                        $entityIDKey => $contactID
                    );

                    if($additionalData !== null)
                    {
                        $info = array_merge($info, $additionalData);
                    }

                    if($enableMapping)
                    {
                        $owners[self::Contact.'_'.$contactID] = &$info;
                    }
                    else
                    {
                        $owners[] = &$info;
                    }
                    unset($info);
                }
                if($companyID > 0)
                {
                    $info =  array(
                        $entityTypeIDKey => self::Company,
                        $entityIDKey => $companyID
                    );

                    if($additionalData !== null)
                    {
                        $info = array_merge($info, $additionalData);
                    }

                    if($enableMapping)
                    {
                        $owners[self::Company.'_'.$companyID] = &$info;
                    }
                    else
                    {
                        $owners[] = &$info;
                    }
                    unset($info);
                }
                */
                return true;
            }
            //break;
        }
        return false;
    }

    public static function TryGetInfo($typeID, $ID, &$info, $bCheckPermissions = false)
    {
        $typeID = intval($typeID);
        $ID = intval($ID);

        if ($ID <= 0) {
            return array();
        }

        $result = null;
        switch ($typeID) {
            case self::Candidate: {
                /*
                $dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('TITLE'));
                $arRes = $dbRes ? $dbRes->Fetch() : null;
                if(is_array($arRes))
                {
                    $info = array(
                        'CAPTION' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
                        'IMAGE_ID' => 0
                    );
                    return true;
                }
                */
                break;
            }
            case self::Order: {
                /*
                $dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'PHOTO'));
                $arRes = $dbRes ? $dbRes->Fetch() : null;
                if(is_array($arRes))
                {
                    $info = array(
                        'CAPTION' => CCrmContact::PrepareFormattedName($arRes),
                        'IMAGE_ID' => isset($arRes['PHOTO']) ? intval($arRes['PHOTO']) : 0
                    );
                    return true;
                }
                break;
                */
            }

        }
        return false;
    }

    public static function GetJavascriptDescriptions()
    {
        return array(
            self::CandidateName => self::GetDescription(self::Candidate),
            self::OrderName => self::GetDescription(self::Order),
        );
    }
}

class YNSIROwnerTypeAbbr
{
    const Undefined = '';
    const Candidate = 'C';
    const Order = 'O';
    const System = 'SYS';

    public static function ResolveByTypeID($typeID)
    {
        if (!is_int($typeID)) {
            $typeID = (int)$typeID;
        }

        switch ($typeID) {
            case YNSIROwnerType::Candidate:
                return self::Candidate;
            case YNSIROwnerType::Order:
                return self::Order;
            case YNSIROwnerType::System:
                return self::System;
            default:
                return self::Undefined;
        }
    }

    public static function ResolveName($abbr)
    {
        if (!is_string($abbr)) {
            $abbr = (string)$abbr;
        }

        $abbr = strtoupper(trim($abbr));
        if ($abbr === '') {
            return '';
        }

        switch ($abbr) {
            case self::Candidate:
                return YNSIROwnerType::CandidateName;
            case self::Order:
                return YNSIROwnerType::OrderName;
        }
        return '';
    }
}

