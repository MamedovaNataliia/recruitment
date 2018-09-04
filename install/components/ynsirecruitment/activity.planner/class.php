<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Activity;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);
if (!Main\Loader::includeModule('ynsirecruitment')) {
    return;
}

class YNSIRActivityPlannerComponent extends \CBitrixComponent
{

    protected function getActivityId()
    {
        return isset($this->arParams['ELEMENT_ID']) ? (int)$this->arParams['ELEMENT_ID'] : 0;
    }

    protected function getCalendarEventId()
    {
        return isset($this->arParams['CALENDAR_EVENT_ID']) ? (int)$this->arParams['CALENDAR_EVENT_ID'] : 0;
    }

    protected function getOwnerTypeId()
    {
        if (!empty($this->arParams['OWNER_TYPE_ID']))
            return (int)$this->arParams['OWNER_TYPE_ID'];
        if (isset($this->arParams['OWNER_TYPE']))
            return YNSIROwnerType::ResolveID($this->arParams['OWNER_TYPE']);

        return 0;
    }

    protected function getOwnerId()
    {
        return isset($this->arParams['OWNER_ID']) ? (int)$this->arParams['OWNER_ID'] : 0;
    }

    protected function getActivityType()
    {
        return isset($this->arParams['TYPE_ID']) ? (int)$this->arParams['TYPE_ID'] : 0;
    }

    protected function getProviderId()
    {
        return isset($this->arParams['PROVIDER_ID']) ? (string)$this->arParams['PROVIDER_ID'] : '';
    }

    protected function getProviderTypeId()
    {
        return isset($this->arParams['PROVIDER_TYPE_ID']) ? (string)$this->arParams['PROVIDER_TYPE_ID'] : '';
    }

    protected function getAction()
    {
        return isset($this->arParams['ACTION']) ? strtoupper((string)$this->arParams['ACTION']) : '';
    }

    protected function getPlannerId()
    {
        return isset($this->arParams['PLANNER_ID']) ? (string)$this->arParams['PLANNER_ID'] : '';
    }

    protected function getFromActivityId()
    {
        return isset($this->arParams['FROM_ACTIVITY_ID']) ? (int)$this->arParams['FROM_ACTIVITY_ID'] : 0;
    }

    protected function getAssociatedEntityId()
    {
        return isset($this->arParams['ASSOCIATED_ENTITY_ID']) ? (int)$this->arParams['ASSOCIATED_ENTITY_ID'] : 0;
    }

    protected function getActivityAdditionalData($activityId, &$activity, $provider = null)
    {
        //bindings
        $activity['BINDINGS'] = $activityId ? YNSIRActivity::GetBindings($activityId) : array();

        //communications
        if (empty($activity['COMMUNICATIONS']))
            $activity['COMMUNICATIONS'] = $activityId ? YNSIRActivity::GetCommunications($activityId) : array();

        /** @var Activity\Provider\Base $provider */
        if (!$activityId && $provider) {
            $activity['COMMUNICATIONS'] = $this->getYNSIREntityCommunications(
                $activity['OWNER_TYPE_ID'],
                $activity['OWNER_ID'],
                $provider::getCommunicationType(isset($activity['PROVIDER_TYPE_ID']) ? $activity['PROVIDER_TYPE_ID'] : null)
            );
        }


        //attaches
        $activity['STORAGE_TYPE_ID'] = isset($activity['STORAGE_TYPE_ID']) ? (int)$activity['STORAGE_TYPE_ID'] : Integration\StorageType::Undefined;
        if (!Integration\StorageType::isDefined($activity['STORAGE_TYPE_ID'])) {
            $activity['STORAGE_TYPE_ID'] = YNSIRActivity::GetDefaultStorageTypeID();
        }

        $activity['FILES'] = $activity['WEBDAV_ELEMENTS'] = $activity['DISK_FILES'] = array();

        YNSIRActivity::PrepareStorageElementIDs($activity);
        YNSIRActivity::PrepareStorageElementInfo($activity);

        //settings
        $activity['SETTINGS'] = (isset($activity['SETTINGS']) && $activity['SETTINGS'] !== '' && is_string($activity['SETTINGS']))
            ? unserialize($activity['SETTINGS']) : array();

        //other
        if (isset($activity['DEADLINE']) && CCrmDateTimeHelper::IsMaxDatabaseDate($activity['DEADLINE'])) {
            $activity['DEADLINE'] = '';
        }
    }

    public function executeComponent()
    {
        if (!Main\Loader::includeModule('crm')) {
            ShowError(Loc::getMessage('YNSIR_MODULE_NOT_INSTALLED'));
            return;
        }

        if (!Main\Loader::includeModule('calendar')) {
            ShowError(Loc::getMessage('CALENDAR_MODULE_NOT_INSTALLED'));
            return;
        }

        $action = $this->getAction();

        switch ($action) {
            case 'EDIT':
                $this->executeEditAction();
                break;
            default:
                $this->executeViewAction();
                break;
        }
    }

    protected function executeEditAction()
    {
        $activityId = $this->getActivityId();
        $calendarEventId = $this->getCalendarEventId();
        $isNew = false;
        $activity = $error = null;

        if ($activityId > 0)
            $activity = YNSIRActivity::GetByID($activityId, false);
        elseif ($calendarEventId > 0)
            $activity = YNSIRActivity::GetByCalendarEventId($calendarEventId, false);
        else {
            $isNew = true;
            $activity = array(
                'OWNER_ID' => $this->getOwnerId(),
                'OWNER_TYPE_ID' => $this->getOwnerTypeId(),
                'RESPONSIBLE_ID' => CCrmSecurityHelper::GetCurrentUserID(),
                'TYPE_ID' => $this->getActivityType(),
                'PROVIDER_ID' => $this->getProviderId(),
                'PROVIDER_TYPE_ID' => $this->getProviderTypeId(),
            );

            if ($this->getAssociatedEntityId() > 0)
                $activity['ASSOCIATED_ENTITY_ID'] = $this->getAssociatedEntityId();
            if ($this->arParams['REQUEST']['OWNER_ROUND_ID'] > 0)
                $activity['ROUND_ID'] = $this->arParams['REQUEST']['OWNER_ROUND_ID'];
        }

        if (empty($activity))
            $error = Loc::getMessage('YNSIR_ACTIVITY_PLANNER_NO_ACTIVITY');

        $provider = $activity ? YNSIRActivity::GetActivityProvider($activity) : null;

        if (!$provider)
            $error = Loc::getMessage('YNSIR_ACTIVITY_PLANNER_NO_PROVIDER');

        if (!$error && !$isNew && !YNSIRActivity::CheckUpdatePermission($activity['OWNER_TYPE_ID'], $activity['OWNER_ID']))
            $error = Loc::getMessage('YNSIR_ACTIVITY_PLANNER_NO_UPDATE_PERMISSION');

        if ($error) {
            $this->arResult['ERROR'] = $error;
            $this->includeComponentTemplate('error');
            return;
        }

        $this->arResult['DURATION_VALUE'] = 1;
        $this->arResult['DURATION_TYPE'] = YNSIRActivityNotifyType::Hour;
        CJSCore::RegisterExt('socnetlogdest', array(
            'js' => '/bitrix/js/socialnetwork/log-destination.js',
            'css' => '/bitrix/js/main/core/css/core_finder.css',
            'lang_additional' => array(
                'LM_POPUP_TITLE' => GetMessage("LM_POPUP_TITLE"),
                'LM_POPUP_TAB_LAST' => GetMessage("LM_POPUP_TAB_LAST"),
                'LM_POPUP_TAB_SG' => GetMessage("LM_POPUP_TAB_SG"),
                'LM_POPUP_TAB_STRUCTURE' => GetMessage("LM_POPUP_TAB_STRUCTURE"),
                'LM_POPUP_TAB_EMAIL' => GetMessage("LM_POPUP_TAB_EMAIL"),
                'LM_POPUP_TAB_CRMEMAIL' => GetMessage("LM_POPUP_TAB_CRMEMAIL"),
                'LM_POPUP_TAB_STRUCTURE_EXTRANET' => GetMessage("LM_POPUP_TAB_STRUCTURE_EXTRANET"),
                'LM_POPUP_CHECK_STRUCTURE' => GetMessage("LM_POPUP_CHECK_STRUCTURE"),
                'LM_POPUP_TAB_LAST_USERS' => GetMessage("LM_POPUP_TAB_LAST_USERS"),
                'LM_POPUP_TAB_LAST_CRMEMAILS' => GetMessage("LM_POPUP_TAB_LAST_CRMEMAILS"),
                'LM_POPUP_TAB_LAST_CONTACTS' => GetMessage("LM_POPUP_TAB_LAST_CONTACTS"),
                'LM_POPUP_TAB_LAST_COMPANIES' => GetMessage("LM_POPUP_TAB_LAST_COMPANIES"),
                'LM_POPUP_TAB_LAST_DEALS' => GetMessage("LM_POPUP_TAB_LAST_ORDERS"),
                'LM_POPUP_TAB_LAST_SG' => GetMessage("LM_POPUP_TAB_LAST_SG"),
                'LM_POPUP_TAB_LAST_STRUCTURE' => GetMessage("LM_POPUP_TAB_LAST_STRUCTURE"),
                'LM_POPUP_TAB_SEARCH' => GetMessage("LM_POPUP_TAB_SEARCH"),
                'LM_SEARCH_PLEASE_WAIT' => GetMessage("LM_SEARCH_PLEASE_WAIT"),
                'LM_EMPTY_LIST' => GetMessage("LM_EMPTY_LIST"),
                'LM_PLEASE_WAIT' => GetMessage("LM_PLEASE_WAIT"),
                'LM_CREATE_SONETGROUP_TITLE' => GetMessage("LM_CREATE_SONETGROUP_TITLE"),
                'LM_CREATE_SONETGROUP_BUTTON_CREATE' => GetMessage("LM_CREATE_SONETGROUP_BUTTON_CREATE"),
                'LM_CREATE_SONETGROUP_BUTTON_CANCEL' => GetMessage("LM_CREATE_SONETGROUP_BUTTON_CANCEL"),
                'LM_INVITE_EMAIL_USER_BUTTON_OK' => GetMessage("LM_INVITE_EMAIL_USER_BUTTON_OK"),
                'LM_INVITE_EMAIL_USER_TITLE' => GetMessage("LM_INVITE_EMAIL_USER_TITLE"),
                'LM_INVITE_EMAIL_USER_PLACEHOLDER_NAME' => GetMessage("LM_INVITE_EMAIL_USER_PLACEHOLDER_NAME"),
                'LM_INVITE_EMAIL_USER_PLACEHOLDER_LAST_NAME' => GetMessage("LM_INVITE_EMAIL_USER_PLACEHOLDER_LAST_NAME"),
                'LM_INVITE_EMAIL_CRM_CREATE_CONTACT' => GetMessage("LM_INVITE_EMAIL_CRM_CREATE_CONTACT"),
                'LM_POPUP_WAITER_TEXT' => GetMessage("LM_POPUP_WAITER_TEXT"),
                'LM_POPUP_SEARCH_NETWORK' => GetMessage("LM_POPUP_SEARCH_NETWORK"),
            ),
            'rel' => array('core', 'popup', 'json', 'finder')
        ));
        if ($isNew) {
            $provider::fillDefaultActivityFields($activity);

            $defaults = \CUserOptions::GetOption('ynsir.activity.planner', 'defaults', array());
            if (isset($defaults['notify']) && isset($defaults['notify'][$provider::getId()])) {
                $activity['NOTIFY_VALUE'] = (int)$defaults['notify'][$provider::getId()]['value'];
                $activity['NOTIFY_TYPE'] = (int)$defaults['notify'][$provider::getId()]['type'];
            }

            if (isset($defaults['duration']) && isset($defaults['duration'][$provider::getId()])) {
                $this->arResult['DURATION_VALUE'] = (int)$defaults['duration'][$provider::getId()]['value'];
                $this->arResult['DURATION_TYPE'] = (int)$defaults['duration'][$provider::getId()]['type'];
            }
            if ($activity['TYPE_ID'] == YNSIRActivityType::Call) {
                $this->arResult['PROVIDER_TYPE_ID'] = 'Call';
            } elseif ($activity['TYPE_ID'] == YNSIRActivityType::Meeting) {
                $this->arResult['PROVIDER_TYPE_ID'] = 'Interview';
            }
            $fromId = $this->getFromActivityId();
            if ($fromId > 0) {
                $fromActivity = YNSIRActivity::GetByID($fromId);
                if ($fromActivity) {
                    $activity['SUBJECT'] = $fromActivity['SUBJECT'];
                    $activity['PRIORITY'] = $fromActivity['PRIORITY'];
                    if ($activity['TYPE_ID'] == YNSIRActivityType::Call || $activity['TYPE_ID'] == YNSIRActivityType::Meeting) {
                        $activity['DESCRIPTION'] = $fromActivity['DESCRIPTION'];
                    }
                    if ($activity['TYPE_ID'] == YNSIRActivityType::Meeting) {
                        $this->arResult['PROVIDER_TYPE_ID'] = 'Meeting';
                        $activity['LOCATION'] = $fromActivity['LOCATION'];
                    }

                    $fromComm = YNSIRActivity::GetCommunications($fromId);
                    if (is_array($fromComm)) {
                        $activity['COMMUNICATIONS'] = array();
                        $commType = $provider::getCommunicationType($activity['PROVIDER_TYPE_ID']);

                        foreach ($fromComm as $comm) {
                            if ($comm['TYPE'] === $commType)
                                $activity['COMMUNICATIONS'][] = $comm;
                        }
                    }
                }
            }
            $this->arResult['DESTINATION_ENTITIES'] = $this->getDestinationEntities($activity);
            if ((int)$this->arParams['REQUEST']['OWNER_ORDER_ID'] > 0) {
                $arFilter['ID'] = $this->arParams['REQUEST']['OWNER_ORDER_ID'];
                $db = YNSIRJobOrder::GetList(array("ID" => "DESC"), $arFilter,false,fasle,array('QUERY_OPTIONS' => array('LIMIT' => 20, 'OFFSET' => 0)));
                $title = '';
                if($rs = $db->Fetch()){
                    $title = $rs['TITLE'];
                }
                $jo = array(
                    'id' => 'O' . $this->arParams['REQUEST']['OWNER_ORDER_ID'],
                    'entityId' => $this->arParams['REQUEST']['OWNER_ORDER_ID'],
                    'name' => $title,
                    'entityType' => 'orderjob'
                );
                $this->arResult['DESTINATION_ENTITIES']['orders'][] = $jo;
            }
            foreach ($this->arParams['REQUEST']['OWNER_INTERVIEWERS_ID'] as $arComm) {
                $p = array(
                    'id' => 'U' . $arComm,
                    'entityId' => $arComm,
                    'name' => CYNSIRViewHelper::GetFormattedUserName($arComm, $this->arParams['NAME_TEMPLATE']),
                    'entityType' => 'users'
                );
                $this->arResult['DESTINATION_ENTITIES']['attendees'][] = $p;
            }
        } else {
            $this->arResult['DESTINATION_ENTITIES'] = $this->getDestinationEntities($activity);
        }
        $this->getActivityAdditionalData($activityId, $activity, $provider);

        $this->arResult['ACTIVITY'] = $activity;
        $this->arResult['PROVIDER'] = $provider;
        $this->arResult['COMMUNICATIONS_DATA'] = $this->getCommunicationsData($activity['COMMUNICATIONS']);
        $this->arResult['PLANNER_ID'] = $this->getPlannerId();

        $options = \CUserOptions::GetOption('ynsir.activity.planner', 'edit', array());
        $this->arResult['DETAIL_MODE'] = (isset($options['view_mode']) && $options['view_mode'] === 'detail');
        $this->arResult['ADDITIONAL_MODE'] = (isset($options['additional_mode']) && $options['additional_mode'] === 'open');

        $this->includeComponentTemplate('edit');
    }

    protected function executeViewAction()
    {
        $userId = CCrmSecurityHelper::GetCurrentUserID();

        $activityId = $this->getActivityId();
        $calendarEventId = $this->getCalendarEventId();

        $activity = $error = null;

        if ($activityId > 0)
            $activity = YNSIRActivity::GetByID($activityId, false);
        elseif ($calendarEventId > 0)
            $activity = YNSIRActivity::GetByCalendarEventId($calendarEventId, false);

        if (empty($activity))
            $error = Loc::getMessage('YNSIR_ACTIVITY_PLANNER_NO_ACTIVITY');

        $provider = $activity ? YNSIRActivity::GetActivityProvider($activity) : null;

        if (!$provider)
            $error = Loc::getMessage('YNSIR_ACTIVITY_PLANNER_NO_PROVIDER');

        if (!$error
            && $userId !== (int)$activity['RESPONSIBLE_ID']
            && !YNSIRActivity::CheckReadPermission($activity['OWNER_TYPE_ID'], $activity['OWNER_ID'])
        ) {
            $error = Loc::getMessage('YNSIR_ACTIVITY_PLANNER_NO_READ_PERMISSION');
        }

        if ($error) {
            $this->arResult['ERROR'] = $error;
            $this->includeComponentTemplate('error');
            return;
        }

        $this->getActivityAdditionalData($activityId, $activity);

        if ($activity['COMPLETED'] === 'N' && $provider::canCompleteOnView($activity['PROVIDER_TYPE_ID'])) {
            $completeResult = \YNSIRActivity::Complete($activity['ID']);
            if ($completeResult)
                $activity['COMPLETED'] = 'Y';
        }

        $activity['DESCRIPTION_HTML'] = $this->makeDescriptionHtml(
            $activity['DESCRIPTION'],
            $activity['DESCRIPTION_TYPE']
        );

        $activity['COMMUNICATIONS'] = $this->prepareCommunicationsForView($activity['OWNER_ID']);;

        $this->arResult['COMMUNICATIONS'] = $activity['COMMUNICATIONS'];
        $this->arResult['PROVIDER'] = $provider;
        $this->arResult['ACTIVITY'] = $activity;

        $this->arResult['TYPE_ICON'] = $this->getTypeIcon($activity);
        $this->arResult['FILES_LIST'] = $this->prepareFilesForView($activity);

        $this->arResult['RESPONSIBLE_NAME'] = CYNSIRViewHelper::GetFormattedUserName($activity['RESPONSIBLE_ID'], $this->arParams['NAME_TEMPLATE']);
        $this->arResult['RESPONSIBLE_URL'] = CComponentEngine::MakePathFromTemplate(
            '/company/personal/user/#user_id#/',
            array('user_id' => $activity['RESPONSIBLE_ID'])
        );
        //ATTENDEES
        if (!empty($activity['ATTENDEES'][$activity['CALENDAR_EVENT_ID']])) {
            $arAttendees = array();
            foreach ($activity['ATTENDEES'][$activity['CALENDAR_EVENT_ID']] as $ATTENDEE) {
                if (intval($ATTENDEE['USER_ID']) !== intval($activity['RESPONSIBLE_ID'])) {
                    $attendeeName = CYNSIRViewHelper::GetFormattedUserName($ATTENDEE['USER_ID'], $this->arParams['NAME_TEMPLATE']);
                    $attendeeURL = CComponentEngine::MakePathFromTemplate(
                        '/company/personal/user/#user_id#/',
                        array('user_id' => $ATTENDEE['USER_ID'])
                    );
                    $arAttendees[] = array(
                        'NAME' => $attendeeName,
                        'URL' => $attendeeURL
                    );
                }
            }
            unset($ATTENDEE);
            $this->arResult['ATTENDEES'] = $arAttendees;
            unset($arAttendees);
        }


        $this->arResult['DOC_BINDINGS'] = array();
        foreach ($activity['BINDINGS'] as $binding) {
            if ($this->isDocument($binding['OWNER_TYPE_ID'])) {
                $this->arResult['DOC_BINDINGS'][] = array(
                    'DOC_NAME' => YNSIROwnerType::GetDescription($binding['OWNER_TYPE_ID']),
                    'CAPTION' => YNSIROwnerType::GetCaption($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']),
                    'URL' => YNSIROwnerType::GetShowUrl($binding['OWNER_TYPE_ID'], $binding['OWNER_ID'])
                );
            }
        }

        $ownerID = (int)$activity['OWNER_ID'];
        $ownerTypeID = (int)$activity['OWNER_TYPE_ID'];

        if (!$ownerID && !$ownerTypeID || \YNSIRActivity::CheckUpdatePermission($ownerTypeID, $ownerID)) {
            if ($provider::isTypeEditable($activity['PROVIDER_TYPE_ID'], $activity['DIRECTION'])) {
                $this->arResult['IS_EDITABLE'] = true;
            }
        }

        $this->includeComponentTemplate('view');
    }

    private function isDocument($entityTypeId)
    {
        $entityTypeId = (int)$entityTypeId;
        return $entityTypeId === YNSIROwnerType::Order || $entityTypeId === YNSIROwnerType::Candidate || $entityTypeId === YNSIROwnerType::Quote;
    }

    private function getTypeIcon($activity)
    {
        if ($activity['TYPE_ID'] == \YNSIRActivityType::Call) {
            return $activity['DIRECTION'] == \YNSIRActivityDirection::Outgoing ? 'call-outgoing' : 'call';
        }
        if ($activity['TYPE_ID'] == \YNSIRActivityType::Meeting)
            return 'meet';
        if ($activity['TYPE_ID'] == \YNSIRActivityType::Email) {
            return $activity['DIRECTION'] == \YNSIRActivityDirection::Outgoing ? 'mail' : 'mail-send';
        }

        if ($activity['PROVIDER_ID'] == 'YNSIR_EXTERNAL_CHANNEL')
            return 'onec';
        if ($activity['PROVIDER_ID'] == 'YNSIR_LF_MESSAGE')
            return 'live-feed';

        if ($activity['PROVIDER_ID'] == 'YNSIR_WEBFORM')
            return 'form';

        if ($activity['PROVIDER_ID'] == 'IMOPENLINES_SESSION')
            return 'chat';

        if ($activity['PROVIDER_ID'] == 'VISIT_TRACKER')
            return 'visit-tracker';

        if ($activity['PROVIDER_ID'] == 'YNSIR_REQUEST')
            return 'deal-request';

        if ($activity['PROVIDER_ID'] == 'CALL_LIST')
            return 'call-list';

        return '';
    }

    private function prepareFilesForView(array $activity)
    {
        $result = array();

        if (!empty($activity['FILES'])) {
            foreach ($activity['FILES'] as $file) {
                $result[] = array(
                    'fileName' => $file['fileName'],
                    'viewURL' => $file['fileURL']
                );
            }
        } elseif (!empty($activity['WEBDAV_ELEMENTS'])) {
            foreach ($activity['WEBDAV_ELEMENTS'] as $element) {
                $result[] = array(
                    'fileName' => $element['NAME'],
                    'viewURL' => $element['VIEW_URL']
                );
            }
        } elseif (!empty($activity['DISK_FILES'])) {
            foreach ($activity['DISK_FILES'] as $file) {
                $result[] = array(
                    'fileName' => $file['NAME'],
                    'viewURL' => $file['VIEW_URL']
                );
            }
        }

        return $result;
    }

    private function prepareCommunicationsForView($communications)
    {
        $result = array();
        global $DB;
        $dbResult = YNSIRCandidate::GetListCandidate(
            array(),
            array('ID' => $communications),
            array(),
            array());
        $arResult['CANDIDATE'] = array();
        if ($arProfile = $dbResult->GetNext()) {
            $arResult['CANDIDATE'] = $arProfile;
        }
        $sFormatName = CSite::GetNameFormat(false);
        $arResult['CANDIDATE']['NAME'] = CUser::FormatName(
            $sFormatName,
            array(
                "NAME" => $arResult['CANDIDATE']['FIRST_NAME'],
                "LAST_NAME" => $arResult['CANDIDATE']['LAST_NAME'],
            )
        );
        $arResult['NAME'] = $arResult['CANDIDATE']['SALT_NAME'] . $arResult['CANDIDATE']['NAME'];
        $phone = '';
        $dbMultiField = YNSIRCandidate::GetListMultiField(array(), array('CANDIDATE_ID' => $communications));
        while ($multiField = $dbMultiField->GetNext()) {
            switch ($multiField['TYPE']) {
                case 'EMAIL':
                   $email .= $multiField['CONTENT'].' ';
                    break;
                case 'CMOBILE':
                case 'PHONE':
                    $phone .= $multiField['CONTENT'].' ';
                    break;
                default:
            }
        }
        $result = array(
            0 =>
                array(
                    'ID' => $communications,
                    'TYPE' => 'PHONE',
                    'VALUE' => $arResult['CANDIDATE']['PHONE'],
                    'ENTITY_ID' => '4',
                    'ENTITY_TYPE_ID' => '1',
                    'ENTITY_SETTINGS' =>
                        array(
                            'HONORIFIC' => '',
                            'NAME' => $arResult['CANDIDATE']['FIRST_NAME'],
                            'SECOND_NAME' => '',
                            'LAST_NAME' => $arResult['CANDIDATE']['LAST_NAME'],
                        ),
                    'TITLE' => $arResult['NAME'],
                    'DESCRIPTION' => 'Candidate',
                    'VIEW_URL' => '/recruitment/candidate/detail/' . $communications . '/',
                    'IMAGE_URL' => '',
                    'FM' =>
                        array(
                            'PHONE' =>
                                array(
                                    0 =>
                                        array(
                                            'VALUE' => $phone,
                                            'VALUE_TYPE' => 'WORK',
                                        )

                                ),
                            'EMAIL' =>
                                array(
                                    4 =>
                                        array(
                                                                                     'VALUE' => $email,
                                            'VALUE_TYPE' => 'WORK',
                                        ),
                                ),
                        ),
                ),
        );
        return $result;
    }

    // Helpers
    private function getDestinationEntities($activity)
    {
        $result = array(
            'responsible' => array(
                array(
                    'id' => 'U' . $activity['RESPONSIBLE_ID'],
                    'entityId' => $activity['RESPONSIBLE_ID'],
                    'name' => CYNSIRViewHelper::GetFormattedUserName($activity['RESPONSIBLE_ID'], $this->arParams['NAME_TEMPLATE']),
                    'entityType' => 'users'
                )
            ),
        );
        $arAttendees = array();
        foreach ($activity['ATTENDEES'][$activity['CALENDAR_EVENT_ID']] as $attendee) {
            if (intval($attendee['USER_ID']) !== intval($activity['RESPONSIBLE_ID'])) {
                $arAttendees[] = array(
                    'id' => 'U' . $attendee['USER_ID'],
                    'entityId' => $attendee['USER_ID'],
                    'name' => CYNSIRViewHelper::GetFormattedUserName($attendee['USER_ID'], $this->arParams['NAME_TEMPLATE']),
                    'entityType' => 'users'
                );
            }
        }
        $result['attendees'] = $arAttendees;

        if ((int)$activity['REFERENCE_TYPE_ID'] === YNSIROwnerType::Order) {
            $arFilter['ID'] = $activity['REFERENCE_ID'];
            $db = YNSIRJobOrder::GetList(array("ID" => "DESC"), $arFilter,false,fasle,array('QUERY_OPTIONS' => array('LIMIT' => 20, 'OFFSET' => 0)));
            $title = '';
            if($rs = $db->Fetch()){
                $title = $rs['TITLE'];
            }
            $result['orders'] = array(
                array(
                    'id' => 'O' . $activity['REFERENCE_ID'],
                    'entityId' => $activity['REFERENCE_ID'],
                    'name' => $title,
                    'entityType' => 'orderjob'
                )
            );
        }

        return $result;
    }

    public static function getDestinationData($params)
    {
        $type = isset($params['type']) ? $params['type'] : 'responsible';
        $result = array('LAST' => array());

        if ($type == 'responsible') {
            if (!Main\Loader::includeModule('socialnetwork'))
                return array();

            $arStructure = CSocNetLogDestination::GetStucture(array());
            $result['DEPARTMENT'] = $arStructure['department'];
            $result['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
            $result['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

            $result['DEST_SORT'] = CSocNetLogDestination::GetDestinationSort(array(
                "DEST_CONTEXT" => "YNSIR_ACTIVITY",
            ));

            CSocNetLogDestination::fillLastDestination(
                $result['DEST_SORT'],
                $result['LAST']
            );

            $destUser = array();
            foreach ($result["LAST"]["USERS"] as $value) {
                $destUser[] = str_replace("U", "", $value);
            }

            $result["USERS"] = \CSocNetLogDestination::getUsers(array("id" => $destUser));
        } elseif ($type == 'guests') {
            if (!Main\Loader::includeModule('socialnetwork'))
                return array();

            $arStructure = CSocNetLogDestination::GetStucture(array());
            $result['DEPARTMENT'] = $arStructure['department'];
            $result['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
            $result['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

            $result['DEST_SORT'] = CSocNetLogDestination::GetDestinationSort(array(
                "DEST_CONTEXT" => "YNSIR_ACTIVITY",
            ));

            CSocNetLogDestination::fillLastDestination(
                $result['DEST_SORT'],
                $result['LAST']
            );

            $destUser = array();
            foreach ($result["LAST"]["USERS"] as $value) {
                $destUser[] = str_replace("U", "", $value);
            }

            $result["USERS"] = \CSocNetLogDestination::getUsers(array("id" => $destUser));
        } elseif ($type == 'order') {

            $arFilter['ACTIVE'] = 1;
            $arFilter['!STATUS'] = JOStatus::JOSTATUS_CLOSED;
            $db = YNSIRJobOrder::GetList(array("ID" => "DESC"), $arFilter,false,fasle,array('QUERY_OPTIONS' => array('LIMIT' => 3, 'OFFSET' => 0)));
            while($rs = $db->Fetch()){
                $jo['O'.$rs['ID']] = array(
                    'id' => 'O'.$rs['ID'],
                    'entityId' => $rs['ID'],
                    'entityType' => 'orderjob',
                    'name' => $rs['TITLE'],
//                    'desc' => $rs['DESCRIPTION'],

                );
            }

            $result['ORDERJOB'] = $jo   ;
            $result['LAST']['ORDERJOB'] = $jo;
        }

        return $result;
    }

    public static function searchDestinationOrderJobs($data)
    {
        $arFilter['ACTIVE'] = 1;
        $arFilter['%TITLE'] = $data['SEARCH'];
        $arFilter['!STATUS'] = JOStatus::JOSTATUS_CLOSED;
        $db = YNSIRJobOrder::GetList(array("ID" => "DESC"), $arFilter,false,fasle,array('QUERY_OPTIONS' => array('LIMIT' => 20, 'OFFSET' => 0)));
        while($rs = $db->Fetch()){
            $jo['O'.$rs['ID']] = array(
                'id' => 'O'.$rs['ID'],
                'entityId' => $rs['ID'],
                'entityType' => 'deals',
                'name' => $rs['TITLE'],
//                'desc' => $rs['DESCRIPTION'],

            );
        }

        $searchResults['DEALS'] = $jo;
        $searchResults['USERS'] = array();

        return $searchResults;

    }

    private static function getDestinationDealEntities($filter, $limit, $order = array())
    {
        $nameTemplate = CSite::GetNameFormat(false);
        $result = array();
        $iterator = CCrmDeal::GetListEx(
            $arOrder = $order,
            $arFilter = $filter,
            $arGroupBy = false,
            $arNavStartParams = array('nTopCount' => $limit),
            $arSelectFields = array('ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME')
        );

        while ($iterator && ($arDeal = $iterator->fetch())) {
            $arDesc = array();
            if ($arDeal['COMPANY_TITLE'] != '')
                $arDesc[] = $arDeal['COMPANY_TITLE'];
            $arDesc[] = CUser::FormatName(
                $nameTemplate,
                array(
                    'LOGIN' => '',
                    'NAME' => $arDeal['CONTACT_NAME'],
                    'SECOND_NAME' => $arDeal['CONTACT_SECOND_NAME'],
                    'LAST_NAME' => $arDeal['CONTACT_LAST_NAME']
                ),
                false, false
            );

            $result['D' . $arDeal['ID']] = array(
                'id' => 'D' . $arDeal['ID'],
                'entityId' => $arDeal['ID'],
                'entityType' => 'deals',
                'name' => htmlspecialcharsbx($arDeal['TITLE']),
                'desc' => htmlspecialcharsbx(implode(', ', $arDesc))
            );
        }

        return $result;
    }

    private function getCommunicationsData(array $communications)
    {
        $result = array();

        foreach ($communications as $arComm) {
            YNSIRActivity::PrepareCommunicationInfo($arComm);
            $result[] = array(
                'id' => $arComm['ID'],
                'type' => $arComm['TYPE'],
                'value' => $arComm['VALUE'],
                'entityId' => $arComm['ENTITY_ID'],
                'entityType' => YNSIROwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
                'entityTitle' => $arComm['TITLE'],
                'entityUrl' => YNSIROwnerType::GetShowUrl($arComm['ENTITY_TYPE_ID'], $arComm['ENTITY_ID'])
            );
        }

        return $result;
    }

    public static function saveActivity($data, $userID, $siteID)
    {
        if (!empty($data['dealId'])) {
            $data['ownerType'] = 'DEAL';
            $data['ownerId'] = $data['dealId'];
        }

        if (empty($data['ownerType']) && empty($data['ownerId']) && !empty($data['communication'])) {
            $commData = isset($data['communication']) ? $data['communication'] : array();
            $data['ownerType'] = isset($commData['entityType']) ? strtoupper(strval($commData['entityType'])) : '';
            $data['ownerId'] = isset($commData['entityId']) ? intval($commData['entityId']) : 0;
        }

        $result = new Main\Result();

        if (count($data) == 0) {
            $result->addError(new Main\Error('SOURCE DATA ARE NOT FOUND!'));
            return $result;
        }

        $ID = isset($data['id']) ? intval($data['id']) : 0;
        $typeID = isset($data['type']) ? intval($data['type']) : YNSIRActivityType::Activity;
        $providerId = isset($data['providerId']) ? strtoupper(strval($data['providerId'])) : '';
        $providerTypeId = isset($data['providerTypeId']) ? strtoupper(strval($data['providerTypeId'])) : '';

        $activity = array(
            'TYPE_ID' => $typeID,
            'PROVIDER_ID' => $providerId,
            'PROVIDER_TYPE_ID' => $providerTypeId
        );

        if ($ID > 0) {
            $activity = YNSIRActivity::GetByID($ID, false);
            if (!$activity) {
                $result->addError(new Main\Error('IS NOT EXISTS!'));
                return $result;
            }
        }

        $provider = YNSIRActivity::GetActivityProvider($activity);
        if (!$provider) {
            $result->addError(new Main\Error('Provider not found!'));
            return $result;
        }

        $ownerTypeName = isset($data['ownerType']) ? strtoupper(strval($data['ownerType'])) : '';
        if ($provider::checkOwner() && $ownerTypeName === '') {
            $result->addError(new Main\Error('OWNER TYPE IS NOT DEFINED!'));
            return $result;
        }

        $ownerTypeID = YNSIROwnerType::ResolveID($ownerTypeName);
        if ($provider::checkOwner() && !YNSIROwnerType::IsDefined($ownerTypeID)) {
            $result->addError(new Main\Error('OWNER TYPE IS NOT SUPPORTED!'));
            return $result;
        }

        $ownerId = isset($data['ownerId']) ? intval($data['ownerId']) : 0;
        if ($provider::checkOwner() && $ownerId <= 0) {
            $result->addError(new Main\Error('OWNER ID IS NOT DEFINED!YY'));
            return $result;
        }

        if ($provider::checkOwner() && !YNSIRActivity::CheckUpdatePermission($ownerTypeID, $ownerId)) {
            $result->addError(new Main\Error('Access denied!'));
            return $result;
        }

        $responsibleID = isset($data['responsibleId']) ? intval($data['responsibleId']) : 0;

        if ($userID <= 0) {
            $userID = YNSIROwnerType::GetResponsibleID($ownerTypeID, $ownerId, false);
            if ($userID <= 0) {
                $result->addError(new Main\Error('Responsible not found!'));
                return $result;
            }
        }
        if (isset($data['guestsId'])) {
            foreach ($data["guestsId"] as $v => $k) {
                if (strlen($v) > 0 && is_array($k) && !empty($k)) {
                    foreach ($k as $vv) {
                        if (strlen($vv) > 0) {
                            $arAccessCodes[] = $vv;
                        }
                    }
                }
            }
            $arAccessCodes[] = $userID;


            $arAccessCodes = array_unique($arAccessCodes);
        }
        if(isset($data['roundID']) && YNSIRInterview::isExist(intval($data['roundID']))) {
            $roundID = intval($data['roundID']);
        } else {
            $roundID = 0;
        }


        $start = isset($data['startTime']) ? strval($data['startTime']) : '';
        $end = isset($data['endTime']) ? strval($data['endTime']) : '';
        if ($start === '') {
            $start = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID);
        }

        if ($end === '') {
            $end = $start;
        }

        $descr = isset($data['description']) ? strval($data['description']) : '';
        $priority = isset($data['important']) ? YNSIRActivityPriority::High : YNSIRActivityPriority::Medium;
        $location = isset($data['location']) ? strval($data['location']) : '';

        $direction = isset($data['direction']) ? intval($data['direction']) : YNSIRActivityDirection::Undefined;

        // Communications
        $commData = isset($data['communication']) ? $data['communication'] : array();
        $commID = isset($commData['id']) ? intval($commData['id']) : 0;
        $commEntityType = isset($commData['entityType']) ? strtoupper(strval($commData['entityType'])) : '';
        $commEntityID = isset($commData['entityId']) ? intval($commData['entityId']) : 0;
        $commType = isset($commData['type']) ? strtoupper(strval($commData['type'])) : '';
        $commValue = isset($commData['value']) ? strval($commData['value']) : '';

        $subject = isset($data['subject']) ? (string)$data['subject'] : '';
        if ($subject === '') {
            $arCommInfo = array(
                'ENTITY_ID' => $commEntityID,
                'ENTITY_TYPE_ID' => YNSIROwnerType::ResolveID($commEntityType)
            );
            YNSIRActivity::PrepareCommunicationInfo($arCommInfo);

            $subject = $provider::generateSubject($activity['PROVIDER_ID'], $direction, array(
                '#DATE#' => $start,
                '#TITLE#' => isset($arCommInfo['TITLE']) ? $arCommInfo['TITLE'] : $commValue,
                '#COMMUNICATION#' => $commValue
            ));
        }

        $arFields = array(
            'PROVIDER_ID' => $providerId,
            'PROVIDER_TYPE_ID' => $providerTypeId,
            'TYPE_ID' => $typeID,
            'SUBJECT' => $subject,
            'COMPLETED' => isset($data['completed']) && $data['completed'] === 'Y' ? 'Y' : 'N',
            'PRIORITY' => $priority,
            'DESCRIPTION' => $descr,
            'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
            'LOCATION' => array('OLD' => false, 'NEW' => $location),
            'DIRECTION' => $direction,
            'NOTIFY_TYPE' => YNSIRActivityNotifyType::None,
            'SETTINGS' => array()
        );
        if (isset($data['ORDER'])) {
            $arFields['REFERENCE_ID'] = $data['ORDER'][0];
            $arFields['REFERENCE_TYPE_ID'] = YNSIROwnerType::Order;
        }
        $arBindings = array(
            "{$ownerTypeName}_{$ownerId}" => array(
                'OWNER_TYPE_ID' => $ownerTypeID,
                'OWNER_ID' => $ownerId
            )
        );

        $arFields['NOTIFY_TYPE'] = isset($data['notifyType']) ? (int)$data['notifyType'] : YNSIRActivityNotifyType::Min;
        $arFields['NOTIFY_VALUE'] = isset($data['notifyValue']) ? (int)$data['notifyValue'] : 15;

        // Communications
        $arComms = array();
        if ($commEntityID <= 0 && $commType === 'PHONE' && $ownerTypeName !== 'DEAL') {
            // Communication entity ID is 0 (processing of new communications)
            // Communication type must present it determines TYPE_ID (is only 'PHONE' in current context)
            // Order does not have multi fields.

            $fieldMulti = new CYNSIRFieldMulti();
            $arFieldMulti = array(
                'ENTITY_ID' => $ownerTypeName,
                'ELEMENT_ID' => $ownerId,
                'TYPE_ID' => 'PHONE',
                'VALUE_TYPE' => 'WORK',
                'VALUE' => $commValue
            );

            $fieldMultiID = $fieldMulti->Add($arFieldMulti);
            if ($fieldMultiID > 0) {
                $commEntityType = $ownerTypeName;
                $commEntityID = $ownerId;
            }
        }

        if ($commEntityType !== '') {
            $arComms[] = array(
                'ID' => $commID,
                'TYPE' => $commType,
                'VALUE' => $commValue,
                'ENTITY_ID' => $commEntityID,
                'ENTITY_TYPE_ID' => YNSIROwnerType::ResolveID($commEntityType)
            );

            $bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
            if (!isset($arBindings[$bindingKey])) {
                $arBindings[$bindingKey] = array(
                    'OWNER_TYPE_ID' => YNSIROwnerType::ResolveID($commEntityType),
                    'OWNER_ID' => $commEntityID
                );
            }
        }

        $isNew = $ID <= 0;
        $arPreviousFields = $ID > 0 ? YNSIRActivity::GetByID($ID) : array();
        $arFields['LOCATION']['OLD'] = strlen($arPreviousFields['LOCATION']) > 0 ? $arPreviousFields['LOCATION'] : false;
        $storageTypeID = isset($data['storageTypeID']) ? intval($data['storageTypeID']) : Integration\StorageType::Undefined;
        if ($storageTypeID === Integration\StorageType::Undefined
            || !Integration\StorageType::IsDefined($storageTypeID)
        ) {
            if ($isNew) {
                $storageTypeID = YNSIRActivity::GetDefaultStorageTypeID();
            } else {
                $storageTypeID = YNSIRActivity::GetStorageTypeID($ID);
                if ($storageTypeID === Integration\StorageType::Undefined) {
                    $storageTypeID = YNSIRActivity::GetDefaultStorageTypeID();
                }
            }
        }

        $arFields['STORAGE_TYPE_ID'] = $storageTypeID;
        $disableStorageEdit = isset($data['disableStorageEdit']) && strtoupper($data['disableStorageEdit']) === 'Y';
        if (!$disableStorageEdit) {
            if ($storageTypeID === Integration\StorageType::File) {
                $arPermittedFiles = array();
                $arUserFiles = isset($data['files']) && is_array($data['files']) ? $data['files'] : array();
                if (!empty($arUserFiles) || !$isNew) {
                    $arPreviousFiles = array();
                    if (!$isNew) {
                        YNSIRActivity::PrepareStorageElementIDs($arPreviousFields);
                        $arPreviousFiles = $arPreviousFields['STORAGE_ELEMENT_IDS'];
                        if (is_array($arPreviousFiles) && !empty($arPreviousFiles)) {
                            $arPermittedFiles = array_intersect($arUserFiles, $arPreviousFiles);
                        }
                    }

                    $uploadControlCID = isset($data['uploadControlCID']) ? strval($data['uploadControlCID']) : '';
                    if ($uploadControlCID !== '' && isset($_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"])) {
                        $uploadedFiles = $_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"];
                        if (!empty($uploadedFiles)) {
                            $arPermittedFiles = array_merge(
                                array_intersect($arUserFiles, $uploadedFiles),
                                $arPermittedFiles
                            );
                        }
                    }

                    $arFields['STORAGE_ELEMENT_IDS'] = $arPermittedFiles;
                }
            } elseif ($storageTypeID === Integration\StorageType::WebDav || $storageTypeID === Integration\StorageType::Disk) {
                $fileKey = $storageTypeID === Integration\StorageType::Disk ? 'diskfiles' : 'webdavelements';
                $arFileIDs = isset($data[$fileKey]) && is_array($data[$fileKey]) ? $data[$fileKey] : array();
                if (!empty($arFileIDs) || !$isNew) {
//                    $arFields['STORAGE_ELEMENT_IDS'] = Bitrix\YNSIR\Integration\StorageManager::filterFiles($arFileIDs, $storageTypeID, $userID);
                }
            }
        }

        //TIME FIELDS
        $arFields['START_TIME'] = $start;
        $arFields['END_TIME'] = $end;

        if ($isNew) {
            $arFields['OWNER_ID'] = $ownerId;
            $arFields['OWNER_TYPE_ID'] = $ownerTypeID;
            $arFields['RESPONSIBLE_ID'] = $responsibleID > 0 ? $responsibleID : $userID;
            $arFields['ATTENDEES_CODES'] = !empty($arAccessCodes) ? $arAccessCodes : array();
            $arFields['ROUND_ID'] = $roundID > 0? $roundID : 0;

            $arFields['BINDINGS'] = array_values($arBindings);
            $arFields['PROVIDER_ID'] = '1';

            $providerResult = $provider::postForm($arFields, $data);
            if (!$providerResult->isSuccess()) {
                $result->addErrors($providerResult->getErrors());
                return $result;
            }
            // add activity
            $check = YNSIRActivity::checkRoom($arFields);
            if ($check !== true) {
                $result->addError(new Main\Error(GetMessage('YNSIR_DUPPLICATE_ROOM')));
                return $result;
            }
            $ID = YNSIRActivity::Add($arFields, false, true, array('REGISTER_SONET_EVENT' => true));
            if ($ID <= 0) {
                $result->addError(new Main\Error(YNSIRActivity::GetLastErrorMessage()));
                return $result;
            }
            $provider::saveAdditionalData($ID, $arFields);

            //Region automation trigger
//            if (
//                $arFields['TYPE_ID'] === \YNSIRActivityType::Call
//                && $arFields['DIRECTION'] === \YNSIRActivityDirection::Incoming
//            ) {
//                \Bitrix\Crm\Automation\Trigger\CallTrigger::execute($arFields['BINDINGS'], $arFields);
//            }
            //end region
        } else {
            $check = YNSIRActivity::checkRoom($arFields);
            if ($check !== true) {
                $result->addError(new Main\Error(GetMessage('YNSIR_DUPPLICATE_ROOM')));
                return $result;
            }
            $dbResult = YNSIRActivity::GetList(
                array(),
                array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
                false,
                false,
                array('OWNER_ID', 'OWNER_TYPE_ID', 'START_TIME', 'END_TIME')
            );
            $presentFields = is_object($dbResult) ? $dbResult->Fetch() : null;
            if (!is_array($presentFields)) {
                $result->addError(new Main\Error('COULD NOT FIND ACTIVITY!'));
                return $result;
            }

            $presentOwnerTypeID = intval($presentFields['OWNER_TYPE_ID']);
            $presentOwnerID = intval($presentFields['OWNER_ID']);
            $ownerChanged = ($presentOwnerTypeID !== $ownerTypeID || $presentOwnerID !== $ownerId);

            $arFields['OWNER_ID'] = $ownerId;
            $arFields['OWNER_TYPE_ID'] = $ownerTypeID;

            if ($responsibleID > 0) {
                $arFields['RESPONSIBLE_ID'] = $responsibleID;
            }
            $arFields['ATTENDEES_CODES'] = !empty($arAccessCodes) ? $arAccessCodes : array();

            //Merge new bindings with old bindings
            $presetCommunicationKeys = array();
            $presetCommunications = YNSIRActivity::GetCommunications($ID);
            foreach ($presetCommunications as $arComm) {
                $commEntityTypeName = YNSIROwnerType::ResolveName($arComm['ENTITY_TYPE_ID']);
                $commEntityID = $arComm['ENTITY_ID'];
                $presetCommunicationKeys["{$commEntityTypeName}_{$commEntityID}"] = true;
            }

            $presentBindings = YNSIRActivity::GetBindings($ID);
            foreach ($presentBindings as &$binding) {
                $bindingOwnerID = (int)$binding['OWNER_ID'];
                $bindingOwnerTypeID = (int)$binding['OWNER_TYPE_ID'];
                $bindingOwnerTypeName = YNSIROwnerType::ResolveName($bindingOwnerTypeID);
                $bindingKey = "{$bindingOwnerTypeName}_{$bindingOwnerID}";

                //Skip present present owner if it is changed
                if ($ownerChanged && $presentOwnerTypeID === $bindingOwnerTypeID && $presentOwnerID === $bindingOwnerID) {
                    continue;
                }

                //Skip present communications - new communications already are in bindings
                if (isset($presetCommunicationKeys[$bindingKey])) {
                    continue;
                }

                $arBindings[$bindingKey] = array(
                    'OWNER_TYPE_ID' => $bindingOwnerTypeID,
                    'OWNER_ID' => $bindingOwnerID
                );
            }
            unset($binding);
            $arFields['BINDINGS'] = array_values($arBindings);

            $providerResult = $provider::postForm($arFields, $data);
            if (!$providerResult->isSuccess()) {
                $result->addErrors($providerResult->getErrors());
                return $result;
            }
            if (!YNSIRActivity::Update($ID, $arFields, false, true, array('REGISTER_SONET_EVENT' => true))) {
                $result->addError(new Main\Error(YNSIRActivity::GetLastErrorMessage()));
                return $result;
            }

            $provider::saveAdditionalData($ID, $arFields);
        }

        YNSIRActivity::SaveCommunications($ID, $arComms, $arFields, !$isNew, false);

        if ($isNew) {
            $defaults = \CUserOptions::GetOption('ynsir.activity.planner', 'defaults', array());

            //save default notify settings
            if (!isset($defaults['notify']))
                $defaults['notify'] = array();

            $defaults['notify'][$provider::getId()] = array(
                'value' => $arFields['NOTIFY_VALUE'],
                'type' => $arFields['NOTIFY_TYPE']
            );

            //save default duration settings
            $durationValue = isset($data['durationValue']) ? (int)$data['durationValue'] : 0;
            $durationType = isset($data['durationType']) ? (int)$data['durationType'] : 0;
            if ($durationValue > 0 && $durationType > 0) {
                if (!isset($defaults['duration']))
                    $defaults['duration'] = array();

                $defaults['duration'][$provider::getId()] = array(
                    'value' => $durationValue,
                    'type' => $durationType
                );
            }

            \CUserOptions::SetOption('ynsir.activity.planner', 'defaults', $defaults);
        }

        $result->setData(array(
            'ACTIVITY' => array(
                'ID' => $ID,
                'EDIT_URL' => YNSIROwnerType::GetEditUrl(YNSIROwnerType::Order, $ID),
                'VIEW_URL' => YNSIROwnerType::GetShowUrl(YNSIROwnerType::Activity, $ID),
                'NEW' => ($isNew ? 'Y' : 'N')
            )
        ));
        return $result;
    }

    private function getYNSIREntityCommunications($entityTypeID, $entityID, $communicationType)
    {
        $communications = array();

        if ($entityTypeID === YNSIROwnerType::CandidateName) {
            $communications = $this->getCommunicationsFromFM($entityTypeID, $entityID, $communicationType);
        } elseif ($entityTypeID === YNSIROwnerType::Order) {
            $communications = $this->getCommunicationsFromFM($entityTypeID, $entityID, $communicationType);
            if (!$communications) {
                $communications = YNSIRActivity::GetCompanyCommunications($entityID, $communicationType);
            }
        }

        return $communications;
    }

    private function getCommunicationsFromFM($entityTypeId, $entityId, $communicationType)
    {
        $entityTypeName = YNSIROwnerType::ResolveName($entityTypeId);
        $communications = array();

        if ($communicationType !== '') {
            $iterator = YNSIRCandidate::GetListMultiField(
                array('ID' => 'asc'),
                array('ENTITY_ID' => $entityTypeName,
                    'ELEMENT_ID' => $entityId,
                    'TYPE_ID' => $communicationType
                )
            );

            while ($row = $iterator->fetch()) {
                if (empty($row['VALUE']))
                    continue;

                $communications[] = array(
                    'ENTITY_ID' => $entityId,
                    'ENTITY_TYPE_ID' => $entityTypeId,
                    'ENTITY_TYPE' => $entityTypeName,
                    'TYPE' => $communicationType,
                    'VALUE' => $row['VALUE'],
                    'VALUE_TYPE' => $row['VALUE_TYPE']
                );
            }
        } else {
            $communications[] = array(
                'ENTITY_ID' => $entityId,
                'ENTITY_TYPE_ID' => $entityTypeId,
                'ENTITY_TYPE' => $entityTypeName,
                'TYPE' => $communicationType
            );
        }

        return $communications;
    }

    private function makeDescriptionHtml($description, $type)
    {
        $type = (int)$type;
        if ($type === CCrmContentType::BBCode) {
            $bbCodeParser = new CTextParser();
            $html = $bbCodeParser->convertText($description);
        } elseif ($type === CCrmContentType::Html) {
            //Already sanitaized
            $html = $description;
        } else//CCrmContentType::PlainText and other
        {
            $html = preg_replace("/[\r\n]+/" . BX_UTF_PCRE_MODIFIER, "<br>", htmlspecialcharsbx($description));
        }

        return $html;
    }
}