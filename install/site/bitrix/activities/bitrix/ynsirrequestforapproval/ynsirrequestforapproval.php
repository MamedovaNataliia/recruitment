<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
if (!CModule::IncludeModule('ynsirecruitment')) {
    die;
}

class CBPYnsirRequestForApproval
    extends CBPCompositeActivity
    implements IBPEventActivity, IBPActivityExternalEventListener
{
    const ACTIVITY = 'YnsirRequestForApproval';

    private $taskId = 0;
    private $taskUsers = array();
    private $subscriptionId = 0;
    private $isInEventActivityMode = false;
    private $taskStatus = false;
    private static $ojOrder = array();
    private static $intProcessStatus = 1;
    private static $errorCode = '';

    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            "Title" => "",
            "Users" => null,
            "Name" => null,
            "Description" => null,
            "TaskButtonMessage" => "",
            'RequiredComment' => '',
            'LabelComment' => '',

            // data result
            'OOrderId' => null,
            'OOrderTitle' => '',
            'OComment' => null,
            'ODetailLink' => null,
            'OIntegrationTime' => null,
            'ODateTimeFormat' => null,
            'ORequesterID' => null,
            'ORequester' => null,
            'OErrorCode' => null,
            'OProcessStatus' => null,
        );

        $this->SetPropertiesTypes(
            array(
                'OOrderId' => array('Type' => 'int'),
                'OOrderTitle' => array('Type' => 'string'),
                'OComment' => array('Type' => 'string'),
                'OErrorCode' => array('Type' => 'string'),
                'ODetailLink' => array('Type' => 'string'),
                'OIntegrationTime' => array('Type' => 'string'),
                'ODateTimeFormat' => array('Type' => 'string'),
                'ORequesterID' => array('Type' => 'int'),
                'ORequesterID' => array('Type' => 'user'),
                'OProcessStatus' => array('Type' => 'int'),
            ));
    }

    protected function ReInitialize()
    {
        global $USER;
        parent::ReInitialize();
        $this->OOrderId = '';
        $this->OOrderTitle = '';
        $this->OComment = '';
        $this->ODetailLink = '';
        $this->OIntegrationTime = '';
        $this->ODateTimeFormat = '';
        $this->ORequesterID = '';
        $this->ORequester = '';
        $this->OErrorCode = '';
        $this->OProcessStatus = 0;
    }

    public function Execute()
    {
        if ($this->isInEventActivityMode)
            return CBPActivityExecutionStatus::Closed;

        $this->Subscribe($this);

        $this->isInEventActivityMode = false;
        return CBPActivityExecutionStatus::Executing;
    }

    public function Subscribe(IBPActivityExternalEventListener $eventHandler)
    {
        if ($eventHandler == null)
            throw new Exception("eventHandler");

        $this->isInEventActivityMode = true;

        $arUsersTmp = $this->Users;
        if (!is_array($arUsersTmp))
            $arUsersTmp = array($arUsersTmp);

        $this->WriteToTrackingService(str_replace("#VAL#", "{=user:" . implode("}, {=user:", $arUsersTmp) . "}", GetMessage("BPRIORA_ACT_TRACK1")));

        $rootActivity = $this->GetRootActivity();
        $documentId = $rootActivity->GetDocumentId();

        $arUsers = CBPHelper::ExtractUsers($arUsersTmp, $documentId, false);

        $arParameters = $this->Parameters;
        if (!is_array($arParameters))
            $arParameters = array($arParameters);

        $runtime = CBPRuntime::GetRuntime();
        $documentService = $runtime->GetService("DocumentService");

        $arParameters["DOCUMENT_ID"] = $documentId;
        $arParameters["DOCUMENT_URL"] = $documentService->GetDocumentAdminPage($documentId);
        $arParameters["DOCUMENT_TYPE"] = $this->GetDocumentType();
        $arParameters["FIELD_TYPES"] = $documentService->GetDocumentFieldTypes($arParameters["DOCUMENT_TYPE"]);
        $arParameters["REQUEST"] = array();

        $arParameters["TaskButtonMessage"] = "OK";

        $arParameters['RequiredComment'] = $this->RequiredComment;
        $arParameters['LabelComment'] = $this->LabelComment;
        $taskService = $this->workflow->GetService("TaskService");
        $this->taskId = $taskService->CreateTask(
            array(
                "USERS" => $arUsers,
                "WORKFLOW_ID" => $this->GetWorkflowInstanceId(),
                "ACTIVITY" => static::ACTIVITY,
                "ACTIVITY_NAME" => $this->name,
                "OVERDUE_DATE" => $this->OverdueDate,
                "NAME" => $this->Name,
                "DESCRIPTION" => $this->Description,
                "PARAMETERS" => $arParameters,
                'DOCUMENT_NAME' => $documentService->GetDocumentName($documentId)
            )
        );
        $this->taskUsers = $arUsers;

        $this->workflow->AddEventHandler($this->name, $eventHandler);
    }

    public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
    {
        if ($eventHandler == null)
            throw new Exception("eventHandler");

        $taskService = $this->workflow->GetService("TaskService");
        if ($this->taskStatus === false) {
            $taskService->DeleteTask($this->taskId);
        } else {
            $taskService->Update($this->taskId, array(
                'STATUS' => $this->taskStatus
            ));
        }

        $timeoutDuration = $this->CalculateTimeoutDuration();
        if ($timeoutDuration > 0) {
            $schedulerService = $this->workflow->GetService("SchedulerService");
            $schedulerService->UnSubscribeOnTime($this->subscriptionId);
        }

        $this->workflow->RemoveEventHandler($this->name, $eventHandler);

        $this->taskId = 0;
        $this->taskUsers = array();
        $this->taskStatus = false;
        $this->subscriptionId = 0;
    }

    public function HandleFault(Exception $exception)
    {
        if ($exception == null)
            throw new Exception("exception");

        $status = $this->Cancel();
        if ($status == CBPActivityExecutionStatus::Canceling)
            return CBPActivityExecutionStatus::Faulting;

        return $status;
    }

    public function Cancel()
    {
        if (!$this->isInEventActivityMode && $this->taskId > 0)
            $this->Unsubscribe($this);

        return CBPActivityExecutionStatus::Closed;
    }

    public function OnExternalEvent($eventParameters = array())
    {
        global $DB, $USER;


        $this->OOrderId = $eventParameters['JOB_ORDER_ID'];
        $this->OOrderTitle = self::$ojOrder['TITLE'];
        $this->OComment = $eventParameters['COMMENT'];
        $this->ODetailLink = '/recruitment/candidate/detail/' . $this->OOrderId . '/';
        $sFormatDB = 'YYYY-MM-DD HH:MI:SS';
        $this->ORequesterID = $USER->GetID();
        $this->ORequester = 'user_' . $USER->GetID();
        $this->ODateTimeFormat = CSite::GetDateFormat();
        $this->OIntegrationTime = $DB->FormatDate(date("Y-m-d H:i:s"), $sFormatDB, $this->ODateTimeFormat);
        $this->OProcessStatus = self::$intProcessStatus;
        $this->OErrorCode = self::$errorCode;

        if ($this->executionStatus == CBPActivityExecutionStatus::Closed)
            return;

        if (!array_key_exists("USER_ID", $eventParameters) || intval($eventParameters["USER_ID"]) <= 0)
            return;

        if (empty($eventParameters["REAL_USER_ID"]))
            $eventParameters["REAL_USER_ID"] = $eventParameters["USER_ID"];

        $rootActivity = $this->GetRootActivity();
        $arUsers = $this->taskUsers;
        if (empty($arUsers))
            $arUsers = CBPHelper::ExtractUsers($this->Users, $this->GetDocumentId(), false);

        $eventParameters["USER_ID"] = intval($eventParameters["USER_ID"]);
        $eventParameters["REAL_USER_ID"] = intval($eventParameters["REAL_USER_ID"]);
        if (!in_array($eventParameters["USER_ID"], $arUsers))
            return;

        $cancel = !empty($eventParameters['CANCEL']);

        $taskService = $this->workflow->GetService("TaskService");
        $taskService->MarkCompleted($this->taskId, $eventParameters["REAL_USER_ID"], $cancel ? CBPTaskUserStatus::Cancel : CBPTaskUserStatus::Ok);


        if(static::$errorCode == 'unknown'){
            $this->WriteToTrackingService(GetMessage('BPJOVA_ACT_ERROR_UNKNOWN'));
        }elseif(static::$errorCode == 'not_permission'){
            $this->WriteToTrackingService(GetMessage('BPJOVA_ACT_ERROR_DONT_PERMS'));
        }elseif(static::$intProcessStatus == 1){
            $this->WriteToTrackingService(
                str_replace(
                    array("#PERSON#", "#COMMENT#"),
                    array("{=user:user_" . $eventParameters["REAL_USER_ID"] . "}", (strlen($eventParameters["COMMENT"]) > 0 ? ": " . $eventParameters["COMMENT"] : "")),
                    GetMessage($cancel ? 'BPRIORA_ACT_CANCEL_APPROVE_TRACK' : 'BPRIORA_ACT_REQUEST_APPROVE_TRACK')
                ),
                $eventParameters["REAL_USER_ID"]
            );
        }
        if ($cancel)
            $this->cancelUsers[] = $eventParameters['USER_ID'];

        $rootActivity->SetVariables($eventParameters["RESPONCE"]);

        $this->taskStatus = $cancel ? CBPTaskStatus::CompleteCancel : CBPTaskStatus::CompleteOk;
        $this->Unsubscribe($this);

        $cancel ? $this->ExecuteOnCancel() : $this->ExecuteOnOk();
    }

    protected function ExecuteOnOk()
    {
        if (count($this->arActivities) <= 0) {
            $this->workflow->CloseActivity($this);
            return;
        }

        /** @var CBPActivity $activity */
        $activity = $this->arActivities[0];
        $activity->AddStatusChangeHandler(self::ClosedEvent, $this);
        $this->workflow->ExecuteActivity($activity);
    }

    protected function ExecuteOnCancel()
    {
        if (count($this->arActivities) <= 1) {
            $this->workflow->CloseActivity($this);
            return;
        }

        /** @var CBPActivity $activity */
        $activity = $this->arActivities[1];
        $activity->AddStatusChangeHandler(self::ClosedEvent, $this);
        $this->workflow->ExecuteActivity($activity);
    }

    protected function OnEvent(CBPActivity $sender)
    {
        $sender->RemoveStatusChangeHandler(self::ClosedEvent, $this);
        $this->workflow->CloseActivity($this);
    }

    public static function ShowTaskForm($arTask, $userId, $userName = "", $arRequest = null)
    {

        $form = '';

        $runtime = CBPRuntime::GetRuntime();
        $runtime->StartRuntime();
        $documentService = $runtime->GetService("DocumentService");
        ob_start();
        $GLOBALS["APPLICATION"]->IncludeComponent(
            "ynsirecruitment:bizproc.request.joborder",
            ".default",
            array(
                'RequiredComment' => $arTask['PARAMETERS']['RequiredComment'],
                'LabelComment' => $arTask['PARAMETERS']['LabelComment'],
            )
        );
        $form .= ob_get_contents();
        ob_end_clean();

        $buttons =
            '<input type="submit" name="approve" value="' . GetMessage("BPRIORA_ACT_BUTTON_SAVE") . 'ddd"/>
			<input type="submit" name="cancel" value="' . GetMessage("BPRIORA_ACT_BUTTON_CANCEL") . '"/>';

        return array($form, $buttons);
    }

    public static function getTaskControls($arTask)
    {
        return array(
            'BUTTONS' => array(
                array(
                    'TYPE' => 'submit',
                    'TARGET_USER_STATUS' => CBPTaskUserStatus::Ok,
                    'NAME' => 'approve',
                    'VALUE' => 'Y',
                    'TEXT' => GetMessage("BPRIORA_ACT_BUTTON_SAVE")
                ),
                array(
                    'TYPE' => 'submit',
                    'TARGET_USER_STATUS' => CBPTaskUserStatus::Cancel,
                    'NAME' => 'cancel',
                    'VALUE' => 'N',
                    'TEXT' => GetMessage("BPRIORA_ACT_BUTTON_CANCEL")
                )
            )
        );
    }

    protected static function getEventParameters($task, $request)
    {
        global $USER;
        $result = array(
            "JOB_ORDER_ID" => isset($request["ynsirc_job_order"]) ? trim($request["ynsirc_job_order"]) : '',
            "COMMENT" => isset($request["comment"]) ? trim($request["comment"]) : '',
        );
        if (empty($request['cancel'])) {
            $request['task'] = $task;
            $result['RESPONCE'] = static::getTaskResponse($request);
        } else
            $result['CANCEL'] = true;

        return $result;
    }

    protected static function getTaskResponse($request)
    {
        global $DB;
        if (!CModule::IncludeModule('ynsirecruitment')) {
            die;
        }
        if ($request['ynsirc_job_order'] <= 0) {
            $arErrorsTmp[] = array(
                'code' => 'errorattachfile',
                'message' => GetMessage("BPRIORA_ACT_ERROR_EMPTY", array('#ENTITY#' => GetMessage('BPRIORA_ACT_JOB_ORDER'))),
                'parameter' => $file_name,
            );
        }
        $arFilter['STATUS'] = JOStatus::JOSTATUS_NEW;
        $arFilter['ID'] = $request['ynsirc_job_order'];
        $rs = YNSIRJobOrder::GetListJobOrder(array("ID" => "DESC"), $arFilter);
        $arOrder = $rs->Fetch();
        if (empty($arOrder) && $request['ynsirc_job_order'] > 0) {
            $arErrorsTmp[] = array(
                'code' => 'errorattachfile',
                'message' => GetMessage("BPRIORA_ACT_ERROR_NOT_EXIST", array('#ENTITY#' => GetMessage('BPRIORA_ACT_JOB_ORDER'))),
                'parameter' => $file_name,
            );
        } else {
            self::$ojOrder = $arOrder;
        }
        if ($request['task']['PARAMETERS']['RequiredComment'] == 1 && strlen($request['comment']) <= 0) {
            $arErrorsTmp[] = array(
                'code' => 'errorattachfile',
                'message' => GetMessage("BPRIORA_ACT_ERROR_EMPTY", array('#ENTITY#' => GetMessage('BPRIORA_ACT_COMMENT'))),
                'parameter' => $file_name,
            );
        }
        $m = "";
        if (count($arErrorsTmp) > 0) {
            static::$intProcessStatus = 0;
            $m = "";
            foreach ($arErrorsTmp as $e)
                $m .= $e["message"] . "<br/>";
            $m = rtrim($m, "<br/>");
            throw new CBPArgumentException($m);
        } else {
            //TODO implement to change status to WAITING
            $arResult['USER_PERMISSIONS'] = YNSIRPerms_::GetCurrentUserPermissions();
            $canApproval = YNSIRJobOrder::CheckUpdatePermissionSec($request['ynsirc_job_order'], YNSIRConfig::OS_APPROVE, $arResult['USER_PERMISSIONS']);
            $arResult['JO_STATUS'] = YNSIRJobOrder::listStatusCanUpdate($canApproval, $arResult['DATA']['STATUS'], $arResult['JO_STATUS']);
            if(isset($arResult['JO_STATUS'][JOStatus::JOSTATUS_WAITING])) {
                $arDataNew['STATUS'] = JOStatus::JOSTATUS_WAITING;
                $arDataOld['STATUS'] = JOStatus::JOSTATUS_NEW;
                $bUpdate = YNSIRJobOrder::Update($request['ynsirc_job_order'], $arDataNew, $arDataOld,true,array(),false);
                if ($bUpdate > 0) {
                    static::$intProcessStatus = 1;
                    static::$errorCode = 'sucess';

//                    $this->WriteToTrackingService('the Job Order had been approved.');
                } else {
                    static::$intProcessStatus = 0;
                    static::$errorCode = 'unknown';
//                    $this->WriteToTrackingService('An error occurred updating.');
                }
            }else{
                static::$intProcessStatus = 0;
                static::$errorCode = 'not_permission';
//                $this->WriteToTrackingService("Don't have permission.");
            }

        }
        return true;
    }

    public static function PostTaskForm($task, $userId, $request, &$errors, $userName = "", $realUserId = null)
    {
        $errors = array();

        try {
            $userId = intval($userId);
            if ($userId <= 0)
                throw new CBPArgumentNullException("userId");

            $arEventParameters = static::getEventParameters($task, $request);
            $arEventParameters["USER_ID"] = $userId;
            $arEventParameters["REAL_USER_ID"] = $realUserId;
            $arEventParameters["USER_NAME"] = $userName;
            CBPRuntime::SendExternalEvent($task["WORKFLOW_ID"], $task["ACTIVITY_NAME"], $arEventParameters);
            return true;
        } catch (Exception $e) {
            $errors[] = array(
                "code" => $e->getCode(),
                "message" => $e->getMessage(),
                "file" => $e->getFile() . " [" . $e->getLine() . "]",
            );
        }

        return false;
    }

    public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
    {
        $arErrors = array();

        if (!array_key_exists("Users", $arTestProperties)) {
            $bUsersFieldEmpty = true;
        } else {
            if (!is_array($arTestProperties["Users"]))
                $arTestProperties["Users"] = array($arTestProperties["Users"]);

            $bUsersFieldEmpty = true;
            foreach ($arTestProperties["Users"] as $userId) {
                if (!is_array($userId) && (strlen(trim($userId)) > 0) || is_array($userId) && (count($userId) > 0)) {
                    $bUsersFieldEmpty = false;
                    break;
                }
            }
        }

        /* check users*/
        if ($bUsersFieldEmpty)
            $arErrors[] = array("code" => "NotExist", "parameter" => "Users", "message" => GetMessage("BPRIORA_ACT_PROP_USERS"));

        /* check name */
        if (!array_key_exists("Name", $arTestProperties) || strlen($arTestProperties["Name"]) <= 0)
            $arErrors[] = array("code" => "NotExist", "parameter" => "Name", "message" => GetMessage("BPRIORA_ACT_PROP_ASS_NAME"));

        return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
    }

    private function CalculateTimeoutDuration()
    {
        $timeoutDuration = ($this->IsPropertyExists("TimeoutDuration") ? $this->TimeoutDuration : 0);

        $timeoutDurationType = ($this->IsPropertyExists("TimeoutDurationType") ? $this->TimeoutDurationType : "s");
        $timeoutDurationType = strtolower($timeoutDurationType);
        if (!in_array($timeoutDurationType, array("s", "d", "h", "m")))
            $timeoutDurationType = "s";

        $timeoutDuration = intval($timeoutDuration);
        switch ($timeoutDurationType) {
            case 'd':
                $timeoutDuration *= 3600 * 24;
                break;
            case 'h':
                $timeoutDuration *= 3600;
                break;
            case 'm':
                $timeoutDuration *= 60;
                break;
            default:
                break;
        }

        return $timeoutDuration;
    }

    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null)
    {
        $runtime = CBPRuntime::GetRuntime();
        $documentService = $runtime->GetService("DocumentService");

        $arMap = array(
            "Users" => "requested_users",
            "Name" => "requested_name",
            "RequiredComment" => "required_comment",
            "LabelComment" => "label_comment",
            "Description" => "description",
            "TaskButtonMessage" => "task_button_message",
        );

        if (!is_array($arWorkflowParameters))
            $arWorkflowParameters = array();
        if (!is_array($arWorkflowVariables))
            $arWorkflowVariables = array();

        if (!is_array($arCurrentValues)) {
            $arCurrentValues = array();
            $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
            if (is_array($arCurrentActivity["Properties"])) {
                foreach ($arMap as $k => $v) {
                    if (array_key_exists($k, $arCurrentActivity["Properties"])) {
                        if ($k == "Users")
                            $arCurrentValues[$arMap[$k]] = CBPHelper::UsersArrayToString($arCurrentActivity["Properties"][$k], $arWorkflowTemplate, $documentType);
                        else
                            $arCurrentValues[$arMap[$k]] = $arCurrentActivity["Properties"][$k];
                    } else {
                        $arCurrentValues[$arMap[$k]] = "";
                    }
                }
            } else {
                foreach ($arMap as $k => $v)
                    $arCurrentValues[$arMap[$k]] = "";
            }
        }

        $arFieldTypes = $documentService->GetDocumentFieldTypes($documentType);
        $arDocumentFields = $documentService->GetDocumentFields($documentType);

        $javascriptFunctions = $documentService->GetJSFunctionsForFields($documentType, "objFields", $arDocumentFields, $arFieldTypes);

        return $runtime->ExecuteResourceFile(
            __FILE__,
            "properties_dialog.php",
            array(
                "arCurrentValues" => $arCurrentValues,
                "arDocumentFields" => $arDocumentFields,
                "arFieldTypes" => $arFieldTypes,
                "javascriptFunctions" => $javascriptFunctions,
                "formName" => $formName,
                "popupWindow" => &$popupWindow,
                "arActionRequiredComment" => array(
                    0 => 'No',
                    1 => 'Yes'
                )
            )
        );
    }

    public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
    {
        $arErrors = array();

        $runtime = CBPRuntime::GetRuntime();

        $arMap = array(
            "requested_users" => "Users",
            "requested_name" => "Name",
            "required_comment" => "RequiredComment",
            "label_comment" => "LabelComment",
            "description" => "Description",
            "task_button_message" => "TaskButtonMessage",
        );

        $arProperties = array();
        foreach ($arMap as $key => $value) {
            if ($key == "requested_users")
                continue;
            $arProperties[$value] = $arCurrentValues[$key];
        }

        $arProperties["Users"] = CBPHelper::UsersStringToArray($arCurrentValues["requested_users"], $documentType, $arErrors);
        if (count($arErrors) > 0)
            return false;

        $arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
        if (count($arErrors) > 0)
            return false;

        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $arCurrentActivity["Properties"] = $arProperties;

        return true;
    }

    public static function getConfig()
    {
        $arResult = array();
        $arRConfig = new SIRecruitmentConfigs();
        $arResult['COMPANY'] = $arRConfig->getListCompany();
        $iblockID = COption::GetOptionInt("intranet", "iblock_structure");
        $arFilter = array('IBLOCK_ID' => $iblockID, 'GLOBAL_ACTIVE' => 'Y');
        $dbRes = CIBlockSection::GetList(array('left_margin' => asc), $arFilter, false, array('UF_HEAD'));
        $arDepart = array();
        while ($arRes = $dbRes->Fetch()) {
            $arDepart[$arRes['ID']] = $arRes;
        }

        $arResult['DEPARTMENT'] = array();
        foreach ($arResult['COMPANY'] as $keyCompany => $itemCompany) {
            $iLevel = 0;
            $bFlagSub = false;
            foreach ($arDepart as $keyDept => $itemDept) {
                if ($bFlagSub == true && $itemDept['DEPTH_LEVEL'] <= $iLevel)
                    break;
                if ($keyCompany == $keyDept) {
                    $bFlagSub = true;
                    $iLevel = intval($itemDept['DEPTH_LEVEL']);
                    $arResult['DEPARTMENT'][$keyCompany][$keyCompany] = $itemCompany;
                }
                if ($bFlagSub == true && $itemDept['DEPTH_LEVEL'] > $iLevel) {
                    $arResult['DEPARTMENT'][$keyCompany][$keyDept] = $itemDept['NAME'];
                }
            }
        }
        return $arResult;
    }
}