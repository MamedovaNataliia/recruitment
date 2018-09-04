<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Disk\File;

if (!CModule::IncludeModule("blog")) {
    return;
}

class CBPYnsirOnboardingActivity extends CBPActivity
{

    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            "Title" => "",
            'candidate_id' => null,
            'job_order_id' => null,
            'asp_email' => null,

            // data result
            'OFullName' => null, // Candidate
            'ODetailLink' => null, // asp link
            'OIntegrationTime' => null,
            'ODateTimeFormat' => null,
            'ORequesterID' => null,
            'ORequester' => null,
            'ONote' => null,
            'OErrorCode' => null,
            'OProcessStatus' => null,
        );
        $this->SetPropertiesTypes(
            array(
                'OFullName' => array('Type' => 'string'),
                'ODetailLink' => array('Type' => 'string'),
                'OErrorCode' => array('Type' => 'string'),
                'OIntegrationTime' => array('Type' => 'string'),
                'ODateTimeFormat' => array('Type' => 'string'),
                'ORequesterID' => array('Type' => 'int'),
                'ORequester' => array('Type' => 'user'),
                'ONote' => array('Type' => 'string'),
                'OProcessStatus' => array('Type' => 'int'),

            ));
    }

    public function Execute()
    {
        global $USER, $DB;
        $sFormatDB = 'YYYY-MM-DD HH:MI:SS';
        $listFields = array();

        $listFields['OProcessStatus'] = 1;
        $listFields['ORequesterID'] = $USER->GetID();
        $listFields['ORequester'] = 'user_' . $listFields['ORequesterID'];
        $listFields['ODateTimeFormat'] = CSite::GetDateFormat();
        $listFields['OIntegrationTime'] = $DB->FormatDate(date("Y-m-d H:i:s"), $sFormatDB, $listFields['ODateTimeFormat']);

        $this->WriteToTrackingService(
            GetMessage('YNSIR_ACTIVITY_START_CONVERT_DATA')
        );
        if (!CModule::IncludeModule('ynsirecruitment') || !CModule::IncludeModule('hrm')) {
            return CBPActivityExecutionStatus::Closed;
        }
        if (!CModule::IncludeModule('ynsirecruitment')) {
            return CBPActivityExecutionStatus::Closed;
        }

        $candidate_id = intval($this->candidate_id);
        $job_order_id = intval($this->job_order_id);
        $asp_email = trim($this->asp_email);

        if ($candidate_id <= 0) {
            $listFields['OProcessStatus'] = 0;
            $listFields['OErrorCode'] = 'INPUT_IS_EMPTY';
            $this->WriteToTrackingService(
                GetMessage('YNSIR_ACTIVITY_ERROR_FIELD_REQUIED', array('#FIELD#' => GetMessage('YNSIR_ACTIVITY_CANDIDATE_ID')))
            );
            $this->SetProperties($listFields);
//            return CBPActivityExecutionStatus::Closed;
        }


        if ($job_order_id <= 0) {
            $listFields['OProcessStatus'] = 0;
            $listFields['OErrorCode'] = 'INPUT_IS_EMPTY';
            $this->WriteToTrackingService(
                GetMessage('YNSIR_ACTIVITY_ERROR_FIELD_REQUIED', array('#FIELD#' => GetMessage('YNSIR_ACTIVITY_JOB_ORDER')))
            );
            $this->SetProperties($listFields);
//            return CBPActivityExecutionStatus::Closed;
        }




        if (strlen($asp_email) <= 0) {
            $listFields['OProcessStatus'] = 0;
            $listFields['OErrorCode'] = 'INPUT_IS_EMPTY';
            $this->WriteToTrackingService(
                GetMessage('YNSIR_ACTIVITY_ERROR_FIELD_REQUIED', array('#FIELD#' => GetMessage('YNSIR_ACTIVITY_ASP_EMAIL')))
            );
            $this->SetProperties($listFields);

        }
        if ($listFields['OProcessStatus'] == 0) {
            return CBPActivityExecutionStatus::Closed;
        }
        // Get candidate information
        $db = YNSIRCandidate::GetListCandidateNonePerms(array(), array('ID' => $candidate_id));
        $candidate = $db->Fetch();
        if (empty($candidate)) {
            $listFields['OProcessStatus'] = 0;
            $listFields['OErrorCode'] = 'CANDIDATE_NOT_FOUND';
            $this->SetProperties($listFields);
            return CBPActivityExecutionStatus::Closed;
        } else {
            //get multiple fields
            $candidate_config = YNSIRConfig::GetListTypeList();
            $dbMultiField = YNSIRCandidate::GetListMultiField(array(), array('CANDIDATE_ID' => $candidate_id, 'TYPE' => 'WORK_POSITION'));
            if ($m = $dbMultiField->Fetch()) {
                $candidate['WORK_POSITION']['ID'][] = $m['CONTENT'];
                $candidate['WORK_POSITION']['TITLE'][] = $candidate_config['WORK_POSITION'][$m['CONTENT']]['NAME_' . strtoupper(LANGUAGE_ID)];
            }
        }
        $sFormatName = CSite::GetNameFormat(false);
        $listFields['OFullName'] = CUser::FormatName(
            $sFormatName,
            array(
                "NAME" => $candidate['FIRST_NAME'],
                "LAST_NAME" => $candidate['LAST_NAME'],
            )
        );
        // get job order
        $db = YNSIRJobOrder::GetList(array(), array('CHECK_PERMISSIONS' => 'N', 'ID' => $job_order_id));
        $job_order = $db->Fetch();
        if (empty($job_order)) {
            $listFields['OProcessStatus'] = 0;
            $listFields['OErrorCode'] = 'JOB_ORDER_NOT_FOUND';
            $this->SetProperties($listFields);
            return CBPActivityExecutionStatus::Closed;
        }
        //get intranet user by email (required hrm module)
        $db = CUser::GetList($byUser = 'ID', $orderUser = 'ASC', array('EMAIL' => $asp_email));
        $intranet_user = $db->Fetch();
        if (empty($intranet_user)) {
            $listFields['OProcessStatus'] = 0;
            $listFields['OErrorCode'] = 'INTRANET_USER_NOT_FOUND';
            $this->SetProperties($listFields);
            return CBPActivityExecutionStatus::Closed;
        }
        $rsUser = HRMUserprofile::getList(array(), array('USER_ID' => $intranet_user['ID']));
        if ($arElement = $rsUser->Fetch()) {
            $listFields['OProcessStatus'] = 0;
            $listFields['OErrorCode'] = 'HRM_STAFF_EXIST';
            $this->SetProperties($listFields);
            return CBPActivityExecutionStatus::Closed;
        }
        //check candidate joborder
        $obRes = YNSIRAssociateJob::GetList(array(), array('CANDIDATE_ID' => $candidate_id, 'ORDER_JOB_ID' => $job_order_id), false, false, array('ID'));
        if (!$obRes->Fetch()) {
            $listFields['OProcessStatus'] = 0;
            $listFields['OErrorCode'] = 'CANDIDATE_NOT_MATCHING_JOB_ORDER';
            $this->WriteToTrackingService(
                GetMessage('YNSIR_ACTIVITY_ERROR_CANDIDATE_NOT_MATCHING_JOB_ORDER')
            );
            $this->SetProperties($listFields);
            return CBPActivityExecutionStatus::Closed;
        }
        //get company structure
        if (intval($job_order['DEPARTMENT']) > 0) {
            $candidate['DEPARTMENT_ID'] = array($job_order['DEPARTMENT']);
            $COMPANY_CONFIG = unserialize(COption::GetOptionString("hrm", "department"));
            $company_id = array();
            foreach ($COMPANY_CONFIG as $COMPANY) {
                $arFullDept = HRMGReport::getSubDept($COMPANY, "Y");
                if (!empty($arFullDept[$job_order['DEPARTMENT']])) {
                    $candidate['COMPANY_ID'] = $COMPANY;
                    $candidate['COMPANY_NAME'] = $arFullDept[$COMPANY]['NAME'];
                    break;
                }
            }
        }
        // convert data from candidate to advance staff profile
        // create starf profile in module hrm
        $profile_data = array(
            'NAME' => $candidate['FIRST_NAME'],
            'LAST_NAME' => $candidate['LAST_NAME'],
            'PERSONAL_BIRTHDAY' => $candidate['DOB'],
            'WORK_POSITION' => implode(",", $candidate['WORK_POSITION']['TITLE']),
            'PERSONAL_GENDER' => $candidate['GENDER'],
//            'UF_SKYPE' => '',
            'UF_DEPARTMENT' => $candidate['DEPARTMENT_ID'],
//            'UF_PHONE_INNER' => '',
//            'UF_FACEBOOK' => '',
            'WORK_COMPANY' => $candidate['COMPANY_NAME'],
        );
        $formartbitrix = CSite::GetDateFormat("SHORT");
        $res = $USER->Update($intranet_user['ID'], $profile_data);
        if ($res) {
            $listFields['OProcessStatus'] = 1;
        } else {
            $listFields['OErrorCode'] = 'ERROR_UPDATED_INTRANET_USER';
        }
        //update user in hrm module
        $work_start_date = $DB->FormatDate(date("Y-m-d"), "YYYY-MM-DD", $formartbitrix);
        $arNewFieldsValue = array(
            'USER_ID' => $intranet_user['ID'],
            'WORK_START_DATE' => $work_start_date,
            'LINE_MANAGER' => $job_order['SUPERVISOR'],
            'COMPANY_ID' => $candidate['COMPANY_ID'],
            'LEVEL' => 0,
        );
        $rsUser = HRMUserprofile::getList(array(), array('USER_ID' => $intranet_user['ID']));
        if ($arElement = $rsUser->Fetch()) {
            //$hrm_usr_id = HRMUserprofile::Update($arElement['ID'], $arNewFieldsValue);
            $listFields['OProcessStatus'] = 0;
            $listFields['OErrorCode'] = 'HRM_STAFF_EXIST';
            $this->SetProperties($listFields);
            return CBPActivityExecutionStatus::Closed;
        } else {
            $hrm_usr_id = HRMUserprofile::Add($arNewFieldsValue);
        }
        $listFields['ODetailLink'] = '/hrm/profile/detail/' . $intranet_user['ID'] . '/';
        //list
        $arInputList['WORK_POSITION'] = array();
        foreach ($candidate['WORK_POSITION']['ID'] as $id) {
            $arInputList['WORK_POSITION'][] = array(
                'USER_ID' => $intranet_user['ID'],
                'ENTITY' => 'WORK_POSITION',
                'SOURCE_ID' => $id,
                'VALUE' => NULL,
                'CONTENT' => NULL,
            );
        }


       // $res_list = HRMUserprofile::DeleteListByUser($intranet_user['ID'], array_keys($arInputList));
        $ID_list = HRMUserprofile::AddList($arInputList);
//        if ($ID_list > 0) {
            //change status and block candidata
            $obRes = YNSIRAssociateJob::GetList(array(), array('CANDIDATE_ID' => $candidate_id, 'ORDER_JOB_ID' => $job_order_id), false, false, array('ID'));
            if ($rs = $obRes->Fetch()) {
                $arUpdateData = array('STATUS_ID' => 'HIRED');
                YNSIRAssociateJob::Update($rs['ID'], $arUpdateData, true, true);
            }else{
                $listFields['OProcessStatus'] = 0;
                $listFields['OErrorCode'] = 'CANDIDATE_NOT_MATCHING_JOB_ORDER';
                $this->WriteToTrackingService(
                    GetMessage('YNSIR_ACTIVITY_ERROR_CANDIDATE_NOT_MATCHING_JOB_ORDER')
                );
                $this->SetProperties($listFields);
            }

            //add tracking
            YNSIRUserRelation::Add(array(
                'SOURCE_ID'=> $candidate_id,
                'USER_ID'=> intval($hrm_usr_id),
                'ENTITY'=> 'ONBOARDING',
            ));

//            $DB->Commit();
//        } else {
//            $DB->Rollback();
//            $listFields['OProcessStatus'] = 0;
//            $listFields['OErrorCode'] = 'ROLLBACK DATA';
//            $this->SetProperties($listFields);
//            return CBPActivityExecutionStatus::Closed;
//        }

        //update status association

        $this->WriteToTrackingService(
            GetMessage('YNSIR_ACTIVITY_FINISH_CONVERT_DATA')
        );
        $this->SetProperties($listFields);

        return CBPActivityExecutionStatus::Closed;
    }

    public static function ValidateProperties($testProperties = array(), CBPWorkflowTemplateUser $user = null)
    {
        $errors = array();

        try {
            CBPHelper::ParseDocumentId($testProperties['DocumentType']);
        } catch (Exception $e) {
            $errors[] = array(
                'code' => 'NotExist',
                'parameter' => 'DocumentType',
                'message' => GetMessage('YNSIR_ACTIVITY_ERROR_DT')
            );
        }

        return array_merge($errors, parent::ValidateProperties($testProperties, $user));
    }

    //Gender Form config BP
    public static function GetPropertiesDialog(
        $documentType, $activityName, $workflowTemplate, $workflowParameters,
        $workflowVariables, $currentValues = null, $formName = '')
    {
        if (!is_array($workflowParameters))
            $workflowParameters = array();
        if (!is_array($workflowVariables))
            $workflowVariables = array();
        $renderEntityFields = '';

        if (!is_array($currentValues)) {
            $currentValues = array(
                'candidate_id' => null,
                'job_order_id' => null,
                'asp_email' => null,
            );
            $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
            if (is_array($currentActivity['Properties'])) {
                $currentValues['candidate_id'] = $currentActivity['Properties']['candidate_id'];
                $currentValues['job_order_id'] = $currentActivity['Properties']['job_order_id'];
                $currentValues['asp_email'] = $currentActivity['Properties']['asp_email'];
            }
        }


        $runtime = CBPRuntime::GetRuntime();
        return $runtime->ExecuteResourceFile(
            __FILE__,
            'properties_dialog.php',
            array(
                'documentType' => $documentType,
                'currentValues' => $currentValues,
                'formName' => $formName,
                'candidate_id' => $currentValues['candidate_id'],
                'job_order_id' => $currentValues['job_order_id'],
                'asp_email' => $currentValues['asp_email'],
            )
        );
    }

    //Validate and Get Value when save config BP activity
    public static function GetPropertiesDialogValues(
        $documentType, $activityName, &$workflowTemplate, &$workflowParameters,
        &$workflowVariables, $currentValues, &$errors)
    {
        $errors = array();
        if (empty($currentValues['candidate_id'])) {
            $errors[] = array(
                'code' => 'emptyRequiredField',
                'message' => GetMessage("YNSIR_ACTIVITY_ERROR_FIELD_REQUIED", array('#FIELD#' => GetMessage('YNSIR_ACTIVITY_CANDIDATE_ID'))),
            );
        }
        if (empty($currentValues['job_order_id'])) {
            $errors[] = array(
                'code' => 'emptyRequiredField',
                'message' => GetMessage("YNSIR_ACTIVITY_ERROR_FIELD_REQUIED", array('#FIELD#' => GetMessage('YNSIR_ACTIVITY_JOB_ORDER'))),

            );
        }
        if (empty($currentValues['asp_email'])) {
            $errors[] = array(
                'code' => 'emptyRequiredField',
                'message' => GetMessage("YNSIR_ACTIVITY_ERROR_FIELD_REQUIED", array('#FIELD#' => GetMessage('YNSIR_ACTIVITY_ASP_EMAIL'))),
            );
        }

        $properties = array('DocumentType' => $documentType);
        $properties['candidate_id'] = $currentValues['candidate_id'];
        $properties['job_order_id'] = $currentValues['job_order_id'];
        $properties['asp_email'] = $currentValues['asp_email'];

        if (!empty($errors))
            return false;

        $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
        $currentActivity['Properties'] = $properties;

        return true;
    }
}