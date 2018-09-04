<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Disk\File;

if (!CModule::IncludeModule("blog")) {
    return;
}

class CBPYnsirgetConfigActivity extends CBPActivity
{

    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            // data result
            "Title" => '',
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
                'OHMUser' => array(
                    'Type' => 'int',
                    'Multiple' => true,
                ),
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

        if (!CModule::IncludeModule('ynsirecruitment')) {
            $this->WriteToTrackingService(GetMessage('YNSIR_MODULE_NOT_INSTALLED'));
            return CBPActivityExecutionStatus::Closed;
        }
        $configs = unserialize(COption::GetOptionString('ynsirecruitment', 'ynsir_hr_manager_config'));
        $listFields['OHMUser'] = $configs;
        $this->WriteToTrackingService(GetMessage('YNSIR_CFACTIVITY_GET_CONFIG_RECRUITMENT'));

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
                'message' => GetMessage('YNSIR_CFACTIVITY_ERROR_DT')
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

            );
            $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
            if (is_array($currentActivity['Properties'])) {

            }
        }


        $runtime = CBPRuntime::GetRuntime();
        return $runtime->ExecuteResourceFile(
            __FILE__,
            'properties_dialog.php',
            array(
                'documentType' => $documentType,
                'currentValues' => $currentValues,
                'formName' => $formName
            )
        );
    }

    //Validate and Get Value when save config BP activity
    public static function GetPropertiesDialogValues(
        $documentType, $activityName, &$workflowTemplate, &$workflowParameters,
        &$workflowVariables, $currentValues, &$errors)
    {
        $errors = array();
        $properties = array('DocumentType' => $documentType);

        if (!empty($errors))
            return false;

        $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
        $currentActivity['Properties'] = $properties;

        return true;
    }
}