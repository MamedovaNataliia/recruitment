<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
if (!CModule::IncludeModule('ynsirecruitment')) {
    die;
}

class CBPYnsirApprovedOrderJobActivity
    extends CBPCompositeActivity
    implements IBPEventActivity, IBPActivityExternalEventListener
{
    const ACTIVITY = 'YnsirApprovedOrderJobActivity';

    private $taskId = 0;
    private $taskUsers = array();
    private $subscriptionId = 0;
    private $isInEventActivityMode = false;
    private $taskStatus = false;
    private static $int_id = 0;
    private static $int_file_id = 0;
    private static $strlDetailLink = '';
    private static $strHistoryLink = '';
    private static $intProcessStatus = 1;
    private static $codeError = '';
    private static $oj = array();
    private static $strPostJob = '';

    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            "Title" => "",
            "Users" => null,
            "action" => null,
            "Name" => null,
            "Description" => null,
            "job_order_id" => null,
            "TaskButtonMessage" => "",

            // data result
            'OOrderId' => null,
            'OTitle' => null,
            'OComment' => null,
            'ODetailLink' => null,
            'OIntegrationTime' => null,
            'ODateTimeFormat' => null,
//            'OUserIdRequest' => null,
            'OLastApprover' => null,
            'OProcessStatus' => null,
            'OErrorCode' => null,
        );

        $this->SetPropertiesTypes(
            array(
                'OOrderId' => array('Type' => 'int'),
                'OTitle' => array('Type' => 'string'),
                'OComment' => array('Type' => 'string'),
                'OErrorCode' => array('Type' => 'string'),
                'ODetailLink' => array('Type' => 'string'),
                'OIntegrationTime' => array('Type' => 'string'),
                'ODateTimeFormat' => array('Type' => 'string'),
//                'OUserIdRequest' => array('Type' => 'user'),
                'OLastApprover' => array('Type' => 'user'),
                'OProcessStatus' => array('Type' => 'int'),
            ));
    }

    protected function ReInitialize()
    {
        global $USER;
        parent::ReInitialize();
        $this->OOrderId = '';
        $this->OComment = '';
        $this->ODetailLink = '';
        $this->OIntegrationTime = '';
        $this->ODateTimeFormat = '';
//        $this->OUserIdRequest = '';
        $this->OLastApprover = '';
        $this->OErrorCode = '';
        $this->OProcessStatus = 0;
    }

    public function Execute()
    {
        if ($this->isInEventActivityMode)
            return CBPActivityExecutionStatus::Closed;

        $abc = $this->job_order_id;

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

        $this->WriteToTrackingService(str_replace("#VAL#", "{=user:" . implode("}, {=user:", $arUsersTmp) . "}", GetMessage("BPRIORA_ACT_TRACK")));

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
        $arParameters["DOCUMENT_TYPE"] = $documentType = $this->GetDocumentType();
        $arParameters["FIELD_TYPES"] = $documentService->GetDocumentFieldTypes($arParameters["DOCUMENT_TYPE"]);
        $arParameters["REQUEST"] = array();

        $arParameters["TaskButtonMessage"] = "OK";

        $arParameters["TaskButtonCancelMessage"] = "Refuse";
        $arParameters["job_order_id"] = $this->job_order_id;
        $arParameters["action"] = $this->action;

        $taskService = $this->workflow->GetService("TaskService");
        $arUsersTmp = array();
        $arParameters["USER_PERMS"] = $arUserPerms = CBPHelper::ExtractUsers($this->Users, $documentId, false);
        foreach ($arUserPerms as &$uuid) {
            $uuid = 'U' . $uuid;
        }

        YNSIRPerms_::AddEntityAttr(YNSIR_JOB_ORDER, $this->job_order_id, $arUserPerms, JOStatus::JOSTATUS_APPROVAL,$this->GetWorkflowInstanceId());

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
        $this->OComment = $eventParameters['COMMENT'];
        $this->ODetailLink = '/recruitment/job-order/detail/' . $this->OOrderId . '/';
        $sFormatDB = 'YYYY-MM-DD HH:MI:SS';
//        $this->OUserIdRequest = $USER->GetID();
        $this->OLastApprover = 'user_'.$USER->GetID();
        $this->ODateTimeFormat = CSite::GetDateFormat();
        $this->OIntegrationTime = $DB->FormatDate(date("Y-m-d H:i:s"), $sFormatDB, $this->ODateTimeFormat);
        $this->OProcessStatus = static::$intProcessStatus;
        $this->OErrorCode = static::$codeError;
        $this->OTitle = static::$oj['TITLE'];

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

        if (static::$codeError == 'unknown') {
            $this->WriteToTrackingService(GetMessage('BPJOVA_ACT_ERROR_UNKNOWN'));
        } elseif (static::$codeError == 'not_permission') {
            $this->WriteToTrackingService(GetMessage('BPJOVA_ACT_ERROR_DONT_PERMS'));
        } elseif (static::$codeError == 'job_order_not_waiting') {
            $this->WriteToTrackingService(GetMessage('BPJOVA_ACT_ERROR_DONT_WAITING'));
        } elseif (static::$intProcessStatus == 1) {
            $this->WriteToTrackingService(
                str_replace(
                    array("#PERSON#", "#COMMENT#"),
                    array("{=user:user_" . $eventParameters["REAL_USER_ID"] . "}", (strlen($eventParameters["COMMENT"]) > 0 ? ": " . $eventParameters["COMMENT"] : "")),
                    GetMessage($cancel ? 'BPRIORA_ACT_CANCEL_TRACK' : 'BPRIORA_ACT_APPROVE_TRACK')
                ),
                $eventParameters["REAL_USER_ID"]
            );
        }
        if ($cancel)
            $this->cancelUsers[] = $eventParameters['USER_ID'];

        $rootActivity->SetVariables($eventParameters["RESPONCE"]);

        $this->taskStatus = $cancel ? CBPTaskStatus::CompleteCancel : CBPTaskStatus::CompleteOk;

        YNSIRPerms_::DeleteEntity(YNSIR_JOB_ORDER, $eventParameters['JOB_ORDER_ID'], JOStatus::JOSTATUS_APPROVAL,$this->GetWorkflowInstanceId());

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
            "ynsirecruitment:bizproc.approval.joborder",
            ".default",
            array(
                'job_order_id' => $arTask['PARAMETERS']['job_order_id'],
                'action' => $arTask['PARAMETERS']['action'],
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
            "JOB_ORDER_ID" => isset($task['PARAMETERS']['job_order_id']) ? trim($task['PARAMETERS']['job_order_id']) : '',
            "COMMENT" => isset($request["comment"]) ? trim($request["comment"]) : '',
        );

        if (empty($request['cancel'])) {
            $request['ynsirc_job_order'] = $task['PARAMETERS']['job_order_id'];
            $request['action'] = $task['PARAMETERS']['action'];
        } else {
            $request['ynsirc_job_order'] = $task['PARAMETERS']['job_order_id'];
            $request['action'] = $task['PARAMETERS']['action'];
            $result['CANCEL'] = true;
        }
        $result['task'] = $task;
        $result['RESPONCE'] = static::getTaskResponse($request);
        return $result;
    }

    protected static function getTaskResponse($request)
    {
        global $DB;
        if (!CModule::IncludeModule('ynsirecruitment')) {
            die;
        }
        //check perms
        $canApproval = YNSIRJobOrder::CheckUpdatePermissionSec($request['ynsirc_job_order'], YNSIRConfig::OS_APPROVE, $arResult['USER_PERMISSIONS']);

        if (!$canApproval) {
            static::$intProcessStatus = 0;
            static::$codeError = 'not_permission';
            return true;
        }
        $actApproval = $request['action'] == RECRUITMENT_ACTION_APPROVED;
        $arErrorsTmp1 = array();
        $arStatus = YNSIRGeneral::getListJobStatus();
        if ($request['ynsirc_job_order'] <= 0) {
            static::$intProcessStatus = 0;
            static::$codeError = 'job_order_null';
        }
        //region validate job order
        if (!$actApproval) {
            if (empty($request['ynsirc_job_order_status'])) {
                $arErrorsTmp1[] = array(
                    'code' => 'ynsirc_job_order_status',
                    'message' => GetMessage("BPRIORA_ACT_ERROR_EMPTY", array('#ENTITY#' => GetMessage('YNSIR_TITLE_JOB_ORDER_STATUS'))),
                    'parameter' => 'ynsirc_job_order_status',
                );
            } elseif (!isset($arStatus[$request['ynsirc_job_order_status']])) {
                $arErrorsTmp1[] = array(
                    'code' => 'ynsirc_job_order_status',
                    'message' => GetMessage("BPRIORA_ACT_ERROR_NOT_EXIST", array('#ENTITY#' => GetMessage('YNSIR_TITLE_JOB_ORDER_STATUS'))),
                    'parameter' => 'ynsirc_job_order_status',
                );
            }
        }
        $arFilter['ID'] = $request['ynsirc_job_order'];
        if ($actApproval) {
            $arFilter['STATUS'] = JOStatus::JOSTATUS_WAITING;
            $arFilter['CHECK_PERMISSIONS'] = 'N';
        }
        $oj = array();
        $rs_ = YNSIRJobOrder::GetListJobOrder(array("ID" => "DESC"), $arFilter, false, false, false);
        $a = $rs_->Fetch();
        if (empty($a) && intval($request['ynsirc_job_order']) > 0) {
            static::$intProcessStatus = 0;
            static::$codeError = 'job_order_not_waiting';
        } else {
            self::$oj = $rs_->Fetch();
        }
        if (strlen($request['comment']) <= 0) {
            $arErrorsTmp1[] = array(
                'code' => 'comment',
                'message' => GetMessage("BPRIORA_ACT_ERROR_NOT_EXIST", array('#ENTITY#' => GetMessage('BPRIORA_ACT_COMMENT'))),
                'parameter' => 'comment',
            );
        }
        //endregion
        $m = "";
        if (count($arErrorsTmp1) > 0) {
            $m = "";
            foreach ($arErrorsTmp1 as $e)
                $m .= $e["message"] . "<br/>";
            $m = rtrim($m, "<br/>");
            throw new CBPArgumentException($m);
        } else {
            if (static::$intProcessStatus == 0) {
                return true;
            }
            $arResult['USER_PERMISSIONS'] = YNSIRPerms_::GetCurrentUserPermissions();
            $arResult['JO_STATUS'] = YNSIRJobOrder::listStatusCanUpdate($canApproval, $arResult['DATA']['STATUS'], $arResult['JO_STATUS']);
            if (isset($arResult['JO_STATUS'][JOStatus::JOSTATUS_APPROVAL]) && isset($arResult['JO_STATUS'][JOStatus::JOSTATUS_REJECT])) {

                if ($actApproval) {
                    if (!empty($request['cancel'])) {
                        //TODO implement change approve status to REJECT.
                        $arDataNew['STATUS'] = JOStatus::JOSTATUS_REJECT;
                        $arDataOld['STATUS'] = JOStatus::JOSTATUS_WAITING;
                        $bUpdate = YNSIRJobOrder::Update($request['ynsirc_job_order'], $arDataNew, $arDataOld,true,array(),false);                        
                        if ($bUpdate > 0) {
                            static::$intProcessStatus = 1;
                        } else {
                            static::$intProcessStatus = 0;
                            static::$codeError = 'unknown';
                        }
                    } else {
                        //TODO implement change approve status to APPROVAL
                        $arDataNew['STATUS'] = JOStatus::JOSTATUS_APPROVAL;
                        $arDataOld['STATUS'] = JOStatus::JOSTATUS_WAITING;
                        $bUpdate = YNSIRJobOrder::Update($request['ynsirc_job_order'], $arDataNew, $arDataOld,true,array(),false);
                        if ($bUpdate > 0) {
                            static::$intProcessStatus = 1;
                        } else {
                            static::$intProcessStatus = 0;
                            static::$codeError = 'unknown';

                        }
                    }
                } else {
                    //TODO implement change approve status to OTHERs with action change status;
                }
            } else {
                static::$intProcessStatus = 0;
                static::$codeError = 'not_permission';
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
        /* check name */
        if (!array_key_exists("job_order_id", $arTestProperties) || strlen($arTestProperties["job_order_id"]) <= 0)
            $arErrors[] = array("code" => "NotExistMaterialCode", "parameter" => "job_order_id", "message" => GetMessage("BPJOVA_JOB_ORDER_ID_MISSING"));
        if (!array_key_exists("action", $arTestProperties) || strlen($arTestProperties["action"]) <= 0 || ($arTestProperties["action"] != RECRUITMENT_ACTION_APPROVED
                && $arTestProperties["action"] != RECRUITMENT_ACTION_CHANGE_STATUS))
            $arErrors[] = array("code" => "NotExistMaterialCode", "parameter" => "action", "message" => GetMessage("BPJOVA_ACT_ACTION_MISSING"));
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
            "job_order_id" => "job_order_id",
            "action" => "action",
            "Name" => "requested_name",
            "Description" => "requested_description",
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
                'arAction' => array(
                    RECRUITMENT_ACTION_APPROVED => 'Approved',
                    RECRUITMENT_ACTION_CHANGE_STATUS => 'Change Status',
                ),
            )
        );
    }

    public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
    {
        $arErrors = array();

        $runtime = CBPRuntime::GetRuntime();

        $arMap = array(
            "requested_users" => "Users",
            "job_order_id" => "job_order_id",
            "action" => "action",
            "title" => "title",
            "requested_name" => "Name",
            "requested_description" => "Description",
            "task_button_message" => "TaskButtonMessage",
        );

        $arProperties = array();
        foreach ($arMap as $key => $value) {
            if ($key == "requested_users")
                continue;
            $arProperties[$value] = $arCurrentValues[$key];
        }

        $arProperties["Users"] = CBPHelper::UsersStringToArray($arCurrentValues["requested_users"], $documentType, $arErrors);

        $arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
        if (count($arErrors) > 0)
            return false;

        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $arCurrentActivity["Properties"] = $arProperties;

        return true;
    }

}