<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPYNSIREventAddActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'EventType' => '',
			'EntityType' => '',
			'EntityID' => '',
			'EventText' => ''
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('ynsirecruitment'))
			return CBPActivityExecutionStatus::Closed;

//		$arEntity[$arDocumentInfo[1]] = array(
//			'ENTITY_TYPE' => $arDocumentInfo[0],
//			'ENTITY_ID' => (int) $arDocumentInfo[1]
//		);

        $arEventType = YNSIREvent::GetEventTypes();
		$arFields = array(
            'ENTITY_ID' => $this->EntityID,
            'ENTITY_TYPE' => $this->EntityType,
            'EVENT_TYPE' => $this->EventType,
			'EVENT_NAME' => $arEventType[$this->EventType],
			'EVENT_TEXT_1' => $this->EventText,
			'USER_ID' => 1,
		);
		$YNSIREvent = new YNSIREvent();
		if (!$YNSIREvent->Add($arFields, false))
		{
			global $APPLICATION;
			$e = $APPLICATION->GetException();
			throw new Exception($e->GetString());
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (!array_key_exists('EventType', $arTestProperties) || strlen($arTestProperties['EventType']) <= 0)
			$arErrors[] = array('code' => 'NotExist', 'parameter' => 'EventType', 'message' => GetMessage('BPEAA_EMPTY_TYPE'));
		if (!array_key_exists('EntityType', $arTestProperties) || strlen($arTestProperties['EntityType']) <= 0)
			$arErrors[] = array('code' => 'NotExist', 'parameter' => 'EntityType', 'message' => GetMessage('BPEAA_EMPTY_TYPE'));
		if (!array_key_exists('EventText', $arTestProperties) || strlen($arTestProperties['EventText']) <= 0)
			$arErrors[] = array('code' => 'NotExist', 'EventText' => 'MessageText', 'message' => GetMessage('BPEAA_EMPTY_MESSAGE'));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '')
	{
		if (!CModule::IncludeModule('ynsirecruitment'))
			return false;

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			'EventType' => 'event_type',
			'EntityType' => 'entity_type',
			'EntityID' => 'entity_ID',
			'EventText' => 'event_text'
		);

		if (!is_array($arWorkflowParameters))
			$arWorkflowParameters = array();
		if (!is_array($arWorkflowVariables))
			$arWorkflowVariables = array();

		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity['Properties']))
			{
				foreach ($arMap as $k => $v)
				{
					if (array_key_exists($k, $arCurrentActivity['Properties']))
						$arCurrentValues[$arMap[$k]] = $arCurrentActivity['Properties'][$k];
					else
						$arCurrentValues[$arMap[$k]] = '';
				}
			}
			else
			{
				foreach ($arMap as $k => $v)
					$arCurrentValues[$arMap[$k]] = '';
			}
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			'properties_dialog.php',
			array(
				'arCurrentValues' => $arCurrentValues,
				'arEntityTypes' => YNSIREvent::getEntityType(),
				'arTypes' => YNSIREvent::GetEventTypes(),
				'formName' => $formName
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			'event_type' => 'EventType',
			'entity_type' => 'EntityType',
			'entity_ID' => 'EntityID',
			'event_text' => 'EventText'
		);

		$arProperties = array();
		foreach ($arMap as $key => $value)
			$arProperties[$value] = $arCurrentValues[$key];

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity['Properties'] = $arProperties;

		return true;
	}
}
?>