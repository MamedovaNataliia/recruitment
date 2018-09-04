<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPYNSIRGetOrderEntityActivity extends CBPActivity
{
    protected static $listDefaultEntityType = array('LEAD', 'CONTACT', 'COMPANY', 'DEAL');

    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            'Title' => '',
            'EntityId' => null,

            // data result
            'ODetailLink' => null,
            'OSupervisor' => null,
            'ORecruiter' => null,
            'Owner' => null,
            'OIntegrationTime' => null,
            'ODateTimeFormat' => null,
            'ORequesterID' => null,
            'ORequester' => null,
            'OErrorCode' => null,
            'OProcessStatus' => null,
        );
        $this->SetPropertiesTypes(
            array(
                'ODetailLink' => array('Type' => 'string'),
                'OErrorCode' => array('Type' => 'string'),
                'OIntegrationTime' => array('Type' => 'string'),
                'ODateTimeFormat' => array('Type' => 'string'),
                'ORequesterID' => array('Type' => 'int'),
                'OSupervisor' => array('Type' => 'int'),
                'ORecruiter' => array('Type' => 'int'),
                'Owner' => array('Type' => 'int'),
                'ORequester' => array('Type' => 'user'),
                'OProcessStatus' => array('Type' => 'int'),
            ));
    }

    public function Execute()
    {

        if(
            !($this->EntityId)||
            !CModule::IncludeModule('ynsirecruitment'))
        {
            return CBPActivityExecutionStatus::Closed;
        }

        $listFields = array();

        foreach($this->getEntityData() as $fieldId => $fieldValue)
            $listFields[$fieldId] = $fieldValue;

        $this->SetProperties($listFields);

        return CBPActivityExecutionStatus::Closed;
    }

    protected function getEntityData()
    {
        global $USER, $DB;
        $entityId = null;
        $objectResult = null;
        $sFormatDB = 'YYYY-MM-DD HH:MI:SS';

        if(!CModule::IncludeModule('ynsirecruitment'))
            return array();

        if(intval($this->EntityId))
        {
            $entityId = intval($this->EntityId);
            $entityData =  YNSIRJobOrder::getById($entityId);
        }
        else
        {
            //Get from Workflow Element ID
            $rootActivity = $this->GetRootActivity();
            $documentId = $rootActivity->GetDocumentId();

            $arDocumentInfo = explode('_', $documentId['2']);
            $rsResult =  YNSIRJobOrder::GetList(array(),array('WF_ELEMENT'=> $arDocumentInfo[0],'CHECK_PERMISSIONS' => 'N'));
            $entityData = $rsResult->Fetch();
        }
        $currentUserID = YNSIRSecurityHelper::GetCurrentUserID();
        if(empty($entityData)) {
            $this->WriteToTrackingService(
                GetMessage('YNSIR_ACTIVITY_ERROR_ENTITY_ID_NOT_FOUND', array('#ENTITY_ID#' => $entityId)),
                $currentUserID
            );
            $entityData['OProcessStatus'] = 0;
        } else {
            $entityData['ODetailLink'] = '/recruitment/job-order/detail/' . $entityData['ID'] . '/';
            $entityData['OProcessStatus'] = 1;
        }
        $entityData['OSupervisor'] = 'user_'.$entityData['SUPERVISOR'];
        $entityData['ORecruiter'] = 'user_'.$entityData['RECRUITER'];
        $entityData['Owner'] = 'user_'.$entityData['OWNER'];
        $entityData['ORequesterID'] = $currentUserID;
        $entityData['ORequester'] = 'user_' . $entityData['ORequesterID'];
        $entityData['ODateTimeFormat'] = CSite::GetDateFormat();
        $entityData['OIntegrationTime'] = $DB->FormatDate(date("Y-m-d H:i:s"), $sFormatDB, $entityData['ODateTimeFormat']);

        if($entityData)
        {
            return $entityData;
        }
        else
        {
            return array();
        }
    }

    public static function ValidateProperties($testProperties = array(), CBPWorkflowTemplateUser $user = null)
    {
        $errors = array();

        try
        {
            CBPHelper::ParseDocumentId($testProperties['DocumentType']);
        }
        catch (Exception $e)
        {
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
        if(!is_array($workflowParameters))
            $workflowParameters = array();
        if(!is_array($workflowVariables))
            $workflowVariables = array();
        $renderEntityFields = '';

        if(!is_array($currentValues))
        {
            $currentValues = array(
                'EntityId' => null,
            );
            $currentActivity= &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
            if(is_array($currentActivity['Properties']))
            {
                $currentValues['EntityId'] = $currentActivity['Properties']['EntityId'];
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
            )
        );
    }

    //Validate and Get Value when save config BP activity
    public static function GetPropertiesDialogValues(
        $documentType, $activityName, &$workflowTemplate, &$workflowParameters,
        &$workflowVariables, $currentValues, &$errors)
    {
        $errors = array();

        if(empty($currentValues['EntityId']))
        {
            $errors[] = array(
                'code' => 'emptyRequiredField',
                'message' => str_replace('#FIELD#',
                    GetMessage("YNSIR_ACTIVITY_ERROR_ENTITY_ID").', '.GetMessage("YNSIR_ACTIVITY_ERROR_ENTITY_TYPE")
                    , GetMessage("YNSIR_ACTIVITY_ERROR_FIELD_REQUIED")),
            );
            return false;
        }

        $properties = array('DocumentType' => $documentType);
        $properties['EntityId'] = $currentValues['EntityId'];

        if(!empty($errors))
            return false;

        $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
        $currentActivity['Properties'] = $properties;

        return true;
    }
}