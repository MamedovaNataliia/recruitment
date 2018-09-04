<?php
IncludeModuleLangFile(__FILE__);

/**
 * GENERAL HELPER
 */

use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Util;

class YNSIRGeneral
{
    const USER_WIDTH_PHOTO = 34;
    const USER_HEIGHT_PHOTO = 34;

    public static function getDepartment($arFilter = array(), $bShowLevel = false)
    {
        $arResult = array();
        $arFilter['IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_structure');
        $arFilter['GLOBAL_ACTIVE'] = 'Y';
        $dbDRes = CIBlockSection::GetList(
            array('left_margin' => asc),
            $arFilter
        );
        while ($arDRes = $dbDRes->Fetch()) {
            $arResult[intval($arDRes['ID'])] = $bShowLevel == true ? str_repeat('.&nbsp;', intval($arDRes['DEPTH_LEVEL'])) . $arDRes['NAME'] : $arDRes['NAME'];
        }
        return $arResult;
    }

    public static function getDepartmentPerms($arFilter = array(), $bShowLevel = false, $sAction = 'ADD')
    {
        global $USER;
        $iIdUser = $USER->GetID();
        $arResult = array();
        // get role for user
        $sPerms = '';
        if ($USER->IsAdmin()) {
            $sPerms = YNSIR_PERM_ALL;
        } else {
            $arPermsRole = YNSIRRole::GetUserPerms($iIdUser);
            $sPerms = isset($arPermsRole[YNSIR_JOB_ORDER][$sAction]['FIELDS'][YNSIRConfig::OS_BASIC_INFO]) ?
                $arPermsRole[YNSIR_JOB_ORDER][$sAction]['FIELDS'][YNSIRConfig::OS_BASIC_INFO]
                : $arPermsRole[YNSIR_JOB_ORDER][$sAction]['-'];
        }
        // get user department
        $rsUser = CUser::GetList(
            ($by = "id"),
            ($order = "asc"),
            array('ID' => $iIdUser),
            array('SELECT' => array('UF_DEPARTMENT'))
        );
        $arRes = $rsUser->Fetch();
        $arUserDept = $arRes['UF_DEPARTMENT'];
        // get all department
        $arFilter['IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_structure');
        $arFilter['GLOBAL_ACTIVE'] = 'Y';
        $dbDRes = CIBlockSection::GetList(
            array('left_margin' => asc),
            $arFilter
        );
        $arDepartment = array();
        while ($arDRes = $dbDRes->Fetch()) {
            if ($sPerms == YNSIR_PERM_ALL) {
                $arResult[intval($arDRes['ID'])] = $bShowLevel == true ? str_repeat('.&nbsp;', intval($arDRes['DEPTH_LEVEL'])) . $arDRes['NAME'] : $arDRes['NAME'];
            } else if ($sPerms == YNSIR_PERM_SELF || $sPerms == YNSIR_PERM_DEPARTMENT) {
                if (in_array($arDRes['ID'], $arUserDept)) {
                    $arResult[intval($arDRes['ID'])] = $bShowLevel == true ? str_repeat('.&nbsp;', intval($arDRes['DEPTH_LEVEL'])) . $arDRes['NAME'] : $arDRes['NAME'];
                }
            } else {
                $arDepartment[intval($arDRes['ID'])] = $arDRes;
            }
        }
        if ($sPerms == YNSIR_PERM_SUBDEPARTMENT && empty($arResult)) {
            foreach ($arUserDept as $iIdDeptUser) {
                if (isset($arDepartment[$iIdDeptUser])) {
                    $arResult[$iIdDeptUser] = $bShowLevel == true ? str_repeat('.&nbsp;', intval($arDepartment[$iIdDeptUser]['DEPTH_LEVEL'])) . $arDepartment[$iIdDeptUser]['NAME'] : $arDepartment[$iIdDeptUser]['NAME'];
                    $bFlag = false;
                    $iLevel = 0;
                    foreach ($arDepartment as $itemDept) {
                        if ($bFlag == true && $itemDept['DEPTH_LEVEL'] <= $iLevel) break;
                        if ($itemDept['ID'] == $iIdDeptUser) {
                            $bFlag = true;
                            $iLevel = $itemDept['DEPTH_LEVEL'];
                        }
                        if ($bFlag == true && $itemDept['DEPTH_LEVEL'] > $iLevel) {
                            $arResult[$itemDept['ID']] = $bShowLevel == true ? str_repeat('.&nbsp;', intval($itemDept['DEPTH_LEVEL'])) . $itemDept['NAME'] : $itemDept['NAME'];
                        }
                    }
                }
            }
        }
        return $arResult;
    }

    public static function getListType($arFilter = array(), $bShort = true)
    {
        $arResult = array();
        $rsRes = YNSIRTypelist::GetList(array('ID' => "ASC"), $arFilter, false);
        while ($arRes = $rsRes->Fetch()) {
            $arResult[$arRes['ID']] = $bShort == true ? $arRes['NAME_' . strtoupper(LANGUAGE_ID)] : $arRes;
        }
        return $arResult;
    }

    public static function getUserInfo($arIdUser = array())
    {
        $arResult = array();
        $arFilter = !empty($arIdUser) ? array('ID' => implode(' | ', $arIdUser)) : array();
        $rsUsers = CUser::GetList(($by = "personal_country"), ($order = "desc"), $arFilter);
        $sFormatFullName = CSite::GetNameFormat(false);
        $objFile = new CFile();
        while ($arUser = $rsUsers->Fetch()) {
            $arUser['FULL_NAME'] = CUser::FormatName($sFormatFullName, $arUser);
            $arFileInfo = $objFile->ResizeImageGet(
                $arUser['PERSONAL_PHOTO'],
                array('width' => static::USER_WIDTH_PHOTO, 'height' => static::USER_HEIGHT_PHOTO),
                BX_RESIZE_IMAGE_EXACT
            );
            $arUser['PHOTO_SRC'] = $arFileInfo['src'];
            $arResult[intval($arUser['ID'])] = $arUser;
        }
        return $arResult;
    }

    public static function getUserNameByID($userID)
    {
        if ($userID <= 0) {
            return;
        }

        $dbResult = \CUser::GetList(
            $by = 'ID',
            $order = 'ASC',
            array('ID' => $userID),
            array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'TITLE'))
        );

        $user = $dbResult->Fetch();
        if (!is_array($user)) {
            return;
        }

        $value = \CUser::FormatName(
            \CSite::GetNameFormat(),
            $user,
            true,
            false
        );

        return $value;
    }

    public static function getListJobStatus($entity = '')
    {
        $arResult = array();
        if ($entity == '') {
            $entity = 'JO_STATUS';
        }
        $res = JOStatus::GetList(array('SORT' => 'ASC'), array('ENTITY_ID' => $entity));
        while ($arData = $res->Fetch()) {
            $arResult[$arData['STATUS_ID']] = $arData['NAME'];
        }
        return $arResult;
    }

    public static function tooltipUser($arData = array(), $idUser = 0, $sIdTooltip = '')
    {
        $sResult = '';
        $sUrlUser = '/company/personal/user/';
        if (empty($arData)) {
            $arTemp = static::getUserInfo(array($idUser));
            $arData = !empty($arTemp) ? $arTemp[0] : array();
        }
        if (!empty($arData)) {
            $sIdTooltip = strlen($sIdTooltip) <= 0 ? 'user_tooltip_' . $arData['ID'] : $sIdTooltip;
            $sResult = '<a href="' . $sUrlUser . $arData['ID'] . '/" title="' . $arData['FULL_NAME'] . '" id="' . $sIdTooltip . '">'
                . $arData['FULL_NAME']
                . '<script>BX.tooltip("' . $arData['ID'] . '", "' . $sIdTooltip . '", "","",false,"");</script>'
                . '</a>';
        }
        return $sResult;
    }

    public static function getHRManager()
    {
        $arConfig = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_hr_manager_config'));
        $iResult = intval($arConfig[0]);
        return $iResult > 0 ? $iResult : 1;
    }

    public static function sendNotifyJOTemplate($arData = array())
    {
        global $USER;
        if (!empty($arData)) {
            $iIdUser = $USER->GetID();
            $iIdHRManager = static::getHRManager();
            if ($iIdUser != $iIdHRManager) {
                $arInfoUser = static::getUserInfo(array($iIdUser));
                $sNMess = GetMessage("YNSIR_GCG_ACTIVE_JO_TEMPLATE", array(
                    "#CATEGORY#" => $arData['CATEGORY'],
                    "#NAME_TEMPLATE#" => $arData['NAME_TEMPLATE'],
                    "#TEMPLATE_ID#" => $arData['ID_TEMPLATE'],
                    "#USER_ID#" => $iIdUser,
                    "#FULL_NAME#" => $arInfoUser[$iIdUser]['FULL_NAME'],
                ));
                CIMNotify::Add(Array(
                    'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
                    'NOTIFY_MESSAGE' => $sNMess,
                    'NOTIFY_MESSAGE_OUT' => 'Notification for recruitment module',
                    'NOTIFY_MODULE' => 'main',
                    'NOTIFY_EVENT' => 'send_notify',
                    'TO_USER_ID' => $iIdHRManager,
                    'NOTIFY_TAG' => YNSIR_MODULE_ID . '|JOB_ORDER_TEMPLATE' . '|ADD|' . $arData['ID_TEMPLATE'],
                ));
            }
        }
    }

    //TASK AUTO CREATE
    /* Module IM callback */
    function OnBeforeConfirmNotify($module, $tag, $value, $arParams)
    {
        global $USER;

        if ($module == "calendar") {
            $arTag = explode("|", $tag);
            if (count($arTag) == 4 && intval($arTag[2]) > 0) {
                if ($value == 'Y') {
                    //Create task if not existed
                    return self::AutoImplementTask($arTag[2]);
//                    Update task if existed
                } else {

                    return true;
                }
            }
        }
    }

    function OnSendInvitationMessage($arParams)
    {
        global $USER;

        if (intval($arParams['eventId']) > 0) {
            if ($arParams['mode'] == 'status_accept') {
                //Create task if not existed
                return self::AutoImplementTask($arParams['eventId']);
            } elseif ($arParams['mode'] == 'status_decline') {
                //remove participand if existed
                return self::RemoveTaskParticipant($arParams['eventId']);
            }
        }
    }

    static function RemoveTaskParticipant($iCanlendarID) {
        global $USER;
        CModule::IncludeModule('tasks');
        $dbEntity = CAllYNSIRActivity::GetList(
            array(),
            array('CALENDAR_EVENT_ID' => $iCanlendarID),
            false,
            false
        );

        $arEntity = $dbEntity->Fetch();
        if (!is_array($arEntity)) {
            return false;
        } else if (!empty($arEntity)) {
            //Find exist Task
            $arFilter = array(
                'PARENT_AUTO_ID' => $arEntity['ID'],
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
                if (in_array(CUser::GetId(), $arAccomplice)) {
                    $arInputAccomplice = array();
                    foreach ($arAccomplice as $uID) {
                        if($uID ==CUser::GetId()) continue;
                        $arInputAccomplice[] = array(
                            'ID' => $uID,
                            'NAME' => '',
                            'LAST_NAME' => '',
                            'EMAIL' => '',
                        );
                    }
                    $arData = array(
                        'SE_ACCOMPLICE' =>
                            $arInputAccomplice
                    );


                    $mgrResult = Manager\Task::update(1/*$arEntity['AUTHOR_ID']*/, $arEntityAct['ASSOCIATED_ENTITY_ID'], $arData, array(
                        'PUBLIC_MODE' => true,
                        'ERRORS' => $error,
                        'THROTTLE_MESSAGES' => $parameters['THROTTLE_MESSAGES'],
                        // there also could be RETURN_CAN or RETURN_DATA, or both as RETURN_ENTITY
                        'RETURN_ENTITY' => $parameters['RETURN_ENTITY'],
                    ));
                }
            }
            return true;
        }
    }

    static function AutoImplementTask($iCanlendarID)
    {
        global $USER;
        CModule::IncludeModule('tasks');
        $dbEntity = CAllYNSIRActivity::GetList(
            array(),
            array('CALENDAR_EVENT_ID' => $iCanlendarID),
            false,
            false
        );

        $arEntity = $dbEntity->Fetch();
        if (!is_array($arEntity)) {
            return false;
        } else if (!empty($arEntity)) {

            //Find exist Task
            $arFilter = array(
                'PARENT_AUTO_ID' => $arEntity['ID'],
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
                if (!in_array(CUser::GetId(), $arAccomplice)) {
                    $arInputAccomplice = array();
                    foreach ($arAccomplice as $uID) {
                        $arInputAccomplice[] = array(
                            'ID' => $uID,
                            'NAME' => '',
                            'LAST_NAME' => '',
                            'EMAIL' => '',
                        );
                    }
                    $arData = array(
                        'SE_ACCOMPLICE' => array_merge(
                            $arInputAccomplice, array(
                                array(
                                    'ID' => CUser::GetId(),
                                    'NAME' => '',
                                    'LAST_NAME' => '',
                                    'EMAIL' => '',
                                )
                            )
                        )
                    );


                    $mgrResult = Manager\Task::update(1/*$arEntity['AUTHOR_ID']*/, $arEntityAct['ASSOCIATED_ENTITY_ID'], $arData, array(
                        'PUBLIC_MODE' => true,
                        'ERRORS' => $error,
                        'THROTTLE_MESSAGES' => $parameters['THROTTLE_MESSAGES'],

                        // there also could be RETURN_CAN or RETURN_DATA, or both as RETURN_ENTITY
                        'RETURN_ENTITY' => $parameters['RETURN_ENTITY'],
                    ));
                }
            } else {
                //Get Recruiter
                if ($arEntity['REFERENCE_TYPE_ID'] == YNSIROwnerType::Order && intval($arEntity['REFERENCE_ID'])) {
                    $arRefOrder = YNSIRJobOrder::GetByID(intval($arEntity['REFERENCE_ID']));
                }
                if (empty($arRefOrder)) return;
                $rsRecruiter = CUser::GetbyId(intval($arRefOrder['RECRUITER']));
                $dbUser = CUser::GetByID(intval($arRefOrder['RECRUITER']));
                $arResult["RECRUITER"] = $dbUser->GetNext();

                $dbResultCa = YNSIRCandidate::GetListCandidate(
                    array(),
                    array('ID' => $arEntity['OWNER_ID'],'CHECK_PERMISSIONS' => 'N'),
                    '',
                    array(), array());
                $rs = array();
                $sFormatName = CSite::GetNameFormat(false);
                if ($arProfile = $dbResultCa->GetNext()) {
                    $arProfile['FULL_NAME'] = CUser::FormatName(
                        $sFormatName,
                        array(
                            "NAME" => $arProfile['FIRST_NAME'],
                            "LAST_NAME" => $arProfile['LAST_NAME'],
                        )
                    );
                }

                $arData = array(
                    'PRIORITY' => '1',
                    'TITLE' => 'RECRUITMENT: '.$arEntity['SUBJECT'],
                    'DESCRIPTION' => YNSIRTaskEvent::GetRefCandidateHTML($arEntity),
                    'SE_RESPONSIBLE' =>
                        array(
                            'U' . $arResult["RECRUITER"]['ID'] =>
                                array(
                                    'ID' => $arResult["RECRUITER"]['ID'],
                                    'NAME' => $arResult["RECRUITER"]['NAME'],
                                    'LAST_NAME' => $arResult["RECRUITER"]['LAST_NAME'],
                                    'EMAIL' => $arResult["RECRUITER"]['EMAIL'],
                                ),
                            0 => '',
                        ),
                    'SE_ACCOMPLICE' => array(
                        array(
                            'ID' => CUser::GetId(),
                            'NAME' => '',
                            'LAST_NAME' => '',
                            'EMAIL' => '',
                        )
                    ),
                    'DURATION_TYPE' => CTasks::TIME_UNIT_TYPE_DAY,
                    'END_DATE_PLAN' => '',
                    'ALLOW_CHANGE_DEADLINE' => 'Y',
                    'MATCH_WORK_TIME' => 'N',
                    'TASK_CONTROL' => 'Y',
                    'ALLOW_TIME_TRACKING' => 'N',
                    'REPLICATE' => 'N',
                    'UF_YNSIR_TASK' => $arEntity['OWNER_ID'],
                    'UF_PARENT_AUTO_ID' => $arEntity['ID'],
                    'UF_ROUND_ID' => $arEntity['ROUND_ID'],
                    'UF_REFERENCE_ID' => $arEntity['REFERENCE_ID'],
                    'UF_REFERENCE_TYPE_ID' => $arEntity['REFERENCE_TYPE_ID'],
                    'UF_AUTHOR_ID' => $arEntity['AUTHOR_ID'],
                    'CREATED_BY' => $arEntity['AUTHOR_ID'],
                    'SE_TEMPLATE' =>
                        array(
                            'REPLICATE_PARAMS' =>
                                array(
                                    'PERIOD' => 'daily',
                                    'EVERY_DAY' => '1',
                                    'WORKDAY_ONLY' => 'N',
                                    'DAILY_MONTH_INTERVAL' => '0',
                                    'EVERY_WEEK' => '1',
                                    'MONTHLY_TYPE' => '1',
                                    'MONTHLY_DAY_NUM' => '1',
                                    'MONTHLY_MONTH_NUM_1' => '1',
                                    'MONTHLY_WEEK_DAY_NUM' => '0',
                                    'MONTHLY_WEEK_DAY' => '0',
                                    'MONTHLY_MONTH_NUM_2' => '1',
                                    'YEARLY_TYPE' => '1',
                                    'YEARLY_DAY_NUM' => '1',
                                    'YEARLY_MONTH_1' => '0',
                                    'YEARLY_WEEK_DAY_NUM' => '0',
                                    'YEARLY_WEEK_DAY' => '0',
                                    'YEARLY_MONTH_2' => '0',
                                    'TIME' => '05:00',
                                    'TIMEZONE_OFFSET' => '0',
                                    'START_DATE' => '',
                                    'REPEAT_TILL' => 'endless',
                                    'END_DATE' => '',
                                    'TIMES' => '0',
                                ),
                        )
                );
                $error = '';
                $mgrResult = Manager\Task::add($arEntity['AUTHOR_ID'], $arData, array(
                    'PUBLIC_MODE' => true,
                    'ERRORS' => $error,
                    'RETURN_ENTITY' => false
                ));

                return array(
                    'ID' => $mgrResult['DATA']['ID'],
                    'DATA' => $mgrResult['DATA'],
                    'CAN' => $mgrResult['CAN'],
                );
                //Not existed

            }

        }
    }
}

?>
